<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Tests\Rdb\System\Core\Console;


use \Symfony\Component\Console\Application;
use \Symfony\Component\Console\Tester\CommandTester;


class ModuleTest extends \Tests\Rdb\BaseTestCase
{


    /**
     * @var string
     */
    protected $backupComposerName;


    /**
     * @var \System\Libraries\FileSystem
     */
    protected $FileSystem;


    /**
     * @var string Path to target test folder without trailing slash.
     */
    protected $targetTestDir;


    /**
     * @var string Test module system name.
     */
    protected $testModuleName;


    public function setup()
    {
        if (!defined('NOREMOVEDIRBYSHELL')) {
            // define NOREMOVEDIRBYSHELL constant to not running RMDIR command and get "cannot find the path" error message even it exists.
            define('NOREMOVEDIRBYSHELL', true);
        }

        $this->testModuleName = 'TestCli' . date('YmdHis') . mt_rand(1, 999) . round(microtime(true) * 1000);
        $this->targetTestDir = MODULE_PATH . DIRECTORY_SEPARATOR . $this->testModuleName;

        // create test module folder.
        if (!is_dir($this->targetTestDir)) {
            $umask = umask(0);
            $output = mkdir($this->targetTestDir, 0755, true);
            umask($umask);
        }

        // create folders and files for this test module.
        $this->FileSystem = new \System\Libraries\FileSystem($this->targetTestDir);
        // create assets for test copy to public folder.
        $this->FileSystem->createFolder('assets/css');
        $this->FileSystem->createFile('assets/css/style.css', '.unknown' . time() . '{background: #fff; color: #333;}');
        // create installer for test install, uninstall, update.
        $this->FileSystem->createFile('Installer.php', '<?php
            namespace Modules\\' . $this->testModuleName . ';

            class Installer implements \\System\\Interfaces\\ModuleInstaller
            {
                /**
                 * @var \System\Container
                 */
                protected $Container;

                public function __construct(\System\Container $Container)
                {
                    $this->Container = $Container;
                }

                public function install()
                {
                    file_put_contents(__DIR__ . \'/installed.txt\', \'Installed on: \' . date(\'Y-m-d H:i:s\'));
                }

                public function uninstall()
                {
                    $umask = umask(0);
                    mkdir(STORAGE_PATH . \'/tests/tests-module-installer/' . $this->testModuleName . '\', 0755, true);
                    umask($umask);
                    file_put_contents(STORAGE_PATH . \'/tests/tests-module-installer/' . $this->testModuleName . '/uninstalled.txt\', date(\'Y-m-d H:i:s\'), FILE_APPEND);
                }

                public function update()
                {
                    file_put_contents(__DIR__ . \'/installed.txt\', \'Updated on: \' . date(\'Y-m-d H:i:s\'), FILE_APPEND);
                }
            }
        ');
        // create moduleComposer.json for test add dependency to main app's composer.json.
        $this->FileSystem->createFile('moduleComposer.json', '
            {
                "require": {
                    "vendor/some-dep-not-exists": "^22"
                },
                "require-dev": {
                    "vendor/some-other-dep-not-exists": "dev-master"
                }
            }
        ');

        // backup main app's composer.json
        $this->backupComposerName = 'composer_modulecli_tests_backup' . time() . round(microtime(true) * 1000) . '.json';
        copy(ROOT_PATH . '/composer.json', ROOT_PATH . DIRECTORY_SEPARATOR . $this->backupComposerName);

        // start the framework to load enabled modules.
        $this->RdbApp = new \Tests\Rdb\System\AppExtended();
        $this->RdbApp->addDependencyInjection();
        $this->Container = $this->RdbApp->getContainer();
    }// setup


    public function tearDown()
    {
        // restore root composer.json
        $deleteResult = @unlink(ROOT_PATH . '/composer.json');
        if ($deleteResult === false && is_file(ROOT_PATH . '/composer.json')) {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                shell_exec('del /f ' . ROOT_PATH . '/composer.json');
            } else {
                shell_exec('rm -f ' . ROOT_PATH . '/composer.json');
            }
        }
        copy(ROOT_PATH . DIRECTORY_SEPARATOR . $this->backupComposerName, ROOT_PATH . '/composer.json');
        // delete the backup one.
        unlink(ROOT_PATH . DIRECTORY_SEPARATOR . $this->backupComposerName);

        // delete test module folder.
        $this->FileSystem->deleteFolder('', true);
        @rmdir($this->targetTestDir);
        $this->FileSystem = null;
        if (is_dir($this->targetTestDir)) {
            // make very sure that test module will be deleted.
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // if on windows.
                shell_exec('RMDIR /Q/S ' . MODULE_PATH . DIRECTORY_SEPARATOR . $mname . ' > /dev/null 2>&1 &');
            } else {
                shell_exec('rm -r ' . MODULE_PATH . DIRECTORY_SEPARATOR . $mname . ' > /dev/null 2>&1 &');
            }
        }

        // delete copied test folders in public
        $this->FileSystem = new \System\Libraries\FileSystem(PUBLIC_PATH . DIRECTORY_SEPARATOR . 'Modules');
        $this->FileSystem->deleteFolder($this->testModuleName, true);
        $this->FileSystem = null;
        if (
            is_dir(PUBLIC_PATH . DIRECTORY_SEPARATOR . 'Modules') &&
            !(new \FilesystemIterator(PUBLIC_PATH . DIRECTORY_SEPARATOR . 'Modules'))->valid()
        ) {
            @rmdir(PUBLIC_PATH . DIRECTORY_SEPARATOR . 'Modules');
        }

        // delete created file in storage.
        $this->FileSystem = new \System\Libraries\FileSystem(STORAGE_PATH);
        $this->FileSystem->deleteFolder('tests', true);
        $this->FileSystem = null;
    }// tearDown


    public function testExecuteDisable()
    {
        $application = new Application();
        $application->add(new \System\Core\Console\Module(null, $this->Container));
        $command = $application->find('system:module');
        unset($application);
        $commandTester = new CommandTester($command);

        // test disable
        $commandTester->execute([
            'command'  => $command->getName(),
            // pass arguments to the helper
            'act' => 'disable',
            // prefix the key with two dashes (--) when passing options,
            '--mname' => $this->testModuleName,
        ]);
        $output = $commandTester->getDisplay();
        $this->assertContains('Success', $output);
        $this->assertContains('disabled', $output);
        $this->assertTrue($this->FileSystem->isFile('.disabled'));// module disabled.

        unset($command, $commandTester, $output);
    }// testExecuteDisable


    public function testExecuteEnable()
    {
        $application = new Application();
        $application->add(new \System\Core\Console\Module(null, $this->Container));
        $command = $application->find('system:module');
        unset($application);
        $commandTester = new CommandTester($command);

        // test enable
        $commandTester->execute([
            'command'  => $command->getName(),
            // pass arguments to the helper
            'act' => 'enable',
            // prefix the key with two dashes (--) when passing options,
            '--mname' => $this->testModuleName,
        ]);
        $output = $commandTester->getDisplay();
        $this->assertContains('Success', $output);
        $this->assertContains('enabled', $output);
        $this->assertFalse($this->FileSystem->isFile('.disabled'));// module enabled.

        unset($command, $commandTester, $output);
    }// testExecuteEnable


    public function testExecuteInstall()
    {
        $application = new Application();
        $application->add(new \System\Core\Console\Module(null, $this->Container));
        $command = $application->find('system:module');
        unset($application);
        $commandTester = new CommandTester($command);

        // test install
        $commandTester->execute([
            'command'  => $command->getName(),
            // pass arguments to the helper
            'act' => 'install',
            // prefix the key with two dashes (--) when passing options,
            '--mname' => $this->testModuleName,
        ]);
        $output = $commandTester->getDisplay();
        $this->assertContains('Success', $output);
        $this->assertContains('installed', $output);
        $this->assertFalse($this->FileSystem->isFile('.disabled'));// module enabled.
        $this->assertTrue($this->FileSystem->isFile('installed.txt'));// installer class create installed.txt file.
        $this->assertTrue(is_dir(PUBLIC_PATH . '/Modules/' . $this->testModuleName . '/assets'));// assets folder copied to public.
        $this->assertContains('"vendor/some-dep-not-exists"', file_get_contents(ROOT_PATH . '/composer.json'));// composer.json contain required item.

        unset($command, $commandTester, $output);
    }// testExecuteInstall


    public function testExecuteUninstall()
    {
        $application = new Application();
        $application->add(new \System\Core\Console\Module(null, $this->Container));
        $command = $application->find('system:module');
        unset($application);
        $commandTester = new CommandTester($command);

        // install it first.
        $commandTester->execute([
            'command'  => $command->getName(),
            // pass arguments to the helper
            'act' => 'install',
            // prefix the key with two dashes (--) when passing options,
            '--mname' => $this->testModuleName,
        ]);

        // then test uninstall
        $commandTester->setInputs(['y']);// confirm question (y, n) answer y.
        $commandTester->execute([
            'command'  => $command->getName(),
            // pass arguments to the helper
            'act' => 'uninstall',
            // prefix the key with two dashes (--) when passing options,
            '--mname' => $this->testModuleName,
        ]);
        $output = $commandTester->getDisplay();
        $this->assertContains('Success', $output);
        $this->assertContains('uninstalled', $output);
        $this->assertContains('composer', $output);
        $this->assertFalse($this->FileSystem->isDir(''));// module folder already deleted.
        $this->assertFalse(is_dir(PUBLIC_PATH . '/Modules/' . $this->testModuleName . '/assets'));// assets folder deleted from public.
        $this->assertFalse(stripos(file_get_contents(ROOT_PATH . '/composer.json'), '"vendor/some-dep-not-exists"'));// composer.json does not contain required item.
        $this->assertTrue(is_file(STORAGE_PATH . '/tests/tests-module-installer/' . $this->testModuleName . '/uninstalled.txt'));// there is uninstall log.
        $this->assertContains(date('Y-m-d'), file_get_contents(STORAGE_PATH . '/tests/tests-module-installer/' . $this->testModuleName . '/uninstalled.txt'));// uninstall log contain today date.

        unset($command, $commandTester, $output);
    }// testExecuteUninstall


    public function testExecuteUninstall2()
    {
        // test uninstall but no delete.
        $application = new Application();
        $application->add(new \System\Core\Console\Module(null, $this->Container));
        $command = $application->find('system:module');
        unset($application);
        $commandTester = new CommandTester($command);

        // install it first.
        $commandTester->execute([
            'command'  => $command->getName(),
            // pass arguments to the helper
            'act' => 'install',
            // prefix the key with two dashes (--) when passing options,
            '--mname' => $this->testModuleName,
        ]);

        // then test uninstall but no delete
        $commandTester->setInputs(['y']);// confirm question (y, n) answer y.
        $commandTester->execute([
            'command'  => $command->getName(),
            // pass arguments to the helper
            'act' => 'uninstall',
            // prefix the key with two dashes (--) when passing options,
            '--mname' => $this->testModuleName,
            '--nodelete' => null,
        ]);
        $output = $commandTester->getDisplay();
        $this->assertContains('Success', $output);
        $this->assertContains('uninstalled', $output);
        $this->assertContains('composer', $output);
        $this->assertTrue($this->FileSystem->isDir(''));// module folder NOT deleted.
        $this->assertFalse(is_dir(PUBLIC_PATH . '/Modules/' . $this->testModuleName . '/assets'));// assets folder deleted from public.
        $this->assertFalse(stripos(file_get_contents(ROOT_PATH . '/composer.json'), '"vendor/some-dep-not-exists"'));// composer.json does not contain required item.
        $this->assertTrue(is_file(STORAGE_PATH . '/tests/tests-module-installer/' . $this->testModuleName . '/uninstalled.txt'));// there is uninstall log.
        $this->assertContains(date('Y-m-d'), file_get_contents(STORAGE_PATH . '/tests/tests-module-installer/' . $this->testModuleName . '/uninstalled.txt'));// uninstall log contain today date.

        unset($command, $commandTester, $output);
    }// testExecuteUninstall2


    public function testExecuteUpdate()
    {
        $application = new Application();
        $application->add(new \System\Core\Console\Module(null, $this->Container));
        $command = $application->find('system:module');
        unset($application);
        $commandTester = new CommandTester($command);

        // test update
        $commandTester->execute([
            'command'  => $command->getName(),
            // pass arguments to the helper
            'act' => 'update',
            // prefix the key with two dashes (--) when passing options,
            '--mname' => $this->testModuleName,
        ]);
        $output = $commandTester->getDisplay();
        $this->assertContains('Success', $output);
        $this->assertContains('updated', $output);
        $this->assertFalse($this->FileSystem->isFile('.disabled'));// module enabled.
        $this->assertTrue($this->FileSystem->isFile('installed.txt'));// installer class create installed.txt file.
        $this->assertContains('Updated', file_get_contents($this->targetTestDir . '/installed.txt'));// contain updated txt.
        $this->assertTrue(is_dir(PUBLIC_PATH . '/Modules/' . $this->testModuleName . '/assets'));// assets folder copied to public.
        $this->assertContains('"vendor/some-dep-not-exists"', file_get_contents(ROOT_PATH . '/composer.json'));// composer.json contain required item.

        unset($command, $commandTester, $output);
    }// testExecuteUpdate


}
