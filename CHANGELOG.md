# Changelog

## 1.2.2 - 2021-07-02

### Changed

- Imports of existing combinations now update the `importTime` column after completing the import process.
- Cleaning up the database tables have been moved to a separate `clean` command instead of doing it with each import.

## 1.2.1 - 2021-05-24

### Changed

- PHP version from 7.4 to 8.0.

## 1.2.0 - 2021-02-18

## Changed

- Updated `factorio-item-browser/export-data` to its latest version.
- Replaced deprecated Export-Queue with the new Combination API.

## 1.1.0 - 2020-07-14

### Changed

- Importers to process the data in chunks to avoid running out of memory.

## 1.0.3 - 2020-06-03

### Changed

- Dependency `factorio-item-browser/export-queue-client` to version 1.2.
- Dependency `factorio-item-browser/api-database` to version 3.2.
- Using ordering `priority` to fetch the next job to process.
- Validation no longer modifies case of identifiers, as the game is case-sensitive not as well.

### Removed

- Support for PHP 7.3. The project must now run with at least PHP 7.4.

## 1.0.2 - 2020-05-03

### Fixed

- Missing icons for some mods because of normalizing the icon names.

## 1.0.1 - 2020-05-02

### Fixed

- Missing error message in export queue in case an import failed.
- Failed imports when some values got used (e.g. too large crafting times).

## 1.0.0 - 2020-04-16

- Initial release of the API import.
