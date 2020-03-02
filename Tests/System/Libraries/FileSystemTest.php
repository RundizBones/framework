<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Tests\System\Libraries;


class FileSystemTest extends \Rdb\Tests\BaseTestCase
{


    /**
     * @var \Rdb\System\Libraries\FileSystem
     */
    protected $FileSystem;


    /**
     * @var string Path to target test folder without trailing slash.
     */
    protected $targetTestDir;


    public function setUp()
    {
        $this->targetTestDir = RDB_TEST_PATH . DIRECTORY_SEPARATOR . '_forTestFileSystem';

        if (!is_dir($this->targetTestDir)) {
            $umask = umask(0);
            $output = mkdir($this->targetTestDir, 0755, true);
            umask($umask);
        }

        $this->FileSystem = new \Rdb\System\Libraries\FileSystem($this->targetTestDir);
    }// setUp


    public function tearDown()
    {
        $this->FileSystem->deleteFolder('', true);
        @rmdir($this->targetTestDir);
    }// tearDown


    public function testCopyFolderRecursive()
    {
        // create source folders, files.
        $this->FileSystem->createFolder('subFolder1/1/1.1/1.1.1/1.1.1.1');
        $this->FileSystem->createFolder('subFolder1/1/1.2/1.2.1');
        $this->FileSystem->createFolder('subFolder1/1/1.2/1.2.2');
        $this->FileSystem->createFolder('subFolder1/1/1.3/1.3.1');
        $this->FileSystem->createFolder('subFolder1/2');
        $this->FileSystem->createFile('subFolder1/1/1.1/1.1.1/1.1.1.1/file.txt', 'bla bla');
        $this->FileSystem->createFile('subFolder1/1/1.1file.txt', 'bla bla');

        // copy recursive
        $this->assertTrue($this->FileSystem->copyFolderRecursive($this->targetTestDir . '/subFolder1', '../subFolder2'));
        $countSrc = count($this->FileSystem->listFilesSubFolders('subFolder1'));// 12
        $this->assertCount($countSrc, $this->FileSystem->listFilesSubFolders('subFolder2'));
        $this->assertCount(2, $this->FileSystem->trackCopy);// only 2 because it only count copy the file. the folder uses create.
        $this->assertCount(18, $this->FileSystem->trackCreate);
        unset($countSrc);
    }// testCopyFolderRecursive


    public function testCreateFile()
    {
        // this will be test `createFile()` which is alias of `writeFile()`
        $this->assertGreaterThanOrEqual(0, $this->FileSystem->createFile('example.txt', 'hello.'));
        $this->assertGreaterThanOrEqual(0, $this->FileSystem->writeFile('../example2.txt', 'hello.'));
        $createResult = $this->FileSystem->createFile('example3.txt', 'hello.');
        $this->assertTrue($createResult !== false);
        $this->assertCount(3, $this->FileSystem->trackCreate);
    }// testCreateFile


    public function testCreateFolder()
    {
        $this->assertTrue($this->FileSystem->createFolder('../subFolder'));
        $this->assertTrue($this->FileSystem->createFolder('../subFolder2'));
        $this->assertCount(2, $this->FileSystem->trackCreate);

        // create multiple folders
        for ($idir = 1; $idir <= 5; $idir++) {
            $this->FileSystem->createFolder('recursivetest/recur'.$idir.'/1/1.1/1.1.1/1.1.1.1');
            $this->FileSystem->createFolder('recursivetest/recur'.$idir.'/2');
            $this->FileSystem->createFolder('recursivetest/recur'.$idir.'/3');
            $this->FileSystem->createFolder('recursivetest/recur'.$idir.'/4');
            $this->FileSystem->createFile('recursivetest/recur'.$idir.'.txt', 'bla bla');
        }// endofor;
        unset($idir, $ifile);
        $this->assertCount(45, $this->FileSystem->listFilesSubFolders('recursivetest'));

        $this->assertCount(27, $this->FileSystem->trackCreate);// 27 is from (5 loop * 5 create on the loop) + 2 create on the top.
    }// testCreateFolder


    public function testDeleteFile()
    {
        $this->FileSystem->createFile('../example1.txt', 'hello.');
        $this->FileSystem->createFile('example2.txt', 'hello.');

        $this->assertTrue($this->FileSystem->deleteFile('example1.txt'));
        $this->assertTrue($this->FileSystem->deleteFile('../example2.txt'));
        $this->assertTrue($this->FileSystem->deleteFile('example2.txt'));// test deleted.
        $this->assertCount(2, $this->FileSystem->trackDeleted);
    }// testDeleteFile


    public function testDeleteFolder()
    {
        $this->FileSystem->createFolder('subFolder');
        $this->assertTrue($this->FileSystem->deleteFolder('../subFolder', true));

        for ($idir = 1; $idir <= 5; $idir++) {
            $this->FileSystem->createFolder('recursivetest/recur'.$idir.'/1/1.1/1.1.1/1.1.1.1');
            $this->FileSystem->createFolder('recursivetest/recur'.$idir.'/2');
            $this->FileSystem->createFolder('recursivetest/recur'.$idir.'/3');
            $this->FileSystem->createFolder('recursivetest/recur'.$idir.'/4');
            $this->FileSystem->createFile('recursivetest/recur'.$idir.'.txt', 'bla bla');
        }// endofor;
        unset($idir, $ifile);
        $this->assertCount(45, $this->FileSystem->listFilesSubFolders('recursivetest'));
        $this->assertTrue($this->FileSystem->deleteFolder('recursivetest'));// delete folders and files recursively but leave itself.
        $this->assertTrue(is_dir($this->targetTestDir.DIRECTORY_SEPARATOR.'recursivetest'));
        $this->assertTrue($this->FileSystem->deleteFolder('../recursivetest', true));// now delete folder itself.
        $this->assertTrue(!file_exists($this->targetTestDir.DIRECTORY_SEPARATOR.'recursivetest'));

        $this->assertCount(47, $this->FileSystem->trackDeleted);// 47 is from (45 list all files and folders which is deleted but not including self + 1 delete on the top + 1 delete itself).
    }// testDeleteFolder


    public function testGetTimestamp()
    {
        $this->FileSystem->createFile('test-timestamp.txt', 'hello world.');
        $timeResult = $this->FileSystem->getTimestamp('test-timestamp.txt');
        $this->assertTrue($timeResult !== false);
        $this->assertTrue(is_int($timeResult));
    }// testGetTimestamp


    public function testIsDir()
    {
        $this->FileSystem->createFolder('caseSenSitiveDir');
        $this->assertTrue($this->FileSystem->isDir('caseSenSitiveDir', true));
        $this->assertTrue($this->FileSystem->isDir('CaseSenSitiveDir', false));
        $this->assertTrue($this->FileSystem->isDir('caseSenSitiveDir', null));
        $this->assertFalse($this->FileSystem->isDir('CaseSenSitiveDir', true));
    }// testIsDir


    public function testIsFile()
    {
        $this->FileSystem->createFile('fileCaseSensitive.txt', 'blah blah');
        $this->assertTrue($this->FileSystem->isFile('fileCaseSensitive.txt', true));
        $this->assertTrue($this->FileSystem->isFile('FileCaseSensitive.txt', false));
        $this->assertTrue($this->FileSystem->isFile('fileCaseSensitive.txt', null));
        $this->assertFalse($this->FileSystem->isFile('FileCaseSensitive.txt', true));
    }// testIsFile


    public function testListFiles()
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->FileSystem->createFolder('test-list-dirs-files' . $i);
            $this->FileSystem->writeFile('test-list-dirs-files' . $i . '.txt', 'file number ' . $i);
        }
        $this->assertCount(10, $this->FileSystem->listFiles(''));
        $this->assertCount(5, $this->FileSystem->listFiles('', 'files'));
        $this->assertCount(5, $this->FileSystem->listFiles('', 'folders'));

        $folders = $this->FileSystem->listFiles('', 'folders');
        foreach ($folders as $folder) {
            $this->assertTrue($this->FileSystem->isDir($folder));
        }
    }// testListFiles


    public function testListFilesSubFolders()
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->FileSystem->createFolder('test-list-dirs-files' . $i);
        }
        $this->assertCount(5, $this->FileSystem->listFilesSubFolders(''));

        $this->FileSystem->createFolder('test-list-dirs-files-recursive/1/1.1/1.1.1');
        $this->assertCount(3, $this->FileSystem->listFilesSubFolders('test-list-dirs-files-recursive'));// actual 4 but list only sub content of this then it will be just 3.
        $this->FileSystem->createFile('test-list-dirs-files-recursive/test-list-file.txt', 'hello world.');
        $this->assertCount(4, $this->FileSystem->listFilesSubFolders('test-list-dirs-files-recursive'));// same as above (3) + 1 file.
    }// testListFilesSubFolders


    public function testTrackDeleted()
    {
        $this->FileSystem->createFolder('track-deleted-test1');
        $this->FileSystem->createFolder('track-deleted-test2/3/4');
        $this->FileSystem->deleteFolder('track-deleted-test1', true);
        $this->FileSystem->deleteFolder('track-deleted-test2', true);
        $this->assertCount(4, $this->FileSystem->trackDeleted);

        $this->FileSystem->trackDeleted = [];
        $this->assertCount(0, $this->FileSystem->trackDeleted);

        $this->FileSystem->createFolder('track-deleted-test1');
        $this->FileSystem->createFolder('track-deleted-test2/3/4');
        $this->FileSystem->createFile('track-deleted-test2/3/4/test-file1.txt', 'hello world.');
        $this->FileSystem->createFile('track-deleted-test2/3/4/test-file2.txt', 'hello world.');
        $this->FileSystem->deleteFolder('track-deleted-test1', true);
        $this->FileSystem->deleteFolder('track-deleted-test2', true);
        $this->assertCount(6, $this->FileSystem->trackDeleted);
    }// testTrackDeleted


}