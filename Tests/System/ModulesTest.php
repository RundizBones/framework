<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Tests\System;


class ModulesTest extends \Rdb\Tests\BaseTestCase
{


    /**
     * @var string
     */
    protected $backupComposerName;


    /**
     * @var \Rdb\System\Libraries\FileSystem
     */
    protected $FileSystem;


    /**
     * @var string New module name for create and tests.
     */
    protected $newModule = '';


    /**
     * @var string New module name (2) for create and tests.
     */
    protected $newModule2 = '';


    public function setup()
    {
        $this->newModule = 'ModuleForTest' . date('YmdHis') . mt_rand(1, 999) . 'M' . round(microtime(true) * 1000);
        $this->newModule2 = 'ModuleForTest2' . date('YmdHis') . mt_rand(1, 999) . 'M' . round(microtime(true) * 1000);

        $this->FileSystem = new \Rdb\System\Libraries\FileSystem(MODULE_PATH);
        $this->FileSystem->createFolder($this->newModule);
        $this->FileSystem->createFile($this->newModule . '/Installer.php', '<?php');

        $this->FileSystem->createFolder($this->newModule2);
        $this->FileSystem->createFolder($this->newModule2 . '/Controllers');
        $this->FileSystem->createFile(
            $this->newModule2 . '/Controllers/DataController.php', 
            '<?php' . "\n" .
            'namespace Rdb\\Modules\\' . $this->newModule2 . '\\Controllers; 
                class DataController extends \\Rdb\\System\\Core\\Controllers\\BaseController {
                    public function indexAction($name1 = \'\', $name2 = \'\') {
                        return \'Hello world \' . $name1 . \' \' . $name2 . \'.\';
                    }
                }
            '
        );

        // backup root composer.json
        $this->backupComposerName = 'composer_modulestests_backup' . time() . round(microtime(true) * 1000) . '.json';
        copy(ROOT_PATH . '/composer.json', ROOT_PATH . DIRECTORY_SEPARATOR . $this->backupComposerName);
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

        $this->FileSystem->deleteFolder($this->newModule, true);
        $this->FileSystem->deleteFolder($this->newModule2, true);
    }// tearDown


    public function testCopyComposer()
    {
        // create moduleComposer.json for test module.
        $this->FileSystem->createFile($this->newModule . '/moduleComposer.json', '
            {
                "require": {
                    "rundiz/serial-number-generator": "dev-master"
                }
            }
        ');

        $Modules = new \Rdb\System\Modules(new \Rdb\System\Container());
        $this->assertTrue($Modules->copyComposer($this->newModule));
        unset($Modules);
        $composerContents = file_get_contents(ROOT_PATH . '/composer.json');
        $this->assertContains('"rundiz/serial-number-generator"', $composerContents);// composer.json contain required item.
        unset($composerContents);
    }// testCopyComposer


    public function testCopyComposerAllModules()
    {
        // create moduleComposer.json for test module.
        $this->FileSystem->createFile($this->newModule . '/moduleComposer.json', '
            {
                "require": {
                    "rundiz/serial-number-generator": "dev-master"
                }
            }
        ');

        $Modules = new \Rdb\System\Modules(new \Rdb\System\Container());
        $Modules->registerAutoload();
        $copiedResult = $Modules->copyComposerAllModules();
        unset($Modules);
        $this->assertArrayHasKey('successCopied', $copiedResult);
        $this->assertGreaterThanOrEqual(1, $copiedResult['successCopied']);
        $this->assertGreaterThanOrEqual(1, $copiedResult['modulesWithComposer']);
        unset($Modules);
    }// testCopyComposerAllModules


    public function testExecute()
    {
        $Modules = new \Rdb\System\Modules(new \Rdb\System\Container());
        $this->assertFalse($Modules->exists($this->newModule2, true));// not yet register

        $Modules->registerAutoload();
        $this->assertTrue($Modules->exists($this->newModule2, true));// already registered
        $result = $Modules->execute('\\Rdb\\Modules\\' . $this->newModule2 . '\\Controllers\\Data:index', ['Thailand', 'Bangkok']);
        $this->assertEquals('Hello world Thailand Bangkok.', $result);
    }// testExecute


    public function testExists()
    {
        // test module just exists only, not strict for enabled modules.
        $Modules = new \Rdb\System\Modules(new \Rdb\System\Container());

        $this->assertFalse($Modules->exists($this->newModule));
        $this->assertTrue($Modules->exists($this->newModule, false));

        $this->assertFalse($Modules->exists($this->newModule));
        $this->assertTrue($Modules->exists($this->newModule2, false));

        unset($Modules);
    }// testExists


    public function testExists2()
    {
        // test module exists in enabled and registered modules.
        $Modules = new \Rdb\System\Modules(new \Rdb\System\Container());
        $Modules->registerAutoload();

        $this->assertTrue($Modules->exists($this->newModule));
        $this->assertTrue($Modules->exists($this->newModule, false));

        $this->assertTrue($Modules->exists($this->newModule2));
        $this->assertTrue($Modules->exists($this->newModule2, false));

        unset($Modules);
    }// testExists2


    public function testExists3()
    {
        // test module disabled. not exists in enabled modules but exists in file system.
        $this->FileSystem->createFile($this->newModule . '/.disabled', '');
        $Modules = new \Rdb\System\Modules(new \Rdb\System\Container());
        $Modules->registerAutoload();

        $this->assertFalse($Modules->exists($this->newModule));// not exists in enabled modules.
        $this->assertTrue($Modules->exists($this->newModule, false));// exists but don't care enabled or disabled.

        $this->assertTrue($Modules->exists($this->newModule2));
        $this->assertTrue($Modules->exists($this->newModule2, false));

        unset($Modules);
    }// testExists2


    public function testGetCurrentModule()
    {
        $Modules = new \Rdb\System\Modules(new \Rdb\System\Container());
        
        $Modules->setCurrentModule('\\Rdb\\System\\Core\\Controllers\\DefaultController');
        $this->assertEquals('Rdb\\System\\Core', $Modules->getCurrentModule());

        $Modules->setCurrentModule('\\Rdb\\Modules\\Rdb\\System\\Core\\Controllers\\DefaultController');
        $this->assertEquals('Rdb', $Modules->getCurrentModule());

        $Modules->setCurrentModule('\\Rdb\\Modules\\System\\Core\\Controllers\\DefaultController');
        $this->assertEquals('System', $Modules->getCurrentModule());

        $Modules->setCurrentModule('\\Rdb\\Modules\\Contact\\Controllers\\DefaultController');
        $this->assertEquals('Contact', $Modules->getCurrentModule());

        $Modules->setCurrentModule('\\Rdb\\Modules\\Contact\\Controllers\\Admin\\DefaultController');
        $this->assertEquals('Contact', $Modules->getCurrentModule());

        unset($Modules);
    }// testGetCurrentModule


    public function testGetModules()
    {
        $Modules = new \Rdb\System\Modules(new \Rdb\System\Container());
        $Modules->registerAutoload();

        $this->assertTrue(in_array($this->newModule, $Modules->getModules()));// found in enabled modules only.
        $this->assertTrue(in_array($this->newModule, $Modules->getModules(false)));// found in modules that is not strict enabled only.

        unset($Modules);
    }// testGetModules


    public function testGetModules2()
    {
        $this->FileSystem->createFile($this->newModule . '/.disabled', '');// add .disabled file.

        $Modules = new \Rdb\System\Modules(new \Rdb\System\Container());
        $Modules->registerAutoload();

        $this->assertFalse(in_array($this->newModule, $Modules->getModules()));// not found in enabled modules only.
        $this->assertTrue(in_array($this->newModule, $Modules->getModules(false)));// found in modules that is not strict enabled only.

        unset($Modules);
    }// testGetModules2


    public function testGetModuleSystemName()
    {
        $Modules = new \Rdb\System\Modules(new \Rdb\System\Container());

        $this->assertEquals('SystemCore', $Modules->getModuleSystemName(''));
        $this->assertEquals($this->newModule, $Modules->getModuleSystemName(MODULE_PATH . DIRECTORY_SEPARATOR . $this->newModule));
        $this->assertEquals('PathToModule', $Modules->getModuleSystemName(MODULE_PATH . DIRECTORY_SEPARATOR . 'PathToModule'));
    }// testGetModuleSystemName


}
