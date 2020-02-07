<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\System;


/**
 * CLI class.
 * 
 * @since 0.1
 */
class Cli
{


    /**
     * @var \Symfony\Component\Console\Application
     */
    protected $CliApp;


    /**
     * @var \Rdb\System\Container
     */
    protected $Container;


    /**
     * Class constructor.
     * 
     * @param \Rdb\System\Container $Container The DI container class.
     */
    public function __construct(Container $Container)
    {
        $this->Container = $Container;
    }// __construct


    /**
     * Automatic get all available commands in Rdb\System\Core\Console and Rdb\Modules\[ModuleName]\Console
     */
    protected function getAvailableCommands()
    {
        $ReflectionClassTargetInstance = new \ReflectionClass('\\Symfony\\Component\\Console\\Command\\Command');

        // add commands in Rdb\System\Core\Console\*
        $systemConsoleFolder = ROOT_PATH . DIRECTORY_SEPARATOR . 'System' . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'Console';
        if (is_dir($systemConsoleFolder)) {
            $RecurItIt = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $systemConsoleFolder, 
                    \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO
                ), 
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            if (is_array($RecurItIt) || is_object($RecurItIt)) {
                foreach ($RecurItIt as $filePath => $object) {
                    if (is_file($filePath) && strpos($filePath, 'Core'.DIRECTORY_SEPARATOR.'Console'.DIRECTORY_SEPARATOR.'BaseConsole.php') === false) {
                        $pathToClass = str_replace([ROOT_PATH, '.php', '/'], ['', '', '\\'], $filePath);// from /Path/To/Class.php => \Path\To\Class
                        if (mb_substr($pathToClass, 0, 1) === '\\') {
                            $pathToClass = 'Rdb' . $pathToClass;
                        } else {
                            $pathToClass = 'Rdb\\' . $pathToClass;
                        }

                        if (class_exists($pathToClass)) {
                            $ReflectionCommand = new \ReflectionClass($pathToClass);
                            $commandInstance = $ReflectionCommand->newInstanceWithoutConstructor();

                            if (
                                $ReflectionClassTargetInstance->isInstance($commandInstance) &&
                                $ReflectionCommand->hasMethod('configure') &&
                                $ReflectionCommand->hasMethod('execute')
                            ) {
                                $this->CliApp->add($ReflectionCommand->newInstance(null, $this->Container));
                            } elseif (
                                $ReflectionCommand->hasMethod('IncludeExternalCommands')
                            ) {
                                $CommandClass = $ReflectionCommand->newInstance($this->Container);
                                $CommandClass->IncludeExternalCommands($this->CliApp);
                            }
                        }
                        unset($commandInstance, $pathToClass, $ReflectionCommand);
                    }
                }// endforeach;
                unset($filePath, $object);
            }
            unset($RecurItIt);
        }// endif check that Rdb\System\Core\Console is folder.
        unset($systemConsoleFolder);
        
        // add commands in Rdb\Modules\[ModuleName]\Console\*
        // the module's console name should start with namespace `modules:`.
        // example: `$this->setName('modules:modulename:whatever');`.
        if ($this->Container->has('Modules')) {
            /* @var $Modules \Rdb\System\Modules */
            $Modules = $this->Container->get('Modules');
            $enabledModules = $Modules->getModules();

            if (is_array($enabledModules)) {
                foreach ($enabledModules as $moduleSystemName) {
                    $moduleConsoleFolder = MODULE_PATH . DIRECTORY_SEPARATOR . $moduleSystemName . DIRECTORY_SEPARATOR . 'Console';

                    if (is_dir($moduleConsoleFolder)) {
                        $RecurItIt = new \RecursiveIteratorIterator(
                            new \RecursiveDirectoryIterator(
                                $moduleConsoleFolder, 
                                \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO
                            ), 
                            \RecursiveIteratorIterator::CHILD_FIRST
                        );

                        if (is_array($RecurItIt) || is_object($RecurItIt)) {
                            foreach ($RecurItIt as $filePath => $object) {
                                if (is_file($filePath)) {
                                    $pathToClass = str_replace([ROOT_PATH, '.php', '/'], ['', '', '\\'], $filePath);// from /Path/To/Class.php => \Path\To\Class
                                    if (mb_substr($pathToClass, 0, 1) === '\\') {
                                        $pathToClass = 'Rdb' . $pathToClass;
                                    } else {
                                        $pathToClass = 'Rdb\\' . $pathToClass;
                                    }

                                    if (class_exists($pathToClass)) {
                                        $ReflectionCommand = new \ReflectionClass($pathToClass);
                                        $commandInstance = $ReflectionCommand->newInstanceWithoutConstructor();

                                        if (
                                            $ReflectionClassTargetInstance->isInstance($commandInstance) &&
                                            $ReflectionCommand->hasMethod('configure') &&
                                            $ReflectionCommand->hasMethod('execute')
                                        ) {
                                            $this->CliApp->add($ReflectionCommand->newInstance(null, $this->Container));
                                        } elseif (
                                            $ReflectionCommand->hasMethod('IncludeExternalCommands')
                                        ) {
                                            $CommandClass = $ReflectionCommand->newInstance($this->Container);
                                            $CommandClass->IncludeExternalCommands($this->CliApp);
                                        }
                                    }// endif module console class exists.
                                    unset($commandInstance, $pathToClass, $ReflectionCommand);
                                }
                            }// endforeach;
                            unset($filePath, $object);
                        }
                        unset($RecurItIt);
                    }// endif; check that Rdb\Modules\[module]\Console is folder.
                    unset($moduleConsoleFolder);
                }// endforeach;
                unset($moduleSystemName);
            }// endif; $enabledModules
            unset($enabledModules, $Modules);
        }

        unset($ReflectionClassTargetInstance);
    }// getAvailableCommands


    /**
     * Run the application in command line.
     */
    public function run()
    {
        if (strtolower(PHP_SAPI) !== 'cli') {
            echo 'This class should be run in the command line (or terminal) only.';
            exit();
        }

        if (
            (!is_callable('exec') || stripos(ini_get('disable_functions'), 'exec') !== false) &&
            (!is_callable('shell_exec') || stripos(ini_get('disable_functions'), 'shell_exec') !== false)
        ) {
            echo 'You are running command line on the server that is not allowed.'."\n";
            echo 'Please make sure that "exec" or "shell_exec" functions was not disabled or try to run this app on your local server instead.';
            exit();
        }

        // begins Symfony Console app
        // this framework required PHP 7.0 so, it is required at least symfony console v3.3.6.
        // this is the document for v3.3 https://symfony.com/doc/3.3/components/console.html
        $this->CliApp = new \Symfony\Component\Console\Application('RundizBones');
        $this->getAvailableCommands();
        $this->CliApp->run();
    }// run


}
