<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\System\Core\Console;


use \Symfony\Component\Console\Command\Command;
use \Symfony\Component\Console\Helper\Table;
use \Symfony\Component\Console\Helper\TableSeparator;
use \Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;
use \Symfony\Component\Console\Question\ConfirmationQuestion;
use \Symfony\Component\Console\Style\SymfonyStyle;


/**
 * Storage CLI.
 * 
 * @since 0.1
 */
class Storage extends BaseConsole
{


    /**
     * @var \Rdb\System\Libraries\FileSystem
     */
    protected $FileSystem;


    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this->setName('system:storage')
            ->setDescription('Manage all files & folders in storage folder.')
            ->setHelp(
                'Manage all files & folders in storage folder.' . "\n\n" .
                '[List target folder and file]' . "\n" .
                '  To list, use list argument and follow with --subfolder option.' . "\n" .
                '  To list target folder, use --subfolder with just folder name.' . "\n" .
                '  To list a file or some files in target folder, use --subfolder with folder/file pattern where file pattern is the glob pattern and subfolder option must contain slash (/).' . "\n" .
                '  See more about glob pattern at http://php.net/manual/en/function.glob.php' . "\n" .
                '  The target folder must be inside ' . STORAGE_PATH . "\n" .
                '  The two dots (.. or ../) is not allowed and will be removed from --subfolder option.' . "\n\n" .
                '  Example: ' . "\n" .
                '    system:storage list --subfolder="logs"' . "\n" .
                '      This will be list "logs" folder.' . "\n\n" .
                '    system:storage list --subfolder="cache/routes*"' . "\n" .
                '      This will be list all files that begins with "routes" in the "cache" folder.' . "\n\n" .
                '[Delete target folder or file]' . "\n" .
                '  To delete, use delete argument and follow with --subfolder option.' . "\n" .
                '  To delete target folder, use --subfolder with just folder name.' . "\n" .
                '  To delete a file or some files in target folder, use --subfolder with folder/file pattern where file pattern is the glob pattern and subfolder option must contain slash (/).' . "\n" .
                '  See more about glob pattern at http://php.net/manual/en/function.glob.php' . "\n" .
                '  The target folder must be inside ' . STORAGE_PATH . "\n" .
                '  The permanent folder cannot be deleted using this command.' . "\n" .
                '  The two dots (.. or ../) is not allowed and will be removed from --subfolder option.' . "\n\n" .
                '  Example: ' . "\n" .
                '    system:storage delete --subfolder="logs"' . "\n" .
                '      This will be delete "logs" folder.' . "\n\n" .
                '    system:storage delete --subfolder="cache/routes*"' . "\n" .
                '      This will be delete all files that begins with "routes" in the "cache" folder.' . "\n\n" .
                "\n" .
                '[Clear storage folder]' . "\n" .
                '  This will be clear cache, logs folders. It is not recommend to do this until you can make sure that those files and folders can be deleted.' . "\n" .
                '  The clear command will be limited to delete these folders due to the storage folder canbe use to store important server side files such as administrator uploaded files.' . "\n" .
                '  To delete another folders than cache and logs, please use delete argument.' . "\n\n" .
                '  Example: ' . "\n" .
                '    system:storage clear' . "\n" .
                '      This will be clear cache, logs folders.' . "\n\n"
            )
            ->addArgument('act', InputArgument::REQUIRED, 'Action to do (list, delete, clear). The delete and list actions must follow with --subfolder option.')
            ->addOption('subfolder', null, InputOption::VALUE_OPTIONAL, 'List, delete sub folder name (not include the storage folder path). Can use glob pattern but not two dots.');
    }// configure


    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $Input, OutputInterface $Output): int
    {
        $Io = new SymfonyStyle($Input, $Output);
        $this->FileSystem = new \Rdb\System\Libraries\FileSystem(STORAGE_PATH);

        if ($Input->getArgument('act') === 'list') {
            // if action is list.
            return $this->executeList($Input, $Output);
        } elseif ($Input->getArgument('act') === 'delete') {
            // if action is delete.
            return $this->executeDelete($Input, $Output);
        } elseif ($Input->getArgument('act') == 'clear') {
            // if action is clear.
            return $this->executeClear($Input, $Output);
        } else {
            $Io->caution('Unknow action');
            if (defined('Command::INVALID')) {
                return Command::INVALID;
            } else {
                return 2;
            }
        }// endif action.
    }// execute


    /**
     * Clear storage folder. (delete cache, logs folders.)
     * 
     * @param InputInterface $Input
     * @param OutputInterface $Output
     * @return void|null
     */
    private function executeClear(InputInterface $Input, OutputInterface $Output)
    {
        $Io = new SymfonyStyle($Input, $Output);
        $Io->title('Clear storage folder (cache, logs)');
        $Helper = $this->getHelper('question');
        $Question = new ConfirmationQuestion('Continue with this action? (y, n)', false);

        if (!$Helper->ask($Input, $Output, $Question)) {
            return 0;
        } else {
            $deletedList = [];
            $deletedList = array_merge($deletedList, $this->executeClearDelete());
            $deletedList = array_merge($deletedList, $this->executeClearDelete('logs'));

            if (isset($deletedList) && !empty($deletedList)) {
                // create .gitignore
                $this->FileSystem->writeFile('.gitignore', '*'."\n".'!.gitignore');

                // display success and list deleted files and folders.
                $Io->success('The target files and folders are cleared.');            
                if (isset($deletedList)) {
                    $this->renderTableFiles($Output, 'Deleted list', $deletedList);
                }
                unset($deletedList);
            } else {
                $Io->warning('Unable to clear the storage. It may not have any files or folders to delete.');
            }
            $this->FileSystem->trackDeleted = [];
        }// endif ask confirm

        unset($Helper, $Io, $Question);

        if (defined('Command::SUCCESS')) {
            return Command::SUCCESS;
        } else {
            return 0;
        }
    }// executeClear


    /**
     * Do clear target folder.
     * 
     * @param string $targetFolder Target folder to clear.
     * @return array Return deleted files and folders list.
     */
    private function executeClearDelete($targetFolder = 'cache'): array
    {
        if (empty($targetFolder)) {
            return [];
        }

        $clearResult = $this->FileSystem->deleteFolder($targetFolder);
        $listFiles = $this->FileSystem->listFilesSubFolders($targetFolder);
        $deletedList = [];

        if (isset($clearResult) && $clearResult === true) {
            foreach ($this->FileSystem->trackDeleted as $row) {
                $deletedList[][] = $row;
            }// endforeach;
            unset($row);

            // make very sure that there are no folders or files left.
            if (is_array($listFiles)) {
                $this->FileSystem->trackDeleted = [];

                foreach ($listFiles as $eachFile) {
                    if (is_file(STORAGE_PATH . DIRECTORY_SEPARATOR . $eachFile)) {
                        $this->FileSystem->deleteFile($eachFile);
                    } else {
                        $this->FileSystem->deleteFolder($eachFile, true);
                    }
                }// endforeach;
                unset($eachFile);

                foreach ($this->FileSystem->trackDeleted as $row) {
                    $deletedList[][] = $row;
                }// endforeach;
                unset($row);
            }
        }

        unset($clearResult, $listFiles);
        return $deletedList;
    }// executeClearDelete


    /**
     * Delete folders and files.
     * 
     * @param InputInterface $Input
     * @param OutputInterface $Output
     */
    private function executeDelete(InputInterface $Input, OutputInterface $Output)
    {
        $Io = new SymfonyStyle($Input, $Output);
        $Io->title('Delete target folder or file');

        if ($Input->getOption('subfolder') == null) {
            $Io->caution('No subfolder option specified.');

            if (defined('Command::INVALID')) {
                return Command::INVALID;
            } else {
                return 2;
            }
        } elseif (
            rtrim($this->FileSystem->getFullPathWithRoot($Input->getOption('subfolder')), " \n\r\t\v\x00\\/" . PHP_EOL) === rtrim($this->FileSystem->getFullPathWithRoot('permanent'), " \n\r\t\v\x00\\/" . PHP_EOL)
        ) {
            $Io->caution('The permanent folder cannot be deleted using this command.');
            if (defined('Command::INVALID')) {
                return Command::INVALID;
            } else {
                return 2;
            }
        } else {
            if (strpos($Input->getOption('subfolder'), '/') !== false) {
                $subfolder = str_replace(['../', '..\\', '..'], '', $Input->getOption('subfolder'));
                $filesArray = glob(STORAGE_PATH.DIRECTORY_SEPARATOR.$subfolder, GLOB_NOSORT);
                $deletedList = [];

                if (is_array($filesArray) && !empty($filesArray)) {
                    // if there are files, folders to work.
                    $count = 0;
                    foreach ($filesArray as $file) {
                        $file = realpath($file);
                        if (is_writable($file)) {
                            if (is_file($file)) {
                                @unlink($file);
                                $deletedList[][] = $file;
                            } elseif (is_dir($file)) {
                                @rmdir($file);
                                $deletedList[][] = $file;
                            }
                        }
                        $count++;
                    }// endforeach;
                    unset($file);

                    // display success or error message.
                    if ($count == count($filesArray)) {
                        $Io->success('The selected folder and file pattern has been deleted.');
                        if (isset($deletedList)) {
                            $this->renderTableFiles($Output, 'Deleted list', $deletedList);
                        }
                    } else {
                        $Io->error('Unable to delete selected folder and file pattern.');
                    }
                    unset($count);
                } else {
                    $Io->warning('The selected folder and file pattern returns no data.');
                }
                unset($deletedList, $filesArray, $subfolder);
            } else {
                $clearResult = $this->FileSystem->deleteFolder($Input->getOption('subfolder'), true);

                if ($clearResult === true) {
                    $deletedList = [];
                    foreach ($this->FileSystem->trackDeleted as $row) {
                        $deletedList[][] = $row;
                    }

                    // display success and list deleted files and folders.
                    $Io->success('The selected folder has been deleted.');
                    if (isset($deletedList)) {
                        $this->renderTableFiles($Output, 'Deleted list', $deletedList);
                    }
                    unset($deletedList);
                } else {
                    $Io->warning('Unable to delete selected folder. It might not exists.');
                }

                $this->FileSystem->trackDeleted = [];
                unset($clearResult);
            }
        }// endif check option.

        unset($Io);

        if (defined('Command::SUCCESS')) {
            return Command::SUCCESS;
        } else {
            return 0;
        }
    }// executeDelete


    /**
     * List folders and files.
     * 
     * @param InputInterface $Input
     * @param OutputInterface $Output
     */
    private function executeList(InputInterface $Input, OutputInterface $Output)
    {
        $Io = new SymfonyStyle($Input, $Output);
        $Io->title('List target folder and file');

        if ($Input->getOption('subfolder') == null) {
            $Io->caution('No subfolder option specified.');

            if (defined('Command::INVALID')) {
                return Command::INVALID;
            } else {
                return 2;
            }
        } else {
            if (strpos($Input->getOption('subfolder'), '/') !== false) {
                // if using glob path pattern (contain / as directory separator NOT \).
                $subfolder = str_replace(['../', '..\\', '..'], '', $Input->getOption('subfolder'));
                $filesArray = glob(STORAGE_PATH.DIRECTORY_SEPARATOR.$subfolder, GLOB_NOSORT);

                if (is_array($filesArray) && !empty($filesArray)) {
                    // if there are files, folders to work.
                    $listFiles = [];
                    foreach ($filesArray as $file) {
                        $file = realpath($file);
                        $listFiles[][] = $file;
                    }// endforeach;
                    unset($file);
                } else {
                    $Io->warning('The selected folder and file pattern returns no data.');
                }
                unset($filesArray, $subfolder);
            } else {
                $listResult = $this->FileSystem->listFilesSubFolders($Input->getOption('subfolder'));
                $listFiles = [];
                if (is_array($listResult)) {
                    foreach ($listResult as $eachFile) {
                        $listFiles[][] = realpath(STORAGE_PATH . DIRECTORY_SEPARATOR . $eachFile);
                    }// endforeach;
                    unset($eachFile);
                }
                unset($listResult);
            }

            if (isset($listFiles)) {
                $this->renderTableFiles($Output, 'List of files and folders', $listFiles);
            }

            unset($listFiles);
        }// endif check option.

        unset($Io);

        if (defined('Command::SUCCESS')) {
            return Command::SUCCESS;
        } else {
            return 0;
        }
    }// executeList


    /**
     * Render files to table.
     * 
     * @param OutputInterface $Output Symfony OutputInterface class.
     * @param string $tableHead Table header text.
     * @param array $listFiles The files list as array.
     */
    private function renderTableFiles(OutputInterface $Output, string $tableHead, array $listFiles)
    {
        $Table = new Table($Output);
        $Table->setColumnMaxWidth(0, 50);

        if (isset($listFiles) && is_array($listFiles)) {
            $Table->setHeaders([$tableHead]);
            $arrayKeys = array_keys($listFiles);
            $lastArrayKey = array_pop($arrayKeys);
            unset($arrayKeys);
            foreach ($listFiles as $key => $eachFile) {
                $Table->addRow($eachFile);
                if ($key !== $lastArrayKey) {
                    $Table->addRow(new TableSeparator());
                }
            }// endforeach;
            unset($eachFile, $key, $lastArrayKey);
            $Table->render();
        }

        unset($Table);
    }// renderTableFiles


}
