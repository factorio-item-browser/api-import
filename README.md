![Factorio Item Browser](https://raw.githubusercontent.com/factorio-item-browser/documentation/master/asset/image/logo.png) 

# API Import

[![GitHub release (latest SemVer)](https://img.shields.io/github/v/release/factorio-item-browser/api-import)](https://github.com/factorio-item-browser/api-import/releases)
[![GitHub](https://img.shields.io/github/license/factorio-item-browser/api-import)](LICENSE.md)
[![build](https://img.shields.io/github/workflow/status/factorio-item-browser/api-import/CI?logo=github)](https://github.com/factorio-item-browser/api-import/actions)
[![Codecov](https://img.shields.io/codecov/c/gh/factorio-item-browser/api-import?logo=codecov)](https://codecov.io/gh/factorio-item-browser/api-import)

This project imports the data generated by the export into the database of the API server.

### Commands

The project provides the following commands to actually import the data:

* `bin/cli.php process`: Requests an export from the queue and processing it. This is the main command to be put in a 
  crontab to have the exports imported into the database automatically, and it will call the other commands to do so.
* `bin/cli.php import <combination-id>`: Imports the main data of the combination into the database, including items,
  recipes and machines.
* `bin/cli.php import-images <combination-id>`: Import all the image files into the database. This requires that the
  database entities have already been created in the database, only the `content` column is updated.
* `bin/cli.php import-translations <combination-id>`: Imports all the translations of the combination into the database.
  Already existing translations will be cleaned up.
