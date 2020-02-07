<?php
/** 
 * Database configuration.
 * 
 * @license http://opensource.org/licenses/MIT MIT
 * @link https://www.php.net/manual/en/pdo.construct.php PDO class constructor document.
 * @link https://www.php.net/manual/en/pdo.constants.php PDO constant that maybe use with the options.
 * @link https://stackoverflow.com/questions/3328794/is-there-a-performance-difference-between-pdo-fetch-statements Benchmark fetch modes.
 */


/* @var $Container \Rdb\System\Container */
// The global variable `$container` was declare in `\Rdb\System\Libraries\Db::connect()` method.
// This is required to use with DB logger class (`\Rdb\System\Libraries\Db\Logger()`).
global $Container;

return [
    0 => [
        // DSN config for PDO. Example: `mysql:host=127.0.0.1;port=3306;dbname=your_db_name;charset=UTF8`.
        // The charset can be utf8 or utf8mb4 but if your server supported, please use utf8mb4.
        'dsn' => 'mysql:host=127.0.0.1;dbname=;charset=utf8mb4',
        // Your database username.
        'username' => '',
        // Your database password.
        'passwd' => '',
        // PDO options.
        'options' => [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_SILENT,// use `\PDO::ERRMODE_SILENT` for production, `\PDO::ERRMODE_EXCEPTION` for development. (use warning cannot work with try..catch).
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
            \PDO::ATTR_STATEMENT_CLASS => ['\\Rdb\\System\\Libraries\\Db\\Statement', [$Container]],
        ],
        // Table prefix.
        'tablePrefix' => '',
    ],// This 0 array key (connection key) is required and always auto start if configured properly.
    // To set additional DB config for another connection to other database, set it as another array key (connection key).
    // Below is an example.
    /*'anotherDb' => [
        'dsn' => 'mysql:host=127.0.0.1;dbname=my_another_db;charset=utf8mb4',
        'username' => 'root',
        'passwd' => '1234',
        'options' => [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_SILENT,// use `\PDO::ERRMODE_SILENT` for production, `\PDO::ERRMODE_EXCEPTION` for development. (use warning cannot work with try..catch).
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
            \PDO::ATTR_STATEMENT_CLASS => ['\\Rdb\\System\\Libraries\\Db\\Statement', [$Container]],
        ],
        'tablePrefix' => 'prefix_',
    ],*/
];