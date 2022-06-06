Our data sets and the scripts that compile them.

# Datasets

These datasets are compiled from multiple sources.

- `data/senate.json` - Louisiana Senate
- `data/house.json` - Louisiana House of Representatives

These datasets are original and manually curated.

- `data/representatives-curated.json` - Louisiana Representatives
- `data/senators-curated.json` - Louisiana Senators

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
