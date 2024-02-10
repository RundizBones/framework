# Tasks to do before commit, release.

## Generate API doc
API doc is no need to generate every commit, just when there are changes on release.
* Run command `phpdoc2` for using phpDocumentor 2. (API doc is no need to generate every commit, just when there are changes on release.)
    Or use `phpdoc3 --config=phpdoc3.xml` for using phpDocumentor 3.

## Commit

## Release
* Update version name in System/App.php
* Run external pack command `rdbdev pack --packtype=dev prod` to pack files and folders into a zip file.
* Then commit to GitHub.