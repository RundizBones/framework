<?php
/** 
 * RundizBones framework public file.
 *
 * This file should be uploaded to your public root web such as `public_html`, `www`, `public`, or any name.
 *
 * @package RundizBones
 * @license http://opensource.org/licenses/MIT MIT
 */


// Defined some constants that will be useful. --------------------------------------------
if (!defined('APP_ENV')) {
    /**
     * Define app environment.
     *
     * Value can be 'production', 'development'.
     * The config value will be auto-detect by order if the files in these environments were not found.
     */
    define('APP_ENV', 'development');
}
if (!defined('ROOT_PATH')) {
    /**
     * Framework root path.
     *
     * The full path to the framework's root path where contains "config", "Modules", "storage", "System" folders.
     */
    define('ROOT_PATH', dirname(__DIR__));
}
if (!defined('STORAGE_PATH')) {
    /**
     * Storage path.
     *
     * The full path to storage folder. This folder contains logs, cache.
     * By default it is `ROOT_PATH . '/storage'`.
     */
    define('STORAGE_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'storage');
}
if (!defined('MODULE_PATH')) {
    /**
     * Modules path.
     *
     * The full path to modules folder.
     * By default it is `ROOT_PATH . '/Modules'`.
     */
    define('MODULE_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'Modules');
}
if (!defined('PUBLIC_PATH')) {
    /**
     * Public (root web) path.
     *
     * The full path to public folder.
     * By default it is the folder that contain this index.php file.
     */
    define('PUBLIC_PATH', __DIR__);
}

// Additional constants can be define here, below this line but before "Do not change.." line.
// End define constants ---------------------------------------------------------------------
// Do not change anything below this line.


// Require Composer autoload.
require ROOT_PATH . DIRECTORY_SEPARATOR . 'System' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';


$App = new \Rdb\System\App();
$App->run();
unset($App);
