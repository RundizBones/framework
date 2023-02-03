# Tasks to do before commit, release.

## Generate API doc
API doc is no need to generate every commit, just when there are changes on release.
* Run command `phpdoc2` or using phpDocumentor 2.

## Commit

## Release
* Update version name in System/App.php
* Run external pack command `rdbdev pack --packtype=dev prod --module=framework` to pack files and folders into a zip file.
* Then commit to GitHub.