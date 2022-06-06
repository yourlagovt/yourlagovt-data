Our data sets and the scripts that compile them.

# Datasets

These datasets are compiled from multiple sources.

- `data/senate.json` - Louisiana Senate
- `data/house.json` - Louisiana House of Representatives

These datasets are original and manually curated.

- `data/representatives-curated.json` - Louisiana Representatives
- `data/senators-curated.json` - Louisiana Senators

Each of `data/senate.json` and `data/house.json` has two main sections.

The first section contains data specific to individual officials. This section is named `senators` and `representatives` for `data/senate.json` and `data/house.json` respectively.

The second section contains data regarding legislation on which these officials have voted. This section is called `bills` in both files.

Below are tables detailing the data that can be found in each of these sections.

## Officials

| Field | Description |
| ----- | ----------- |
| `.name.last` | Surname |
| `.name.first` | Given name |
| `.district.number` | District number |
| `.district.image` | Image containing a map of the state with the district highlighted |
| `.district.pdf` | PDF containing a high-resolution map of the district |
| `.party` | Name of the political party with which the official is affiliated |
| `.gender` | Gender of the official |
| `.race` | Race of the official (e.g. `Caucasian`, `African-American`, etc.) |
| `.addresses.#.street` | Street address or post office box number |
| `.addresses.#.city` | City name |
| `.addresses.#.state` | State abbreviation, kept for completeness |
| `.addresses.#.zip` | Zip code |
| `.phone` | Office phone number |
| `.email` | Office e-mail address |
| `.assistants` | List of names of the official's assistant(s) |
| `.photo.small` | Image containing a small photo of the official |
| `.photo.large` | Image containing a larger photo of the official |
| `.url.house` | URL of the representative's page on the state [House of Representatives web site](https://house.louisiana.gov/) where applicable |
| `.url.senate` | URL of the senator's page on the state [Senate web site](https://senate.la.gov/) where applicable |
| `.url.bio` | PDF containing a short biography about the senator where applicable |
| `.url.legislation` | URL of the official's page on the [state legislature web site](https://legis.la.gov/legis/home.aspx) |
| `.url.ballotpedia` | URL of the official's page on [Ballotpedia](https://ballotpedia.org/Main_Page) |
| `.url.followthemoney` | URL of the official's page on [FollowTheMoney](https://www.followthemoney.org/) |
| `.url.votesmart` | URL of the official's page on [Vote Smart](https://justfacts.votesmart.org/) |
| `.url.legiscan` | URL of the official's page on [LegiScan](https://legiscan.com/) |
| `.url.website` | URL of the official's own official, campaign, or personal web site where applicable |
| `.url.wikipedia` | URL of the senator's page on [Wikipedia](https://www.wikipedia.org/) where applicable |
| `.url.facebook` | URL of the official's own official, campaign, or personal Facebook page where applicable |
| `.url.twitter` | URL of the official's own official, campaign, or personal Twitter page where applicable |
| `.url.youtube` | URL of the official's own official, campaign, or personal YouTube page where applicable |
| `.url.instagram` | URL of the official's own official, campaign, or personal Instagram page where applicable |
| `.parishes` | List of the names of parishes represented by the official |
| `.house_districts` | House districts represented by the senator where applicable |
| `.senate_districts` | Senate districts represented by the representative where applicable |
| `.elected` | Year the official was elected |
| `.eligible` | Year the official will be eligible for reelection |
| `.votes.#.date` | Most recent date on which the official voted for an article of legislation |
| `.votes.#.vote` | Most recent vote of the official on an article of legislation (`Yea`, `Nay`, or `Absent`) |
| `.votes.#.bill_number` | Identification number attached to the article of legislation on which the official voted |

## Legislation

| Field | Description |
| ----- | ----------- |
| `.number` | Identification number attached to the article of legislation |
| `.title` | Descriptive title of the article of legislation |
| `.sponsor_districts` | District numbers associated with the senators or representatives sponsoring the article of legislation |
| `.subjects` | List of topical subjects of the article of legislation |
| `.url.state` | URL for the article of legislation on the [state legislature web site](https://legis.la.gov/legis/home.aspx) |
| `.url.legiscan` | URL for the article of legislation on [LegiScan](https://legiscan.com/) |
| `.texts.#.state` | URL for a document related to the text of the article of legislation on the [state legislature web site](https://legis.la.gov/legis/home.aspx) |
| `.texts.#.legiscan` | URL for a document related to the text of the article of legislation on [LegiScan](https://legiscan.com/) |

Key for identification numbers:

- `H` - [House of Representatives](https://house.louisiana.gov/)
- `S` - [Senate](https://senate.la.gov/)
- `B` - [Bill](https://en.wikipedia.org/wiki/Bill_(law))
- `R` - [Resolution](https://en.wikipedia.org/wiki/Resolution_(law))
- `CR` - [Concurrent Resolution](https://en.wikipedia.org/wiki/Concurrent_resolution)

# Sources

- [Louisiana House of Representatives](https://house.louisiana.gov/)
- [Louisiana State Senate](https://senate.la.gov/)
- [Louisiana State Legislature](https://legis.la.gov/legis/home.aspx)
- [LegiScan](https://legiscan.com/)
- [Ballotpedia](https://ballotpedia.org/)

# Building

To do a local build to update the datasets, you will need:

- A local clone of this repository
- PHP 8+ or Docker
- A [LegiScan account](https://legiscan.com/user/register)

## LegiScan

1. Log in to LegiScan and navigate to the [Datasets](https://legiscan.com/datasets) page.
2. Locate the dataset for the Louisiana Regular Session and click the JSON link for that dataset.
3. Move the downloaded ZIP file into the `data` directory of your local clone of this repository and decompress it there.

## Scripts

### Senate

`src/senate.php` builds `data/senate.json`.

```sh
php src/senate.php > data/senate.json
```

### House of Representatives

`src/house.php` builds `data/house.json`.

```sh
php src/house.php > data/house.json
```

## Docker

To run one of the PHP scripts from this repository using Docker:

```sh
docker run -it --rm -v `pwd`:/app -w /app php:8-cli-alpine php SCRIPT
```

# Licensing

Source code in this repository is licensed under the [MIT License](https://opensource.org/licenses/MIT).

Data originating from this repository is licensed under the [Open Database License](https://opendatacommons.org/licenses/odbl/1-0/).

All other data contained in or referenced by this repository is licensed under the terms of its respective originating sources.
