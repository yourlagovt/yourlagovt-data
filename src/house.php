<?php

require __DIR__ . '/lib.php';

$representatives = [];

// FULL INFORMATION
$doc = fetchHtml('https://house.louisiana.gov/H_Reps/H_Reps_FullInfo');
$fieldsets = $doc->getElementsByTagName('fieldset');
foreach ($fieldsets as $fieldset) {
    $fields = parseFieldset($fieldset);
    $representative = new stdClass;
    $representative->name = parseName($fields['LASTFIRST']);
    $representative->district = new stdClass;
    $representative->district->number = (int) $fields['DISTRICTNUMBER'];
    $representative->district->image = "https://house.louisiana.gov/DistLAMaps/district{$representative->district->number}.png";
    $representative->district->pdf = "https://house.louisiana.gov/H_Reps/DistrictMaps/District{$representative->district->number}.pdf";
    $representative->party = $fields['PARTYAFFILIATION'];
    $representative->email = $fields['EMAILADDRESSPUBLIC'];
    $representative->photo = new stdClass;
    $representative->photo->small = "https://house.louisiana.gov/H_Reps/RepPics20/rep{$representative->district->number}.jpg";
    $representative->photo->large = "https://house.louisiana.gov/H_Reps/RepsLargeDownload/Dist{$representative->district->number}.jpg";
    $representative->senate_districts = array_map('intval', preg_split('/, and |, | and /', $fields['SENATEDISTRICT']));
    $representative->url = new stdClass;
    $representative->url->house = "https://house.louisiana.gov/H_Reps/members.aspx?ID={$representative->district->number}";
    $representative->addresses = parseAddresses($fields['OFFICEADDRESS']);
    $representative->phone = formatPhone($fields['DISTRICTOFFICEPHONE']);
    $representatives[$representative->district->number] = $representative;
}

// INDIVIDUAL INFORMATION
foreach ($representatives as $representative) {
    $doc = fetchHtml($representative->url->house);
    $representative->assistants = explode(' and ', trim($doc->getElementById('body_FormView6_LEGISLATIVEAIDELabel')->textContent));
    $representative->parishes = preg_split('/, and | and |, /', $doc->getElementById('body_FormView6_DISTRICTPARISHESLabel')->textContent);
    $representative->elected = (int) $doc->getElementById('body_FormView4_YEARELECTEDLabel')->textContent;
    $representative->eligible = (int) $doc->getElementById('body_FormView4_FINAL_TERMLabel')->textContent;
    $representative->url->legislation = $doc->getElementById('body_DetailsView6')->getElementsByTagName('a')->item(0)->getAttribute('href');
}

// LEGISCAN
addLegiScanData($representatives, 'Rep');
$bills = getLegiScanBills($representatives, 'Rep');

// CURATED DATA
$curated = parseJsonFile(__DIR__ . '/../data/representatives-curated.json');
foreach ($curated as $representative) {
    $district = $representative->district;
    $representatives[$district]->gender = $representative->gender;
    $representatives[$district]->race = $representative->race;
    foreach ($representative->url ?? [] as $name => $value) {
        $representatives[$district]->url->$name = $value;
    }
}

// BALLOTPEDIA
addBallotpediaLinks($representatives);

// OUTPUT
$data = [
    'representatives' => array_values($representatives),
    'bills' => array_values($bills),
];
echo json_encode($data, \JSON_PRETTY_PRINT);
