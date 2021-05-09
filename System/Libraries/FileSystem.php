<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\System\Libraries;


/**
 * Works about files and folders.
 *
 * @since 0.1
 */
class FileSystem
{


    /**
     * @var array The associative array to track result of copy. Example: `[0 => ['src' => 'path1/file.txt', 'dest' => 'path2/file.txt', 'result' => true], 1 => ...]`
     */
    public $trackCopy = [];


    /**
     * @var array The associative array to track result of create folder and file. Example: `[0 => ['path' => 'path1/file.txt', 'result' => true], 1 => ...]`
     */
    public $trackCreate = [];


    /**
     * @var array For track deleted files and folders.
     */
    public $trackDeleted = [];


    /**
     * @var string Path to root folder that this class will be working with. No trailing slash.
     */
    protected $root;


    /**
     * Class constructor.
     * 
     * @param string $root Path to root folder that this class will be working with.
     *                                  If leave this empty and `STORAGE_PATH` constant was defined, it will be use this constant by default.
     *                                  No trailing slash.
     */
    public function __construct(string $root = '')
    {
        if (empty($root) && defined('STORAGE_PATH')) {
            $root = STORAGE_PATH;
        }

        $this->root = $root;
        $this->trackCopy = [];
        $this->trackCreate = [];
        $this->trackDeleted = [];
    }// __construct


    /**
     * Copy folder recursively.
     * 
     * This method does not support overwrite of existing destination file.
     * 
     * Example:
     * <pre>
     * $Fs = new \Rdb\System\Libraries\FileSystem('/www/my-installed-path/public/Modules');
     * $Fs->copyFolderRecursive('/www/my-installed-path/Modules/MyModule/assets', 'MyModules/assets');
     * // then every thing in /www/my-installed-path/Modules/MyModule/assets will be copy to /www/my-installed-path/public/Modules/MyModules/assets
     * </pre>
     * 
     * @link https://stackoverflow.com/a/7775949/128761 Reference.
     * @param string $source Full path to source folder. No trailing slash.
     * @param string $dest Path to destination folder inside the `$root` in class constructor. No trailing slash.
     * @return bool Return `true` on success, `false` on failure.
     */
    public function copyFolderRecursive(string $source, string $dest)
    {
        $this->createFolder($dest);

        $RecursiveDirIter = new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS);
        $RecursiveIter = new \RecursiveIteratorIterator($RecursiveDirIter, \RecursiveIteratorIterator::SELF_FIRST);
        unset($RecursiveDirIter);
        $result = true;

        if (!is_array($this->trackCopy)) {
            $this->trackCopy = [];
        }

        if (is_array($RecursiveIter) || is_object($RecursiveIter)) {
            foreach ($RecursiveIter as $item) {
                if ($item->isDir()) {
                    $actionResult = $this->createFolder($dest . DIRECTORY_SEPARATOR . $RecursiveIter->getSubPathname());
                } elseif ($item->isFile()) {
                    // remove any two dots or upper directory.
                    $dest = $this->removeUpperPath($dest);

                    $actionResult = copy($item, $this->root . DIRECTORY_SEPARATOR . $dest . DIRECTORY_SEPARATOR . $RecursiveIter->getSubPathname());
                    $this->trackCopy[] = [
                        'src' => $item,
                        'dest' => $this->root . DIRECTORY_SEPARATOR . $dest . DIRECTORY_SEPARATOR . $RecursiveIter->getSubPathname(),
                        'result' => $actionResult,
                    ];
                }

                if ($result === true && isset($actionResult)) {
                    // if result is still true then it can be overwrite the `$result` but if it is false then cannot overwrite the `$result`.
                    $result = $actionResult;
                }
                unset($actionResult);
            }// endforeach;
            unset($item);
        }

        unset($RecursiveIter);
        return $result;
    }// copyFolderRecursive


    /**
     * Create new file.
     * 
     * It is an alias of `writeFile()` method.
     * 
     * @param string $path Path to the file to create and write that is not included with `$root` in the constructor.
     * @param string $contents The file contents.
     * @return mixed Returns the number of bytes that were written to the file, or `false` on failure.
     */
    public function createFile(string $path, string $contents)
    {
        return $this->writeFile($path, $contents);
    }// createFile


    /**
     * Create folder.
     * 
     * @param string $dirname The folder name that is not included with `$root` in the constructor. The folder name can included sub folders such as path1/sub1/sub2.
     * @return bool Return `true` on success, `false` on failure. If folder is already exists then it returns `true`.
     */
    public function createFolder(string $dirname): bool
    {
        $dirname = $this->removeUpperPath($dirname);

        if (!is_array($this->trackCreate)) {
            $this->trackCreate = [];
        }

        if (!is_dir($this->root . DIRECTORY_SEPARATOR . $dirname)) {
            $umask = umask(0);
            $output = mkdir($this->root . DIRECTORY_SEPARATOR . $dirname, 0755, true);
            umask($umask);
        } else {
            $output = true;
        }

        $this->trackCreate[] = [
            'path' => $this->root . DIRECTORY_SEPARATOR . $dirname,
            'result' => $output,
        ];

        unset($umask);
        return $output;
    }// createFolder


    /**
     * Delete a file.
     * 
     * @param string $fileName The file name that is not included with `$root` in the constructor. The file name can included sub folder such as path/to/file.txt
     * @return bool Return `true` on success, `false` on failure. If file is not exists then it returns `true`.
     */
    public function deleteFile(string $fileName): bool
    {
        $fileName = $this->removeUpperPath($fileName);

        if (
            is_file($this->root . DIRECTORY_SEPARATOR . $fileName) && 
            is_writable($this->root . DIRECTORY_SEPARATOR . $fileName)
        ) {
            if (!is_array($this->trackDeleted)) {
                $this->trackDeleted = [];
            }

            $this->trackDeleted[] = $this->root . DIRECTORY_SEPARATOR . $fileName;
            return unlink($this->root . DIRECTORY_SEPARATOR . $fileName);
        } elseif (!file_exists($this->root . DIRECTORY_SEPARATOR . $fileName)) {
            return true;
        } elseif (!is_writable($this->root . DIRECTORY_SEPARATOR . $fileName)) {
            return false;
        }

        return false;
    }// deleteFile


    /**
     * Delete folder and its content.
     * 
     * @param string $dirname The folder name that is not included with `$root` in the constructor. The folder name can included sub folders such as path1/sub1/sub2.
     * @param bool $deleteSelf Delete `$dirname` itself if set to `true`, set to `false` will just delete all files and sub folders in it.
     * @param int $deleted Dot not set this, it is for count number of deleted items. Do not set this in your calls.
     * @param string $dirnameSelf Do not set this, it is for checking prevent delete self. Do not set this in your calls.
     * @return bool Return `true` on success, `false` on failure. If there is nothing to delete then it will be return false.
     */
    public function deleteFolder(string $dirname, bool $deleteSelf = false, &$deleted = 0, $dirnameSelf = null): bool
    {
        $dirname = $this->removeUpperPath($dirname);
        if ($dirnameSelf == null) {
            $dirnameSelf = $dirname;
        }

        if (!is_array($this->trackDeleted)) {
            $this->trackDeleted = [];
        }

        if (is_dir($this->root . DIRECTORY_SEPARATOR . $dirname) && is_readable($this->root . DIRECTORY_SEPARATOR . $dirname)) {
            $handle = opendir($this->root . DIRECTORY_SEPARATOR . $dirname);
            while (($file = readdir($handle)) !== false) {
                if ($file != '.' && $file != '..') {
                    $relativePath = ($dirname != '' ? $dirname . DIRECTORY_SEPARATOR . $file : $dirname . $file);
                    if (is_dir($this->root . DIRECTORY_SEPARATOR . $relativePath)) {
                        $this->deleteFolder($relativePath, $deleteSelf, $deleted, $dirnameSelf);
                    } else {
                        if (is_file($this->root . DIRECTORY_SEPARATOR . $relativePath) && is_writable($this->root . DIRECTORY_SEPARATOR . $relativePath)) {
                            $this->trackDeleted[] = $this->root . DIRECTORY_SEPARATOR . $relativePath;
                            unlink($this->root . DIRECTORY_SEPARATOR . $relativePath);
                            $deleted++;
                        }
                    }
                }
            }// endwhile;
            closedir($handle);

            if (
                (
                    $deleteSelf === true || 
                    (
                        $deleteSelf === false && 
                        realpath($this->root . DIRECTORY_SEPARATOR . $dirnameSelf) !== realpath($this->root) &&
                        realpath($this->root . DIRECTORY_SEPARATOR . $dirname) !== realpath($this->root . DIRECTORY_SEPARATOR . $dirnameSelf)
                    )
                ) &&
                realpath($this->root . DIRECTORY_SEPARATOR . $dirnameSelf) !== realpath($this->root)// Do not delete root.
            ) {
                $this->trackDeleted[] = $this->root . DIRECTORY_SEPARATOR . $dirname;
                rmdir($this->root . DIRECTORY_SEPARATOR . $dirname);
                $deleted++;
            }
            unset($file, $handle);
        }

        if ($deleted > 0) {
            return true;
        }
        return false;
    }// deleteFolder


    /**
     * Get file extension only.
     * 
     * @since 1.1.3
     * @param string $path File name (with or without extension) or any path to get only file name.
     * @return string Return only file extension without dot.
     */
    public function getFileExtensionOnly(string $path): string
    {
        $path = str_replace(['\\', '/', DIRECTORY_SEPARATOR], '/', $path);
        $expPath = explode('/', $path);
        $fileName = $expPath[count($expPath) - 1];
        unset($expPath);

        $expFile = explode('.', $fileName);
        return $expFile[count($expFile) - 1];
    }// getFileExtensionOnly


    /**
     * Get file name only.
     * 
     * @since 1.1.3
     * @param string $path File name (with or without extension) or any path to get only file name.
     * @return string Return only file name without extension.
     */
    public function getFileNameOnly(string $path): string
    {
        $path = str_replace(['\\', '/', DIRECTORY_SEPARATOR], '/', $path);
        $expPath = explode('/', $path);
        $fileName = $expPath[count($expPath) - 1];
        unset($expPath);

        $expFile = explode('.', $fileName);
        if (count($expFile) > 1) {
            // if there is at least one dot. example file.ext, file.name.ext
            // remove file extension.
            unset($expFile[count($expFile) - 1]);
        }
        $fileName = implode('.', $expFile);
        unset($expFile);

        return $fileName;
    }// getFileNameOnly


    /**
     * Get total folder size.
     * 
     * @link https://stackoverflow.com/a/21409562/128761 Original source code.
     * @param string $path Path to the file that is not included with `$root` in the constructor. No need to prepend with slash.
     * @return int Return total bytes.
     */
    public function getFolderSize(string $path): int
    {
        $bytesTotal = 0;
        $fullPath = $this->root . DIRECTORY_SEPARATOR . $path;

        if(!empty($fullPath) && file_exists($fullPath)){
            foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($fullPath, \FilesystemIterator::SKIP_DOTS)) as $object){
                $bytesTotal += $object->getSize();
            }// endforeach;
        }

        return (int) $bytesTotal;
    }// getFolderSize


    /**
     * Get file or folder timestamp.
     * 
     * @param string $path Path to the file that is not included with `$root` in the constructor. No need to prepend with slash.
     * @return int|false Return timestamp or `false` on failure.
     */
    public function getTimestamp(string $path)
    {
        // use filemtime is fastest.
        $output = false;
        if (file_exists($this->root . DIRECTORY_SEPARATOR . $path)) {
            $output = filemtime($this->root . DIRECTORY_SEPARATOR . $path);
        }
        return $output;
    }// getTimestamp


    /**
     * Check for is file exists and it is folder (or directory).
     * 
     * @param string $path Path to the folder (or directory) to check, it must be inside the `$root` in class constructor.
     * @param bool|null $caseSensitive Set to `true` to check by case sensitive, `false` to case insensitive, `null` means up to the OS. The `null` value will be use PHP `is_dir()` function.
     * @return bool Returns `true` if the filename exists and is a directory, `false` otherwise.
     */
    public function isDir(string $path, $caseSensitive = null): bool
    {
        // Summary of functions/classes to list directory in milliseconds.
        // Flysystem->listPaths = ~11.5
        // glob = ~0.52 (fastest)
        // opendir&readdir = ~0.65
        // scandir = ~0.76
        // \DirectoryIterator = ~0.57
        // \FilesystemIterator = ~0.61
        
        if ($caseSensitive === null) {
            return is_dir($this->root . DIRECTORY_SEPARATOR . $path);
        } else {
            $fullPath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $this->root . DIRECTORY_SEPARATOR . $path);
            // mb_strtolower in case that some one use non-english.
            // see an example at http://stackoverflow.com/a/28701459/128761
            $fullPathLower = mb_strtolower($fullPath);
            $directory = dirname($fullPath);
            $filesArray = glob($directory . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);

            if (is_array($filesArray)) {
                foreach ($filesArray as $file) {
                    if ($caseSensitive === true && $file === $fullPath && is_dir($file)) {
                        unset($directory, $file, $filesArray, $fullPath, $fullPathLower);
                        return true;
                    } elseif ($caseSensitive === false && mb_strtolower($file) === $fullPathLower) {
                        unset($directory, $file, $filesArray, $fullPath, $fullPathLower);
                        return true;
                    }
                }// endforeach;
                unset($file);
            }

            unset($directory, $filesArray, $fullPath, $fullPathLower);
            return false;
        }
    }// isDir


    /**
     * Check for is file exists and it is file.
     * 
     * @param string $path Path to the file to check, it must be inside the `$root` in class constructor.
     * @param bool|null $caseSensitive Set to `true` to check by case sensitive, `false` to case insensitive, `null` means up to the OS. The `null` value will be use PHP `is_file()` function.
     * @return bool Returns `true` if the filename exists and is a file, `false` otherwise.
     */
    public function isFile(string $path, $caseSensitive = null): bool
    {
        if ($caseSensitive === null) {
            return is_file($this->root . DIRECTORY_SEPARATOR . $path);
        } else {
            $fullPath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $this->root . DIRECTORY_SEPARATOR . $path);
            // mb_strtolower in case that some one use non-english.
            // see an example at http://stackoverflow.com/a/28701459/128761
            $fullPathLower = mb_strtolower($fullPath);
            $directory = dirname($fullPath);
            $dirIterator = new \DirectoryIterator($directory);
            if (is_object($dirIterator)) {
                foreach ($dirIterator as $file) {
                    if ($file->isFile()) {
                        if ($caseSensitive === true && $file->getPathname() === $fullPath) {
                            unset($directory, $dirIterator, $file, $fullPath, $fullPathLower);
                            return true;
                        } elseif ($caseSensitive === false && mb_strtolower($file->getPathname()) === $fullPathLower) {
                            unset($directory, $dirIterator, $file, $fullPath, $fullPathLower);
                            return true;
                        }
                    }
                }// endforeach;
                unset($file);
            }

            unset($directory, $dirIterator, $fullPath, $fullPathLower);
            return false;
        }
    }// isFile


    /**
     * List all files only, folders only, files and folders in single level of specified $dirname.
     * 
     * @since 0.2.2
     * @param string $dirname Path to folder inside the root.
     * @param string $filterType Filter type of listing. Accept: 'files', 'folders', '' (empty string or all files and folders). Default is empty string.
     * @return array Return the array list of files (or folders).
     *                          Example: if dirname is "a" then result will be `array(0 => a/file1.txt, 1 =>  a/folder1, 2 => a/file2.txt)`.
     *                          The return value will be relative path from `$root`.
     */
    public function listFiles(string $dirname, string $filterType = ''): array
    {
        // sanitize filter type.
        if ($filterType === 'file' || $filterType === 'files') {
            $filterType = 'files';
        } elseif ($filterType === 'folder' || $filterType === 'folders') {
            $filterType = 'folders';
        } else {
            $filterType = '';
        }

        $dirname = $this->removeUpperPath($dirname);
        $files = [];

        if (is_dir($this->root . DIRECTORY_SEPARATOR . $dirname) && is_readable($this->root . DIRECTORY_SEPARATOR . $dirname)) {
            $handle = opendir($this->root . DIRECTORY_SEPARATOR . $dirname);
            while (($file = readdir($handle)) !== false) {
                if ($file != '.' && $file != '..') {
                    $relativePath = ($dirname != '' ? $dirname . DIRECTORY_SEPARATOR . $file : $dirname . $file);
                    if ($filterType !== '') {
                        if (is_dir($this->root . DIRECTORY_SEPARATOR . $relativePath) && $filterType === 'folders') {
                            $files[] = $relativePath;
                        } elseif (is_file($this->root . DIRECTORY_SEPARATOR . $relativePath) && $filterType === 'files') {
                            $files[] = $relativePath;
                        }
                    } else {
                        $files[] = $relativePath;
                    }
                }
            }// endwhile;
            closedir($handle);
        }

        return $files;
    }// listFiles


    /**
     * List all files and subfolders in specified $dirname.
     * 
     * @param string $dirname Path to folder inside the root.
     * @param array $files For access currently listed folders & files. Do not set value to this argument.
     * @return array Return the array list of files and sub folders. 
     *                          Example: if dirname is "a" then result will be `array(0 => a/1, 1 =>  a/1/1.1, 2 => a/1/1.1/1.1.txt, 3 => a/aa, 4 => a/aa/aaa)`.
     *                          The return value will be relative path from `$root`.
     */
    public function listFilesSubFolders(string $dirname, array &$files = []): array
    {
        $dirname = $this->removeUpperPath($dirname);

        if (is_dir($this->root . DIRECTORY_SEPARATOR . $dirname) && is_readable($this->root . DIRECTORY_SEPARATOR . $dirname)) {
            $handle = opendir($this->root . DIRECTORY_SEPARATOR . $dirname);
            while (($file = readdir($handle)) !== false) {
                if ($file != '.' && $file != '..') {
                    $relativePath = ($dirname != '' ? $dirname . DIRECTORY_SEPARATOR . $file : $dirname . $file);
                    if (is_dir($this->root . DIRECTORY_SEPARATOR . $relativePath)) {
                        $files[] = $relativePath;
                        $this->listFilesSubFolders($relativePath, $files);
                    } else {
                        if (is_file($this->root . DIRECTORY_SEPARATOR . $relativePath)) {
                            $files[] = $relativePath;
                        }
                    }
                }
            }// endwhile;
            closedir($handle);
        }

        return $files;
    }// listFilesSubFolders


    /**
     * Remove any upper path string such as ../ from string.
     * 
     * @param string $path
     * @return string
     */
    protected function removeUpperPath(string $path): string
    {
        $path = str_replace(['../', '..\\', '...', '..'], '', $path);
        return $path;
    }// removeUpperPath


    /**
     * Rename a file or directory.
     * 
     * @since 1.1.2
     * @see https://www.php.net/manual/en/function.rename.php PHP rename function behavior about overwritten and emit a warning.
     * @param string $oldName The old name. Related from root specified in class constructor.
     * @param string $newName The new name. Related from root specified in class constructor.
     * @param array $options Available options:<br>
     *                      `checkOldNameExists` (bool) Set to `false` for skip check file exists for old name before rename and it will be use PHP's `rename()` function behavior. 
     *                          Set to `true` to check before. Default is `true`.<br>
     *                      `checkNewNameExists` (bool) Set to `false` for skip check file exists for new name before rename and it will be use PHP's `rename()` function behavior. 
     *                          Set to `true` to check before. Default is `true`.<br>
     * @return bool Return `true` on success, `false` on failure.
     */
    public function rename(string $oldName, string $newName, array $options = []): bool
    {
        $oldName = $this->removeUpperPath($oldName);
        $newName = $this->removeUpperPath($newName);

        if (!isset($options['checkOldNameExists']) || $options['checkOldNameExists'] === true) {
            if (!file_exists($this->root . DIRECTORY_SEPARATOR . $oldName)) {
                return false;
            }
        }
        if (!isset($options['checkNewNameExists']) || $options['checkNewNameExists'] === true) {
            if (file_exists($this->root . DIRECTORY_SEPARATOR . $newName)) {
                return false;
            }
        }

        return rename($this->root . DIRECTORY_SEPARATOR . $oldName, $this->root . DIRECTORY_SEPARATOR . $newName);
    }// rename


    /**
     * Set web safe file name.
     * 
     * Allowed characters: 0-9, a-z, -, _, . (alpha-numeric, dash, underscore, dot).<br>
     * Replace multiple spaces to one space<br>
     * Replace space to dash<br>
     * Replace not allowed characters to empty<br>
     * Replace multiple dashes to one dash<br>
     * Replace multiple dots to one dot.
     * 
     * @since 1.1.2
     * @param string $file The entered file name to rename.
     * @return string Return formatted for web safe file name.
     */
    public function setWebSafeFileName(string $file): string
    {
        // replace multiple spaces to one space.
        $file = preg_replace('#\s+#iu', ' ', $file);
        // replace space to dash.
        $file = str_replace(' ', '-', $file);
        // replace non alpha-numeric to nothing.
        $file = preg_replace('#[^\da-z\-_\.]#iu', '', $file);
        // replace multiple dashes to one dash.
        $file = preg_replace('#-{2,}#', '-', $file);
        // replace multiple dots to one dot.
        $file = preg_replace('#\.{2,}#', '.', $file);

        return $file;
    }// setWebSafeFileName


    /**
     * Write file. (Create a file).
     * 
     * @param string $path Path to the file to create and write that is not included with `$root` in the constructor.
     * @param string $contents The file contents.
     * @return mixed Returns the number of bytes that were written to the file, or `false` on failure.
     */
    public function writeFile(string $path, string $contents)
    {
        if (!is_array($this->trackCreate)) {
            $this->trackCreate = [];
        }

        $path = $this->removeUpperPath($path);
        $result = file_put_contents($this->root . DIRECTORY_SEPARATOR . $path, $contents);

        $this->trackCreate[] = [
            'path' => $this->root . DIRECTORY_SEPARATOR . $path,
            'result' => $result,
        ];

        return $result;
    }// writeFile


}