<?php

require __DIR__ . '/lib.php';

$senators = [];

// FULL INFORMATION
$doc = fetchHtml('https://senate.la.gov/Senators_FullInfo');
$fieldsets = $doc->getElementsByTagName('fieldset');
foreach ($fieldsets as $fieldset) {
    $fields = parseFieldset($fieldset);
    $name = $fields['LASTFIRST'];
    if (str_starts_with($name, 'Vacant')) {
        continue;
    }
    $senator = new stdClass;
    $senator->name = parseName($name);
    $senator->district = new stdClass;
    $senator->district->number = (int) $fields['DISTRICTNUMBER'];
    $senator->district->image = "https://senate.la.gov/MapsGraphics39all/district{$senator->district->number}.png";
    $senator->district->pdf = "https://senate.la.gov/DistrictMaps/District{$senator->district->number}.pdf";
    $senator->party = $fields['PARTYAFFILIATION'];
    $senator->addresses = parseAddresses($fields['OFFICEADDRESS']);
    $senator->phone = formatPhone($fields['DISTRICTOFFICEPHONE']);
    $senator->email = $fields['EMAILADDRESSPUBLIC'];
    $senator->photo = new stdClass;
    $senator->photo->small = "https://senate.la.gov/SenatorPics/Sen{$senator->district->number}.jpg";
    $senator->photo->large = "https://senate.la.gov/Senators/SenLargePics/Dist{$senator->district->number}.JPG";
    $senator->url = new stdClass;
    $senator->url->senate = "https://senate.la.gov/smembers.aspx?ID={$senator->district->number}";
    $senator->url->bio = "https://senate.la.gov/Senators/Bios/district{$senator->district->number}.pdf";
    $senators[$senator->district->number] = $senator;
}

// PARISH
$doc = fetchHtml('https://senate.la.gov/Senators_ByParish');
$sections = array_filter(
    iterator_to_array($doc->getElementsByTagName('div')),
    fn($div) => $div->getAttribute('class') === 'accordion-section'
);
foreach ($sections as $section) {
    $as = $section->getElementsByTagName('a');
    $parish = trim(str_replace(' Parish', '', $as->item(0)->textContent));
    for ($item = 1; $item < $as->length; $item++) {
        preg_match('/[0-9]+$/', $as->item($item)->getAttribute('href'), $match);
        $district = (int) $match[0];
        $senator = $senators[$district] ?? null;
        if ($senator === null) {
            continue;
        }
        if (!isset($senator->parishes)) {
            $senator->parishes = [];
        }
        $senator->parishes[] = $parish;
    }
}

// TERM LIMITS
$doc = fetchHtml('https://senate.la.gov/Senators_ByTermLimits');
$table = $doc->getElementById('body_ListView1_groupPlaceholderContainer');
$ths = $table->getElementsByTagName('th');
foreach ($ths as $th) {
    $as = $th->getElementsByTagName('a');
    if (!$as->length) {
        continue;
    }
    preg_match('/[0-9]+$/', $as->item(0)->getAttribute('href'), $match);
    $district = (int) $match[0];
    $senator = $senators[$district] ?? null;
    if ($senator === null) {
        continue;
    }
    $spans = $th->getElementsByTagName('span');
    $senator->elected = (int) $spans->item(1)->textContent;
    $senator->eligible = (int) $spans->item(2)->textContent;
}

// INDIVIDUAL INFORMATION
foreach ($senators as $senator) {
    $doc = fetchHtml($senator->url->senate);
    $assistant = $doc->getElementById('body_FormView6_LEGISLATIVEAIDELabel')->textContent;
    if (!empty($assistant)) {
        $senator->assistant = $assistant;
    }
    $senator->house_districts = array_map('intval', explode(', ', $doc->getElementById('body_FormView6_OPPDISTRICTSLabel')->textContent));
    $senator->url->legislation = $doc->getElementById('body_DetailsView6mylegis')->getElementsByTagName('a')->item(0)->getAttribute('href');
}

// LEGISCAN
addLegiScanData($senators, 'Sen');
$bills = getLegiScanBills($senators, 'Sen');

// CURATED DATA
$curated = parseJsonFile(__DIR__ . '/../data/senators-curated.json');
foreach ($curated as $senator) {
    $district = $senator->district;
    if (!isset($senators[$district])) {
        continue;
    }
    $senators[$district]->gender = $senator->gender;
    $senators[$district]->race = $senator->race;
    foreach ($senator->url as $name => $value) {
        $senators[$district]->url->$name = $value;
    }
}

// BALLOTPEDIA
addBallotpediaLinks($senators);

// OUTPUT
$data = [
    'senators' => array_values($senators),
    'bills' => array_values($bills),
];
echo json_encode($data, \JSON_PRETTY_PRINT);
