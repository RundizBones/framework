<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\System\Core\Console;


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
    protected function configure()
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
                '  The two dots (.. or ../) is not allowed and will be removed from --subfolder option.' . "\n\n" .
                '  Example: ' . "\n" .
                '    system:storage delete --subfolder="logs"' . "\n" .
                '      This will be delete "logs" folder.' . "\n\n" .
                '    system:storage delete --subfolder="cache/routes*"' . "\n" .
                '      This will be delete all files that begins with "routes" in the "cache" folder.' . "\n\n" .
                "\n" .
                '[Clear storage folder]' . "\n" .
                '  This will be clear everything inside storage folder. It is not recommend to do this until you can make sure that those files and folders can be deleted.' . "\n" .
                '  Some files and folders maybe important because storage folder can be use for some process not just cache and log.' . "\n" .
                '  This is WARNED!' . "\n" .
                '  To clear, use clear argument.' . "\n\n" .
                '  Example: ' . "\n" .
                '    system:storage clear' . "\n" .
                '      This will be clear everything inside storage folder.' . "\n\n"
            )
            ->addArgument('act', InputArgument::REQUIRED, 'Action to do (list, delete, clear). The delete action must follow with --subfolder option.')
            ->addOption('subfolder', null, InputOption::VALUE_OPTIONAL, 'Delete sub folder name (not include the storage folder path). Can use glob pattern but not two dots.');
    }// configure


    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $Input, OutputInterface $Output)
    {
        $Io = new SymfonyStyle($Input, $Output);
        $this->FileSystem = new \Rdb\System\Libraries\FileSystem(STORAGE_PATH);

        if ($Input->getArgument('act') === 'list') {
            // if action is list.
            $this->executeList($Input, $Output);
        } elseif ($Input->getArgument('act') === 'delete') {
            // if action is delete.
            $this->executeDelete($Input, $Output);
        } elseif ($Input->getArgument('act') == 'clear') {
            // if action is clear.
            $this->executeClear($Input, $Output);
        } else {
            $Io->caution('Unknow action');
        }// endif action.

        $this->FileSystem = null;
        unset($Io);
    }// execute


    /**
     * Clear storage folder. (DELETE EVERYTHING!)
     * 
     * @param InputInterface $Input
     * @param OutputInterface $Output
     * @return void|null
     */
    private function executeClear(InputInterface $Input, OutputInterface $Output)
    {
        $Io = new SymfonyStyle($Input, $Output);
        $Io->title('Clear storage folder');
        $Helper = $this->getHelper('question');
        $Question = new ConfirmationQuestion('Continue with this action? (y, n)', false);

        if (!$Helper->ask($Input, $Output, $Question)) {
            return;
        } else {
            $clearResult = $this->FileSystem->deleteFolder('');
            $listFiles = $this->FileSystem->listFilesSubFolders('');

            if ($clearResult === true) {
                $deletedList = [];
                foreach ($this->FileSystem->trackDeleted as $row) {
                    $deletedList[][] = $row;
                }

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
                    }
                }

                // create .gitignore
                $this->FileSystem->writeFile('.gitignore', '*'."\n".'!.gitignore');

                // display success and list deleted files and folders.
                $Io->success('All files and folders are cleared.');                    
                $Io->table(['Deleted list'], $deletedList);
                unset($deletedList);
            } else {
                $Io->warning('Unable to clear the cache. It may not have any files or folders to delete.');
            }
            $this->FileSystem->trackDeleted = [];
            unset($clearResult, $listFiles);
        }// endif ask confirm

        unset($Helper, $Io, $Question);
    }// executeClear


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
                        $Io->table(['Deleted list'], $deletedList);
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
                    $Io->table(['Deleted list'], $deletedList);
                    unset($deletedList);
                } else {
                    $Io->warning('Unable to delete selected folder. It might not exists.');
                }

                $this->FileSystem->trackDeleted = [];
                unset($clearResult);
            }
        }// endif check option.

        unset($Io);
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
        } else {
            if (strpos($Input->getOption('subfolder'), '/') !== false) {
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

                    $Io->table(['List of files and folders'], $listFiles);
                    unset($listFiles);
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

                $Io->table(['List of files and folders'], $listFiles);
                unset($listFiles);
            }
        }// endif check option.

        unset($Io);
    }// executeList


}
