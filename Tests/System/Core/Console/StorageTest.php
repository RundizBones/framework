<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Tests\System\Core\Console;


use \Symfony\Component\Console\Application;
use \Symfony\Component\Console\Tester\CommandTester;


class StorageTest extends \Rdb\Tests\BaseTestCase
{


    /**
     * @var \Rdb\System\Libraries\FileSystem
     */
    protected $FileSystem;


    /**
     * @var string Path to target test folder without trailing slash.
     */
    protected $targetTestDir;


    /**
     * @var string Folder name for tests, related to storage path.
     */
    protected $testSubfolder;


    public function setup(): void
    {
        $this->testSubfolder = 'tests' . DIRECTORY_SEPARATOR . '_testConsole' . date('YmdHis') . '_' . mt_rand(1, 999) . round(microtime(true) * 1000);
        $this->targetTestDir = STORAGE_PATH . DIRECTORY_SEPARATOR . $this->testSubfolder;

        if (!is_dir($this->targetTestDir)) {
            $umask = umask(0);
            $output = mkdir($this->targetTestDir, 0755, true);
            umask($umask);
        }

        $this->FileSystem = new \Rdb\System\Libraries\FileSystem($this->targetTestDir);
        $this->FileSystem->createFile('readme.txt', 'You can delete this folder if it exists. It is for test only. This folder was created from ' . __FILE__);
        $this->FileSystem->createFolder('logs');
        $this->FileSystem->createFile('logs/test-01.log', 'Hello.');
        $this->FileSystem->createFile('logs/test-02.log', 'Hello.');
        $this->FileSystem->createFolder('logs2');
        $this->FileSystem->createFile('logs2/test-01.log', 'Hello.');
        $this->FileSystem->createFile('logs2/test-02.log', 'Hello.');
    }// setup


    public function tearDown(): void
    {
        $this->FileSystem->deleteFolder('', true);
        $this->FileSystem = new \Rdb\System\Libraries\FileSystem(STORAGE_PATH);
        $this->FileSystem->deleteFolder('tests', true);
        @rmdir($this->targetTestDir);
    }// tearDown


    public function testExecuteClear()
    {
        // create folders and files for test `clear` command that is limited to cache, logs folders.
        $FileSystem = new \Rdb\System\Libraries\FileSystem();
        $FileSystem->createFolder('cache/_testConsole');
        $FileSystem->createFile('cache/_testConsole/test.txt', 'hello world');
        $FileSystem->createFolder('logs/logs2');
        $FileSystem->createFile('logs/logs2/test-01.log', 'Hello.');

        $application = new Application();
        $application->add(new \Rdb\System\Core\Console\Storage());
        $command = $application->find('system:storage');
        unset($application);
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['y']);// confirm question (y, n) answer y.

        $commandTester->execute([
            'command'  => $command->getName(),
            // pass arguments to the helper
            'act' => 'clear',
        ]);
        // the output of the command in the console
        $output = $commandTester->getDisplay();
        // the result has changed, they can be line wrap. So, use regexp to test.
        $this->assertRegExp('/' . preg_quote(DIRECTORY_SEPARATOR, '/') . '([ \|\s]*)_([a-zA-Z\|\s]{2,})e/', $output);// assert \(any space, |)_(a-z, |, any space more than 2 chars)e == \_testConsole
        //$this->assertStringContainsString(DIRECTORY_SEPARATOR . '_testConsole', $output);// previous test.
        $this->assertRegExp('/l([a-zA-Z\|\s]{2,})s/', $output);// logs
        $this->assertRegExp('/l([a-zA-Z\|\s]{2,})s([ \|\s]*)2/', $output);// logs2
        $this->assertRegExp('/t([a-zA-Z\|\s]{2,})t([ \|\s]*)\-([ \|\s]*)0([ \|\s]*)1([a-zA-Z\.\|\s]{2,})g/', $output);// test-01.log
        $this->assertTrue($FileSystem->isDir('tests'));// tests folder must not deleted.

        unset($command, $commandTester, $FileSystem, $output);
    }// testExecuteClear


    public function testExecuteDelete()
    {
        $application = new Application();
        $application->add(new \Rdb\System\Core\Console\Storage());
        $command = $application->find('system:storage');
        unset($application);
        $commandTester = new CommandTester($command);

        // test delete using glob.
        $commandTester->execute([
            'command'  => $command->getName(),
            // pass arguments to the helper
            'act' => 'delete',
            // prefix the key with two dashes (--) when passing options,
            '--subfolder' => $this->testSubfolder . '/logs/test*',
        ]);
        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertRegExp('/' . preg_quote(DIRECTORY_SEPARATOR, '/') . '([ \|\s]*)_([a-zA-Z\|\s]{2,})e/', $output);// assert \(any space, |)_(a-z, |, any space more than 2 chars)e == \_testConsole
        $this->assertRegExp('/l([a-zA-Z\|\s]{2,})s/', $output);// logs

        // test delete using normal folder name.
        $commandTester->execute([
            'command'  => $command->getName(),
            // pass arguments to the helper
            'act' => 'delete',
            // prefix the key with two dashes (--) when passing options,
            '--subfolder' => $this->testSubfolder,
        ]);
        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertRegExp('/' . preg_quote(DIRECTORY_SEPARATOR, '/') . '([ \|\s]*)_([a-zA-Z\|\s]{2,})e/', $output);// assert \(any space, |)_(a-z, |, any space more than 2 chars)e == \_testConsole
        $this->assertRegExp('/l([a-zA-Z\|\s]{2,})s/', $output);// logs
        $this->assertRegExp('/l([a-zA-Z\|\s]{2,})s([ \|\s]*)2/', $output);// logs2

        unset($command, $commandTester, $output);
    }// testExecuteDelete


    public function testExecuteList()
    {
        $application = new Application();
        $application->add(new \Rdb\System\Core\Console\Storage());
        $command = $application->find('system:storage');
        unset($application);
        $commandTester = new CommandTester($command);

        // test list using glob.
        $commandTester->execute([
            'command'  => $command->getName(),
            // pass arguments to the helper
            'act' => 'list',
            // prefix the key with two dashes (--) when passing options,
            '--subfolder' => $this->testSubfolder . '/logs/test*',
        ]);
        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertRegExp('/' . preg_quote(DIRECTORY_SEPARATOR, '/') . '([ \|\s]*)_([a-zA-Z\|\s]{2,})e/', $output);// assert \(any space, |)_(a-z, |, any space more than 2 chars)e == \_testConsole
        $this->assertRegExp('/l([a-zA-Z\|\s]{2,})s/', $output);// logs

        // test list using normal subfolder name.
        $commandTester->execute([
            'command'  => $command->getName(),
            // pass arguments to the helper
            'act' => 'list',
            // prefix the key with two dashes (--) when passing options,
            '--subfolder' => $this->testSubfolder,
        ]);
        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertRegExp('/' . preg_quote(DIRECTORY_SEPARATOR, '/') . '([ \|\s]*)_([a-zA-Z\|\s]{2,})e/', $output);// assert \(any space, |)_(a-z, |, any space more than 2 chars)e == \_testConsole
        $this->assertRegExp('/l([a-zA-Z\|\s]{2,})s/', $output);// logs
        $this->assertRegExp('/l([a-zA-Z\|\s]{2,})s([ \|\s]*)2/', $output);// logs2

        unset($command, $commandTester, $output);
    }// testExecuteList


}
