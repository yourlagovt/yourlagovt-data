<?php

function fetch(string $sourceUrl): string
{
    $cacheFilename = preg_replace('/[^-A-Za-z0-9_.]/', '_', $sourceUrl);
    $cacheDir = __DIR__ . '/../cache';
    $cacheFile = $cacheDir . '/' . $cacheFilename;
    if (file_exists($cacheFile)) {
        $contents = file_get_contents($cacheFile);
    } else {
        $contents = file_get_contents($sourceUrl);
        if (!file_exists($cacheDir)) {
            mkdir($cacheDir);
        }
        file_put_contents($cacheFile, $contents);
    }
    return $contents;
}

function parseHtml(string $contents): DOMDocument
{
    $errors = libxml_use_internal_errors(true);
    $doc = new DOMDocument;
    $doc->loadHTML($contents);
    libxml_use_internal_errors($errors);
    return $doc;
}

function fetchHtml(string $sourceUrl): DOMDocument
{
    return parseHtml(fetch($sourceUrl));
}

function parseJsonFile(string $file)
{
    return file_exists($file) ? json_decode(file_get_contents($file)) : null;
}

function parseAddresses(string $rawAddresses): array
{
    $cityStateZipPattern = '/\v+(?P<city>[^,\v]+\S),\h+(?P<state>[A-Za-z]{2,})\s+(?P<zip>[0-9]{5}(?:-[0-9]{4})?)/S';
    $addresses = [];
    while (preg_match($cityStateZipPattern, $rawAddresses, $match, \PREG_OFFSET_CAPTURE)) {
        $street = trim(substr($rawAddresses, 0, $match[0][1]));
        $street = preg_replace(
            [
                '/^(?:Physical|Mailing)(?: Address)?:\s*/S',
                '/\h*\r\n\h*/S',
                '/\h{2,}/S',
            ],
            [
                '',
                ', ',
                ' ',
            ],
            $street
        );
        $address = new stdClass;
        $address->street = $street;
        $address->city = $match['city'][0];
        $address->state = strtoupper($match['state'][0]);
        $address->zip = $match['zip'][0];
        $addresses[] = $address;
        $rawAddresses = substr($rawAddresses, $match[0][1] + strlen($match[0][0]));
    }
    return $addresses;
}

function parseName(string $rawName): object
{
    $name = new stdClass;
    [$name->last, $name->first] = explode(', ', trim($rawName), 2);
    if (preg_match('/\s*([JS]r\.?|IV|I{1,3}),?\s*/', $name->first, $match)) {
        $name->first = str_replace($match[0], '', $name->first);
        $name->suffix = $match[1];
    }
    if (preg_match('/ "([^"]+)"/', $name->first, $match)) {
        $name->first = str_replace($match[0], '', $name->first);
        $name->nickname = $match[1];
    }
    return $name;
}

function formatPhone(string $phone): string
{
    return preg_replace('/^\\(?([0-9]{3})[\\)-]?\h*([0-9]{3})-([0-9]{4}).*$/', '+1-$1-$2-$3', $phone);
}

function parseFieldset(DOMElement $fieldset): array
{
    $fields = [];
    $spans = $fieldset->getElementsByTagName('span');
    foreach ($spans as $span) {
        $id = $span->getAttribute('id');
        if (preg_match('/^body_ListView1_(.+)Label_[0-9]+$/', $id, $match)) {
            $fields[$match[1]] = $span->textContent;
        }
    }
    return $fields;
}

function parseLegiScanDistrictNumber(string $district): int
{
    return (int) preg_replace('/^[HS]D-0*/', '', $district);
}

/**
 * @param string $role 'Sen' for Senator, 'Rep' for Representative
 * @return array Associative array of objects indexed by district number
 */
function getLegiScanPeople(string $role): array
{
    $files = glob(__DIR__ . '/../data/LA/2022-2022_Regular_Session/people/*');
    $people = array_map(
        fn($file) => parseJsonFile($file)->person,
        $files,
    );
    $peopleWithRole = array_filter($people, fn($person) => $person->role === $role);
    return array_reduce(
        $peopleWithRole,
        function ($index, $person) {
            $district = parseLegiScanDistrictNumber($person->district);
            $index[$district] = $person;
            return $index;
        },
        []
    );
}

/**
 * @param string $role 'Sen' for Senator, 'Rep' for Representative
 */
function getLegiScanVotesForPerson(int $peopleId, string $role): array
{
    $files = glob(__DIR__ . '/../data/LA/2022-2022_Regular_Session/vote/*');
    $personVotes = [];
    foreach ($files as $file) {
        $rollCall = parseJsonFile($file)->roll_call;
        $rollCallVotes = array_filter($rollCall->votes, fn($vote) => $vote->people_id === $peopleId);
        If (empty($rollCallVotes)) {
            continue;
        }
        $vote = array_shift($rollCallVotes);
        if (isset($personVotes[$rollCall->bill_id])
            && $personVotes[$rollCall->bill_id]->date > $rollCall->date) {
            continue;
        }
        $personVote = new stdClass;
        $personVote->date = $rollCall->date;
        $personVote->vote = $vote->vote_text;
        $personVotes[$rollCall->bill_id] = $personVote;
    }
    uasort($personVotes, fn($a, $b) => $b->date <=> $a->date);
    foreach ($personVotes as $billId => $personVote) {
        $personVote->billNumber = getLegiScanBill(billId: $billId, role: $role)->number;
    }
    return array_values($personVotes);
}

function getLegiScanBill(string $role, ?string $billId = null, ?string $billNumber = null): object
{
    static $billsById = [];
    static $billsByNumber = [];

    if (empty($billsById)) {
        $files = glob(__DIR__ . '/../data/LA/2022-2022_Regular_Session/bill/*');
        foreach ($files as $file) {
            $bill = parseJsonFile($file)->bill;
            $billsById[$bill->bill_id] = $file;
            $billsByNumber[$bill->bill_number] = $file;
        }
    }

    if ($billId !== null) {
        $file = $billsById[$billId];
    } elseif ($billNumber !== null) {
        $file = $billsByNumber[$billNumber];
    } else {
        throw new \RuntimeException('No known identifier specified');
    }

    $rawBill = parseJsonFile($file)->bill;

    $bill = new stdClass;
    $bill->number = $rawBill->bill_number;
    $bill->title = $rawBill->title;
    $bill->url = new stdClass;
    $bill->url->state = $rawBill->state_link;
    $bill->url->legiscan = $rawBill->url;
    $bill->texts = array_map(
        function ($rawText) {
            $text = new stdClass;
            $text->state = $rawText->state_link;
            $text->legiscan = $rawText->url;
            return $text;
        },
        $rawBill->texts
    );
    $bill->sponsor_districts = array_map(
        fn($sponsor) => parseLegiScanDistrictNumber($sponsor->district),
        array_filter(
            $rawBill->sponsors,
            fn($sponsor) => $sponsor->role === $role
        )
    );
    sort($bill->sponsor_districts);
    $bill->subjects = array_map(
        fn($subject) => $subject->subject_name,
        $rawBill->subjects
    );
    sort($bill->subjects);

    return $bill;
}

function getLegiScanLink(string $name, string $id): string
{
    $nameSegment = str_replace(' ', '-', strtolower($name));
    return "https://legiscan.com/LA/people/$nameSegment/id/$id";
}

function getBallotpediaLink(string $id): string
{
    return "https://ballotpedia.org/{$id}";
}

function getFollowTheMoneyLink(string $eid): string
{
    return "https://www.followthemoney.org/entity-details?eid={$eid}";
}

function getVoteSmartLink(string $id): string
{
    return "https://justfacts.votesmart.org/candidate/biography/{$id}";
}

function addLegiScanData(array $officials, string $role)
{
    foreach (getLegiScanPeople($role) as $district => $person) {
        $official = $officials[$district] ?? null;
        if ($official === null) {
            continue;
        }
        $official->url->ballotpedia = getBallotpediaLink($person->ballotpedia);
        $official->url->followthemoney = getFollowTheMoneyLink($person->ftm_eid);
        $official->url->votesmart = getVoteSmartLink($person->votesmart_id);
        $official->url->legiscan = getLegiScanLink($person->name, $person->people_id);
        $official->votes = getLegiScanVotesForPerson($person->people_id, $role);
    }
}

function getLegiScanBills(array $officials, string $role): array
{
    $bills = [];
    foreach ($officials as $official) {
        foreach ($official->votes as $vote) {
            if (!isset($bills[$vote->billNumber])) {
                $bills[$vote->billNumber] = getLegiScanBill(billNumber: $vote->billNumber, role: $role);
            }
        }
    }
    return $bills;
}

function addBallotpediaLinks(array $officials)
{
    foreach ($officials as $official) {
        $response = fetchHtml($official->url->ballotpedia);

        $divs = array_filter(
            iterator_to_array($response->getElementsByTagName('div')),
            fn($div) => str_contains($div->getAttribute('class'), 'infobox'),
        );
        $div = array_shift($divs);

        $divs = array_filter(
            iterator_to_array($div->childNodes),
            fn($div) => str_contains($div->textContent, 'Contact'),
        );
        $div = array_shift($divs);

        $links = [];

        do {
            $div = $div->nextElementSibling;
            $a = $div->getElementsByTagName('a')->item(0);
            $links[$a->textContent] = $a->getAttribute('href');
        } while ($div->nextElementSibling);

        if (!isset($official->url->facebook)) {
            foreach (['Official', 'Campaign', 'Personal'] as $text) {
                $key = "$text Facebook";
                if (isset($links[$key])) {
                    $official->url->facebook = $links[$key];
                    break;
                }
            }
        }

        if (!isset($official->url->website)) {
            if (isset($links['Official website']) && !str_contains($links['Official website'], 'house.louisiana.gov')) {
                $official->url->website = $links['Official website'];
            } elseif (isset($links['Campaign website'])) {
                $official->url->website = $links['Campaign website'];
            }
        }
    }
}
