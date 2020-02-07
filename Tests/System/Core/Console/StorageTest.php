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


    public function setup()
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


    public function tearDown()
    {
        $this->FileSystem->deleteFolder('', true);
        $this->FileSystem = new \Rdb\System\Libraries\FileSystem(STORAGE_PATH);
        $this->FileSystem->deleteFolder('tests', true);
        @rmdir($this->targetTestDir);
    }// tearDown


    public function testExecuteClear()
    {
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
        $this->assertContains(DIRECTORY_SEPARATOR . '_testConsole', $output);
        $this->assertContains('logs', $output);
        $this->assertContains('logs2', $output);
        $this->assertContains('test-01.log', $output);

        unset($command, $commandTester, $output);
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
        $this->assertContains(DIRECTORY_SEPARATOR . '_testConsole', $output);
        $this->assertContains('logs', $output);

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
        $this->assertContains(DIRECTORY_SEPARATOR . '_testConsole', $output);
        $this->assertContains('logs', $output);
        $this->assertContains('logs2', $output);

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
        $this->assertContains(DIRECTORY_SEPARATOR . '_testConsole', $output);
        $this->assertContains('logs', $output);

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
        $this->assertContains(DIRECTORY_SEPARATOR . '_testConsole', $output);
        $this->assertContains('logs', $output);
        $this->assertContains('logs2', $output);

        unset($command, $commandTester, $output);
    }// testExecuteList


}
