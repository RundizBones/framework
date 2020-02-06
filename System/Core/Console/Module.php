<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace System\Core\Console;


use \Symfony\Component\Console\Formatter\OutputFormatterStyle;
use \Symfony\Component\Console\Input\ArrayInput;
use \Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\NullOutput;
use \Symfony\Component\Console\Output\OutputInterface;
use \Symfony\Component\Console\Question\ConfirmationQuestion;
use \Symfony\Component\Console\Style\SymfonyStyle;


/**
 * Module CLI.
 * 
 * @since 0.1
 */
class Module extends BaseConsole
{


    /**
     * @var \System\Modules
     */
    protected $Modules;


    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('system:module')
            ->setDescription('Manage a module.')
            ->setHelp(
                'Manage a module such as install, uninstall, update, enable, disable.'."\n\n".
                '[Install a module]' . "\n" .
                '  To install a module, use install action and follow with --mname option. The --mname is module system name (folder name, case sensitive) that you want to work with.' . "\n" .
                '  This will be copy "assets" folder on your module to "public/Modules/[your module]/assets".' . "\n" .
                '  This will be also copy composer dependency to main app\'s composer.json.' . "\n" .
                '  If your module contain composer dependency then you have to run the "composer update" command again.' . "\n" .
                '  The installed module will be enabled automatically.' . "\n\n" .
                '  Example: ' . "\n" .
                '    system:module install --mname="Contact"' . "\n" .
                '      This will install the "Contact" module.' . "\n\n" .
                '[Enable a module]' . "\n" .
                '  To enable a module, use enable action and follow with --mname option. The --mname is module system name (folder name, case sensitive) that you want to work with.' . "\n\n" .
                '  Example: ' . "\n" .
                '    system:module enable --mname="Contact"' . "\n" .
                '      This will enable the "Contact" module.' . "\n\n" .
                '[Disable a module]' . "\n" .
                '  To disable a module, use disable action and follow with --mname option. The --mname is module system name (folder name, case sensitive) that you want to work with.' . "\n\n" .
                '  Example: ' . "\n" .
                '    system:module disable --mname="Contact"' . "\n" .
                '      This will disable the "Contact" module.' . "\n\n" .
                '[Update a module]' . "\n" .
                '  To update a module, use update action and follow with --mname option. The --mname is module system name (folder name, case sensitive) that you want to work with.' . "\n" .
                '  This will be update "assets" folder on your module to "public/Modules/[your module]/assets".' . "\n" .
                '  This will be also update composer dependency to main app\'s composer.json.' . "\n" .
                '  If your module contain composer dependency then you have to run the "composer update" command again.' . "\n\n" .
                '  Example: ' . "\n" .
                '    system:module update --mname="Contact"' . "\n" .
                '      This will update the "Contact" module.' . "\n\n" .
                '[Uninstall a module]' . "\n" .
                '  To uninstall a module, use uninstall action and follow with --mname option. The --mname is module system name (folder name, case sensitive) that you want to work with.' . "\n" .
                '  This will be remove module folder from public folder. Example: "public/Modules/[your module]".' . "\n" .
                '  This will be also remove your module\'s composer dependency from main app\'s composer.json.' . "\n" .
                '  If your module contain composer dependency then you have to run the "composer update" command again.' . "\n" .
                '  Your module will be deleted from Modules folder.' . "\n" .
                '  To not delete the module from Modules folder, just add --nodelete option.' . "\n\n" .
                '  Example: ' . "\n" .
                '    system:module uninstall --mname="Contact"' . "\n" .
                '      This will uninstall the "Contact" module.' . "\n" .
                '    system:module uninstall --mname="Contact" --nodelete' . "\n" .
                '      This will call the uninstall method in the "Contact" module and then disable it without delete.' . "\n\n"
            )
            ->addArgument('act', InputArgument::REQUIRED, 'Action to do (install, uninstall, update, enable, disable)')
            ->addOption('mname', 'm', InputOption::VALUE_REQUIRED, 'The module system name (folder name) that you want to work with. This is case sensitive.')
            ->addOption('nodelete', null, InputOption::VALUE_NONE, 'Add this option on uninstall to not delete the module and the module will be disabled.');
    }// configure


    /**
     * Display success message with additional message (if exists).
     * 
     * @param SymfonyStyle $Io The output style class.
     * @param string $successMessage The success message. This is the main message.
     * @param array $additionalMessages Additional messages in associative array.
     *                                                          `resultMessage` is normal additional message. This can be string or array of messages.
     *                                                          `warningMessage` is warning message. This can be string or array of messages.
     */
    private function displaySuccess(SymfonyStyle $Io, string $successMessage, array $additionalMessages = [])
    {
        $Io->success($successMessage);

        if (isset($additionalMessages['resultMessage'])) {
            if (is_scalar($additionalMessages['resultMessage'])) {
                $Io->writeln($additionalMessages['resultMessage']);
            } elseif (is_array($additionalMessages['resultMessage'])) {
                foreach ($additionalMessages['resultMessage'] as $message) {
                    if (is_scalar($message)) {
                        $Io->writeln($message);
                    }
                }// endforeach;
                unset($message);
            }
        }

        if (isset($additionalMessages['warningMessage'])) {
            if (is_scalar($additionalMessages['warningMessage'])) {
                $Io->warning($additionalMessages['warningMessage']);
            } elseif (is_array($additionalMessages['warningMessage'])) {
                foreach ($additionalMessages['warningMessage'] as $message) {
                    if (is_scalar($message)) {
                        $Io->warning($message);
                    }
                }// endforeach;
                unset($message);
            }
        }
    }// displaySuccess


    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $Input, OutputInterface $Output)
    {
        $Io = new SymfonyStyle($Input, $Output);

        if ($this->Container->has('Modules')) {
            $this->Modules = $this->Container->get('Modules');
        } else {
            throw new \OutOfBoundsException('Unable to get the Modules class. This application may load incorrectly.');
        }

        if ($Input->getArgument('act') === 'disable') {
            $this->executeDisable($Input, $Output);
        } elseif ($Input->getArgument('act') === 'enable') {
            $this->executeEnable($Input, $Output);
        } elseif ($Input->getArgument('act') === 'install') {
            $this->executeInstall($Input, $Output);
        } elseif ($Input->getArgument('act') === 'uninstall') {
            $this->executeUninstall($Input, $Output);
        } elseif ($Input->getArgument('act') === 'update') {
            $this->executeUpdate($Input, $Output);
        } else {
            $Io->caution('Unknow action');
        }// endif; action.

        unset($Io);
    }// execute


    /**
     * Disable a module.
     * 
     * @param InputInterface $Input
     * @param OutputInterface $Output
     */
    private function executeDisable(InputInterface $Input, OutputInterface $Output)
    {
        $Io = new SymfonyStyle($Input, $Output);
        $Io->title('Disable a module');

        $mname = $Input->getOption('mname');
        // validate if module exists (not care enabled or not, it can be enabled at end).
        $validated = $this->Modules->exists($mname, false);

        if ($validated !== true) {
            $Io->error('The module you entered is not exists.');
        } elseif ($validated === true) {
            // if validated the module.
            $FileSystem = new \System\Libraries\FileSystem(MODULE_PATH . DIRECTORY_SEPARATOR . $mname);
            if (!$FileSystem->isFile('.disabled', false)) {
                // if .disabled file is not exists.
                $FileSystem->createFile('.disabled', '');
            }
            $Io->success('Success, your module has been disabled.');
        }

        unset($Io, $mname);
    }// executeDisable


    /**
     * Enable a module.
     * 
     * @param InputInterface $Input
     * @param OutputInterface $Output
     */
    private function executeEnable(InputInterface $Input, OutputInterface $Output)
    {
        $Io = new SymfonyStyle($Input, $Output);
        $Io->title('Enable a module');

        $mname = $Input->getOption('mname');
        // validate if module exists (not care enabled or not, it can be enabled at end).
        $validated = $this->Modules->exists($mname, false);

        if ($validated !== true) {
            $Io->error('The module you entered is not exists.');
        } elseif ($validated === true) {
            // if validated the module.
            $FileSystem = new \System\Libraries\FileSystem(MODULE_PATH . DIRECTORY_SEPARATOR . $mname);
            $FileSystem->deleteFile('.disabled');
            $Io->success('Success, your module has been enabled.');
        }

        unset($Io, $mname);
    }// executeEnable


    /**
     * Install a module.
     * 
     * @param InputInterface $Input
     * @param OutputInterface $Output
     */
    private function executeInstall(InputInterface $Input, OutputInterface $Output)
    {
        $Io = new SymfonyStyle($Input, $Output);
        $Style = new OutputFormatterStyle('black', 'yellow');
        $Output->getFormatter()->setStyle('mark', $Style);
        $Io->title('Install a module');

        $mname = $Input->getOption('mname');
        // validate if module exists (not care enabled or not, it can be enabled at end).
        $validated = $this->Modules->exists($mname, false);

        if ($validated !== true) {
            $Io->error('The module you entered is not exists.');
        } elseif ($validated === true) {
            // if validated the module.

            // enable the module before continue. ------------------------------------------------------------------
            $command = $this->getApplication()->find('system:module');
            $arguments = [
                'command' => 'system:module',
                'act' => 'enable',
                '--mname' => $mname,
            ];
            $command->run(new ArrayInput($arguments), new NullOutput());
            unset($arguments, $command);

            // after enabled, register for auto load and class_exists will work.
            $this->Modules->registerAutoload();

            $installed = true;
            // check that target file and folder is writable.
            $installed = $this->isTargetFileFolderWritable($Io);

            // try to call installer class if exists. ---------------------------------------------------------------------
            $InstallerClassName = '\\Modules\\' . $mname . '\\Installer';
            if ($installed === true && class_exists($InstallerClassName)) {
                // if class exists.
                $Installer = new $InstallerClassName($this->Container);
                if ($Installer instanceof \System\Interfaces\ModuleInstaller) {
                    // if class really is the installer.
                    try {
                        $Installer->install();
                    } catch (\Exception $e) {
                        $Io->error($e->getMessage());
                        $installed = false;
                    }
                }
                unset($Installer);
            }
            unset($InstallerClassName);

            // try to do something else after installer was success. -----------------------------------------------
            if ($installed === true) {
                // if installed or installer class was called.
                // copy assets folder to public/Modules/[module_name]/assets folder. ----------------
                if (is_dir(MODULE_PATH . DIRECTORY_SEPARATOR . $mname . DIRECTORY_SEPARATOR . 'assets')) {
                    $Fs = new \System\Libraries\FileSystem(PUBLIC_PATH . DIRECTORY_SEPARATOR . 'Modules');
                    $Fs->copyFolderRecursive(MODULE_PATH . DIRECTORY_SEPARATOR . $mname . DIRECTORY_SEPARATOR . 'assets', $mname . DIRECTORY_SEPARATOR . 'assets');
                    unset($Fs);
                }

                // copy moduleComposer.json --------------------------------------------------------------
                if (
                    is_file(MODULE_PATH . DIRECTORY_SEPARATOR . $mname . DIRECTORY_SEPARATOR . 'moduleComposer.json') && 
                    is_file(ROOT_PATH . DIRECTORY_SEPARATOR . 'composer.json')
                ) {
                    // if moduleComposer.json was found in module's folder and composer.json was found in app's root folder.
                    // copy the "require" and "require-dev" to app's composer.json.
                    $result = $this->Modules->copyComposer($mname);
                    if ($result === true) {
                        $resultMessage = 'Due to this module contain additional composer packages. Please run "<mark>composer update</mark>" command after success.';
                    } else {
                        $resultWarning = 'Unable to copy module\'s composer to app\'s composer, please try to manually do this and run "composer update".';
                    }
                    unset($result);
                }

                // display success. ---------------------------------------------------------------------------
                $additionalMessages = [];
                if (isset($resultMessage)) {
                    $additionalMessages['resultMessage'] = $resultMessage;
                }
                if (isset($resultWarning)) {
                    $additionalMessages['warningMessage'] = $resultWarning;
                }
                $this->displaySuccess($Io, 'Success, your module has been installed.', $additionalMessages);
                unset($additionalMessages, $resultMessage, $resultWarning);
            }// endif; success installer
            unset($installed);
        }

        unset($Io, $mname, $validated);
    }// executeInstall


    /**
     * Uninstall a module.
     * 
     * @param InputInterface $Input
     * @param OutputInterface $Output
     */
    private function executeUninstall(InputInterface $Input, OutputInterface $Output)
    {
        $Io = new SymfonyStyle($Input, $Output);
        $Style = new OutputFormatterStyle('black', 'yellow');
        $Output->getFormatter()->setStyle('mark', $Style);
        $Io->title('Uninstall a module');
        $Helper = $this->getHelper('question');
        $nodeleteOption = $Input->getOption('nodelete');
        $questionMsg = 'Are you sure?' . "\n" . 'The module uninstaller class will be called.';
        if ($nodeleteOption === false) {
            $questionMsg .= 'The module\'s public folder, module\'s composer, and module folder will be deleted.' . "\n";
            $questionMsg .= '(y, n) - default is n.';
            $Question = new ConfirmationQuestion($questionMsg, false);
        } else {
            $questionMsg .= 'The module\'s public folder, and module\'s composer will be deleted. The module will be disabled.' . "\n";
            $questionMsg .= '(y, n) - default is n.';
            $Question = new ConfirmationQuestion($questionMsg, false);
        }
        unset($questionMsg);

        if (!$Helper->ask($Input, $Output, $Question)) {
            return;
        } else {
            $mname = $Input->getOption('mname');
            // validate if module exists (not care enabled or not, it can be enabled at end).
            $validated = $this->Modules->exists($mname, false);

            if ($validated !== true) {
                $Io->error('The module you entered is not exists.');
            } elseif ($validated === true) {
                // if validated the module.
                $uninstalled = true;
                // check that target file and folder is writable.
                $uninstalled = $this->isTargetFileFolderWritable($Io);

                // try to call installer class if exists. ---------------------------------------------------------------------
                $InstallerClassName = '\\Modules\\' . $mname . '\\Installer';
                if ($uninstalled === true && class_exists($InstallerClassName)) {
                    // if class exists.
                    $Installer = new $InstallerClassName($this->Container);
                    if ($Installer instanceof \System\Interfaces\ModuleInstaller) {
                        // if class really is the installer.
                        try {
                            $Installer->uninstall();
                        } catch (\Exception $e) {
                            $Io->error($e->getMessage());
                            $uninstalled = false;
                        }
                    }
                    unset($Installer);
                }
                unset($InstallerClassName);

                // try to do something else after installer was success. -----------------------------------------------
                if ($uninstalled === true) {
                    // if uninstalled or installer class was called.
                    // delete public/Modules/[module_name] folder.-----------------------------------------
                    if (realpath(ROOT_PATH) !== realpath(PUBLIC_PATH) && is_dir(PUBLIC_PATH . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR . $mname) && $mname != 'SystemCore') {
                        $Fs = new \System\Libraries\FileSystem(PUBLIC_PATH . DIRECTORY_SEPARATOR . 'Modules');
                        $Fs->deleteFolder($mname, true);
                        unset($Fs);
                    }

                    // delete Modules/[module_name] folder. ------------------------------------------------
                    if ($nodeleteOption === false) {
                        // if there is no option for "no delete" then delete the module folder from Modules folder.
                        // try to use command to delete folder if there is .git folder existing.
                        if (!defined('NOREMOVEDIRBYSHELL')) {
                            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                                // if on windows.
                                shell_exec('RMDIR /Q/S "' . MODULE_PATH . DIRECTORY_SEPARATOR . $mname . '"');
                            } else {
                                shell_exec('rm -r "' . MODULE_PATH . DIRECTORY_SEPARATOR . $mname . '" > /dev/null 2>&1');
                            }
                        }
                        // then try delete the module folders and files using php.
                        $Fs = new \System\Libraries\FileSystem(MODULE_PATH);
                        $Fs->deleteFolder($mname, true);
                        unset($Fs);
                    } else {
                        // if there is an option to "no delete" then disable the module.
                        $command = $this->getApplication()->find('system:module');
                        $arguments = [
                            'command' => 'system:module',
                            'act' => 'disable',
                            '--mname' => $mname,
                        ];
                        $command->run(new ArrayInput($arguments), new NullOutput());
                        unset($arguments, $command);
                    }

                    // remove moduleComposer.json ---------------------------------------------------------
                    if (
                        is_file(ROOT_PATH . DIRECTORY_SEPARATOR . 'composer.json') &&
                        is_file(ROOT_PATH . DIRECTORY_SEPARATOR . 'composer.default.json')
                    ) {
                        // delete main app's composer.json
                        unlink(ROOT_PATH . DIRECTORY_SEPARATOR . 'composer.json');
                        // copy composer.default.json to main app's composer.json
                        copy(ROOT_PATH . DIRECTORY_SEPARATOR . 'composer.default.json', ROOT_PATH . DIRECTORY_SEPARATOR . 'composer.json');
                        // copy composer.json from **ALL enabled** modules into main app's composer.json
                        $copiedResult = $this->Modules->copyComposerAllModules();

                        if (
                            is_array($copiedResult) &&
                            isset($copiedResult['modulesWithComposer']) &&
                            isset($copiedResult['successCopied']) &&
                            $copiedResult['modulesWithComposer'] === $copiedResult['successCopied']
                        ) {
                            $resultMessage = 'Due to the composer packages has been changed. Please run "<mark>composer update</mark>" command after success.';
                        } else {
                            $resultWarning = sprintf(
                                'Unable to copy composer.json from some modules, here is the list (%s). Please try to manually edit your composer by merge the required dependency and then run "composer update" command.', 
                                (isset($copiedResult['failedModules']) ? implode(', ', $copiedResult['failedModules']) : '')
                            );
                        }
                        unset($copiedResult);
                    }

                    // display success. ---------------------------------------------------------------------------
                    $additionalMessages = [];
                    if (isset($resultMessage)) {
                        $additionalMessages['resultMessage'] = $resultMessage;
                    }
                    if (isset($resultWarning)) {
                        $additionalMessages['warningMessage'] = $resultWarning;
                    }
                    $this->displaySuccess($Io, 'Success, your module has been uninstalled.', $additionalMessages);
                    unset($additionalMessages, $resultMessage, $resultWarning);
                }// endif; success installer
                unset($uninstalled);
            }
        }// endif; ask for confirm.

        unset($Helper, $Io, $mname, $nodeleteOption, $Question, $validated);
    }// executeUninstall


    /**
     * Update a module.
     * 
     * @param InputInterface $Input
     * @param OutputInterface $Output
     */
    private function executeUpdate(InputInterface $Input, OutputInterface $Output)
    {
        $Io = new SymfonyStyle($Input, $Output);
        $Style = new OutputFormatterStyle('black', 'yellow');
        $Output->getFormatter()->setStyle('mark', $Style);
        $Io->title('Update a module');

        $mname = $Input->getOption('mname');
        // validate if module exists (enabled only).
        $validated = $this->Modules->exists($mname);

        if ($validated !== true) {
            $Io->error('The module you entered is not exists or not enabled.');
        } elseif ($validated === true) {
            // if validated the module.
            $updated = true;
            // check that target file and folder is writable.
            $updated = $this->isTargetFileFolderWritable($Io);

            // try to call installer class if exists (for update). ----------------------------------------------------------
            $InstallerClassName = '\\Modules\\' . $mname . '\\Installer';
            if ($updated === true && class_exists($InstallerClassName)) {
                // if class exists.
                $Installer = new $InstallerClassName($this->Container);
                if ($Installer instanceof \System\Interfaces\ModuleInstaller) {
                    // if class really is the installer.
                    try {
                        $Installer->update();
                    } catch (\Exception $e) {
                        $Io->error($e->getMessage());
                        $updated = false;
                    }
                }
                unset($Installer);
            }
            unset($InstallerClassName);

            // try to do something else after update was success. ----------------------------------------------------
            if ($updated === true) {
                // if updated or installer class for update was called.
                // delete public/Modules/[module_name] folder.-----------------------------------------
                if (realpath(ROOT_PATH) !== realpath(PUBLIC_PATH) && is_dir(PUBLIC_PATH . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR . $mname) && $mname != 'SystemCore') {
                    $Fs = new \System\Libraries\FileSystem(PUBLIC_PATH . DIRECTORY_SEPARATOR . 'Modules');
                    $Fs->deleteFolder($mname, true);
                    unset($Fs);
                }

                // then copy assets folder to public/Modules/[module_name]/assets folder. ----------
                if (is_dir(MODULE_PATH . DIRECTORY_SEPARATOR . $mname . DIRECTORY_SEPARATOR . 'assets')) {
                    $Fs = new \System\Libraries\FileSystem(PUBLIC_PATH . DIRECTORY_SEPARATOR . 'Modules');
                    $Fs->copyFolderRecursive(MODULE_PATH . DIRECTORY_SEPARATOR . $mname . DIRECTORY_SEPARATOR . 'assets', $mname . DIRECTORY_SEPARATOR . 'assets');
                    unset($Fs);
                }

                // copy moduleComposer.json --------------------------------------------------------------
                if (
                    is_file(ROOT_PATH . DIRECTORY_SEPARATOR . 'composer.json') &&
                    is_file(ROOT_PATH . DIRECTORY_SEPARATOR . 'composer.default.json')
                ) {
                    // delete main app's composer.json
                    unlink(ROOT_PATH . DIRECTORY_SEPARATOR . 'composer.json');
                    // copy composer.default.json to main app's composer.json
                    copy(ROOT_PATH . DIRECTORY_SEPARATOR . 'composer.default.json', ROOT_PATH . DIRECTORY_SEPARATOR . 'composer.json');
                    // copy composer.json from **ALL enabled** modules into main app's composer.json
                    $copiedResult = $this->Modules->copyComposerAllModules();

                    if (
                        is_array($copiedResult) &&
                        isset($copiedResult['modulesWithComposer']) &&
                        isset($copiedResult['successCopied']) &&
                        $copiedResult['modulesWithComposer'] === $copiedResult['successCopied']
                    ) {
                        $resultMessage = 'Due to the composer packages has been changed. Please run "<mark>composer update</mark>" command after success.';
                    } else {
                        $resultWarning = sprintf(
                            'Unable to copy moduleComposer.json from some modules, here is the list (%s). Please try to manually edit your composer by merge the required dependency and then run "composer update" command.', 
                            (isset($copiedResult['failedModules']) ? implode(', ', $copiedResult['failedModules']) : '')
                        );
                    }
                    unset($copiedResult);
                }

                // display success. ---------------------------------------------------------------------------
                $additionalMessages = [];
                if (isset($resultMessage)) {
                    $additionalMessages['resultMessage'] = $resultMessage;
                }
                if (isset($resultWarning)) {
                    $additionalMessages['warningMessage'] = $resultWarning;
                }
                $this->displaySuccess($Io, 'Success, your module has been updated.', $additionalMessages);
                unset($additionalMessages, $resultMessage, $resultWarning);
            }// endif;  success installer
            unset($updated);
        }

        unset($Io, $mname, $validated);
    }// executeUpdate


    /**
     * Check if target file and folder is writable.
     * 
     * This will test main app's composer.json and public/Modules folder.<br>
     * If it is not writable then the error message will be displayed using `$Io` class.
     * 
     * @param SymfonyStyle $Io The output style class.
     * @return bool Return `true` if writable, `false` if it is not.
     */
    private function isTargetFileFolderWritable(SymfonyStyle $Io): bool
    {
        $result = true;

        if (
            is_file(ROOT_PATH . DIRECTORY_SEPARATOR . 'composer.json') &&
            !is_writable(ROOT_PATH . DIRECTORY_SEPARATOR . 'composer.json')
        ) {
            $result = false;
            $Io->error(
                sprintf(
                    'Please make sure that %s is writable.', 
                    ROOT_PATH . DIRECTORY_SEPARATOR . 'composer.json'
                )
            );
        } elseif (
            is_dir(PUBLIC_PATH . DIRECTORY_SEPARATOR . 'Modules') &&
            !is_writable(PUBLIC_PATH . DIRECTORY_SEPARATOR . 'Modules')
        ) {
            $result = false;
            $Io->error(
                sprintf(
                    'Please make sure that %s is writable.', 
                    PUBLIC_PATH . DIRECTORY_SEPARATOR . 'Modules'
                )
            );
        }

        return $result;
    }// isTargetFileFolderWritable


}
