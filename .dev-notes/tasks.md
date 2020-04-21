# Tasks to do before commit, release.

## Generate API doc
API doc is no need to generate every commit, just when there are changes on release.
* Run command `phpdoc2` or using phpDocumentor 2.

## Commit

## Release
* Update version name in System/App.php
* Run command `npm run pack && npm run pack -- --development` to pack the files into zip files.
* Then commit to GitHub.