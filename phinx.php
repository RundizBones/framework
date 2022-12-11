<?php
/**
 * Phinx configuration for the RundizBones framework.
 * 
 * DO NOT set Database configuration here. Set it in the config folder of the framework instead.
 * 
 * @package RundizBones
 * @since 0.1
 * @license http://opensource.org/licenses/MIT MIT
 */


// get configuration from the framework. ---------------------------------------------------------------
// find index file.
$glob_results = glob(__DIR__.'/**/*', GLOB_NOSORT);
if (is_array($glob_results)) {
    foreach ($glob_results as $file) {
        $file = realpath($file);
        if (strpos($file, 'index.php') !== false) {
            $indexFile = $file;
            break;
        }
    }// endforeach;
    unset($file);
}
unset($glob_results);
if (!isset($indexFile)) {
    $glob_results = glob(__DIR__.'/*', GLOB_NOSORT);
    if (is_array($glob_results)) {
        foreach ($glob_results as $file) {
            $file = realpath($file);
            if (strpos($file, 'index.php') !== false) {
                $indexFile = $file;
                break;
            }
        }// endforeach;
        unset($file);
    }
    unset($glob_results);
}
// get index contents.
if (isset($indexFile)) {
    $indexContent = file_get_contents($indexFile);
    preg_match('#APP_ENV([\'"])(\s+)?,(\s+)?([\'"])(.+)([\'"])#iu', $indexContent, $matches);
    unset($indexContent);
}
// get environment.
if (isset($matches[5])) {
    $appEnv = $matches[5];
} else {
    $appEnv = 'production';
}
unset($matches);
// get configurations.
$rootPath = __DIR__;
if (is_file($rootPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'development' . DIRECTORY_SEPARATOR . 'db.php')) {
    $dbFile = $rootPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'development' . DIRECTORY_SEPARATOR . 'db.php';
    $devDbConfig = require $dbFile;
    $devDriver = rdbGetDriverFromDSN($devDbConfig[0]['dsn']);
    $devDbHost = rdbGetDataFromDSN($devDbConfig[0]['dsn'], 'host', 'localhost');
    $devDbPort = rdbGetDataFromDSN($devDbConfig[0]['dsn'], 'port', '3306');
    $devDbname = rdbGetDataFromDSN($devDbConfig[0]['dsn'], 'dbname');
    $devDbCharset = rdbGetDataFromDSN($devDbConfig[0]['dsn'], 'charset', 'utf-8');
    $devDbUsername = $devDbConfig[0]['username'];
    $devDbPassword = $devDbConfig[0]['passwd'];
    $devTablePrefix = $devDbConfig[0]['tablePrefix'];
    unset($dbFile, $devDbConfig);
}
if (is_file($rootPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'production' . DIRECTORY_SEPARATOR . 'db.php')) {
    $dbFile = $rootPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'production' . DIRECTORY_SEPARATOR . 'db.php';
    $prodDbConfig = require $dbFile;
    $prodDriver = rdbGetDriverFromDSN($prodDbConfig[0]['dsn']);
    $prodDbHost = rdbGetDataFromDSN($prodDbConfig[0]['dsn'], 'host', 'localhost');
    $prodDbPort = rdbGetDataFromDSN($prodDbConfig[0]['dsn'], 'port', '3306');
    $prodDbname = rdbGetDataFromDSN($prodDbConfig[0]['dsn'], 'dbname');
    $prodDbCharset = rdbGetDataFromDSN($prodDbConfig[0]['dsn'], 'charset', 'utf-8');
    $prodDbUsername = $prodDbConfig[0]['username'];
    $prodDbPassword = $prodDbConfig[0]['passwd'];
    $prodTablePrefix = $prodDbConfig[0]['tablePrefix'];
    unset($dbFile, $prodDbConfig);
}
if (is_file($rootPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'db.php')) {
    $dbFile = $rootPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'db.php';
    $defaultDbConfig = require $dbFile;
    $defaultDriver = rdbGetDriverFromDSN($defaultDbConfig[0]['dsn']);
    $defaultDbHost = rdbGetDataFromDSN($defaultDbConfig[0]['dsn'], 'host', 'localhost');
    $defaultDbPort = rdbGetDataFromDSN($defaultDbConfig[0]['dsn'], 'port', '3306');
    $defaultDbname = rdbGetDataFromDSN($defaultDbConfig[0]['dsn'], 'dbname');
    $defaultDbCharset = rdbGetDataFromDSN($defaultDbConfig[0]['dsn'], 'charset', 'utf8');
    $defaultDbUsername = $defaultDbConfig[0]['username'];
    $defaultDbPassword = $defaultDbConfig[0]['passwd'];
    $defaultTablePrefix = $defaultDbConfig[0]['tablePrefix'];
    unset($dbFile, $defaultDbConfig);
}
unset($rootPath);
function rdbGetDataFromDSN(string $dsn, string $name, string $default = '')
{
    $dsnExp = explode(':', $dsn);
    if (count($dsnExp) == 2) {
        list($driver, $dsnValues) = $dsnExp;
        $dsnValuesExp = explode(';', (is_scalar($dsnValues) ? $dsnValues : ''));
        if (is_array($dsnValuesExp)) {
            foreach ($dsnValuesExp as $dsnItem) {
                if (is_scalar($dsnItem) && stripos($dsnItem, $name) !== false) {
                    $dsnItemExp = explode('=', $dsnItem);
                    return (isset($dsnItemExp[1]) ? trim($dsnItemExp[1]) : $default);
                }
            }// endforeach;
            unset($dsnItem);
        }
        unset($driver, $dsnValuesExp);
    }
    unset($dsnExp);
    return $default;
}
function rdbGetDriverFromDSN(string $dsn, string $default = 'mysql')
{
    $dsnExp = explode(':', $dsn);
    if (count($dsnExp) == 2) {
        list($driver, $dsnValues) = $dsnExp;
        unset($dsnExp, $dsnValues);
        if (!empty($driver)) {
            return $driver;
        }
        unset($driver, $dsnValues);
    }
    unset($dsnExp);
    return $default;
}

// end get configuration from the framework. ----------------------------------------------------------


return
[
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/Modules/*/phinxdb/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/Modules/*/phinxdb/seeds'
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_database' => $appEnv,// for phinx 0.11.x renamed to `default_environment` since 0.12.0
        'default_environment' => $appEnv,// since phinx 0.12.0
        'production' => [
            'adapter' => ($prodDriver ?? ($defaultDriver ?? 'mysql')),
            'host' => ($prodDbHost ?? ($defaultDbHost ?? 'localhost')),
            'name' => ($prodDbname ?? ($defaultDbname ?? '')),
            'user' => ($prodDbUsername ?? ($defaultDbUsername ?? '')),
            'pass' => ($prodDbPassword ?? ($defaultDbPassword ?? '')),
            'port' => ($prodDbPort ?? ($defaultDbPort ?? '3306')),
            'charset' => ($prodDbCharset ?? ($defaultDbCharset ?? 'utf8')),
            'table_prefix' => ($prodTablePrefix ?? ($defaultTablePrefix ?? '')),
        ],
        'development' => [
            'adapter' => ($devDriver ?? ($defaultDriver ?? 'mysql')),
            'host' => ($devDbHost ?? ($defaultDbHost ?? 'localhost')),
            'name' => ($devDbname ?? ($defaultDbname ?? '')),
            'user' => ($devDbUsername ?? ($defaultDbUsername ?? '')),
            'pass' => ($devDbPassword ?? ($defaultDbPassword ?? '')),
            'port' => ($devDbPort ?? ($defaultDbPort ?? '3306')),
            'charset' => ($devDbCharset ?? ($defaultDbCharset ?? 'utf8')),
            'table_prefix' => ($devTablePrefix ?? ($defaultTablePrefix ?? '')),
        ],
        'testing' => [
            'adapter' => ($devDriver ?? ($defaultDriver ?? 'mysql')),
            'host' => ($devDbHost ?? ($defaultDbHost ?? 'localhost')),
            'name' => ($devDbname ?? ($defaultDbname ?? '')),
            'user' => ($devDbUsername ?? ($defaultDbUsername ?? '')),
            'pass' => ($devDbPassword ?? ($defaultDbPassword ?? '')),
            'port' => ($devDbPort ?? ($defaultDbPort ?? '3306')),
            'charset' => ($devDbCharset ?? ($defaultDbCharset ?? 'utf8')),
            'table_prefix' => ($devTablePrefix ?? ($defaultTablePrefix ?? '')),
        ]
    ],
    'version_order' => 'creation'
];