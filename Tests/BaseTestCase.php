<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Tests;


class BaseTestCase extends \PHPUnit\Framework\TestCase
{


    /**
     * @var \Rdb\System\Container;
     */
    protected $Container;


    /**
     * @var \Rdb\System\App
     */
    protected $RdbApp;


    protected function runApp(string $method, string $url, array $cookies = [], array $additionalData = [])
    {
        $_SERVER['RUNDIZBONES_TESTS'] = 'true';
        $_SERVER['REQUEST_METHOD'] = strtoupper($method);

        $parseUrl = parse_url($url);
        if (isset($parseUrl['scheme']) && $parseUrl['scheme'] === 'https') {
            $_SERVER['HTTPS'] = 'on';
        }
        if (isset($parseUrl['host'])) {
            $_SERVER['HTTP_HOST'] = $parseUrl['host'];
        }
        if (isset($parseUrl['path'])) {
            $_SERVER['REQUEST_URI'] = $parseUrl['path'] . (isset($parseUrl['query']) ? '?' . $parseUrl['query'] : '');
        }
        if (isset($parseUrl['query'])) {
            $_SERVER['QUERY_STRING'] = $parseUrl['query'];
            if (!empty($parseUrl['query'])) {
                parse_str($parseUrl['query'], $_GET);
            }
        } else {
            $_SERVER['QUERY_STRING'] = '';
        }
        unset($parseUrl);

        // below is copied top part of public/index.php ----------------------------
        if (!defined('APP_ENV')) {
            define('APP_ENV', 'development');
        }
        if (!defined('ROOT_PATH')) {
            define('ROOT_PATH', dirname(__DIR__));
        }
        if (!defined('STORAGE_PATH')) {
            define('STORAGE_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'storage');
        }
        if (!defined('MODULE_PATH')) {
            define('MODULE_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'Modules');
        }
        if (!defined('PUBLIC_PATH')) {
            define('PUBLIC_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'public');
        }
        // end top part of public/index.php ------------------------------------------

        if (!empty($cookies)) {
            $_COOKIE = $cookies;
        }

        if (!empty($additionalData)) {
            if (isset($additionalData['POST'])) {
                $_POST = $additionalData['POST'];
            }
        }

        $_REQUEST = array_merge($_POST, $_GET);

        if (!defined('RDB_TEST_PATH')) {
            define('RDB_TEST_PATH', __DIR__);
        }

        // below is copied bottom part of public/index.php --------------------------
        $App = new \Rdb\System\App();
        $this->RdbApp = $App;
        unset($App);
        // end bottom part of public/index.php ----------------------------------------
    }// runApp


}
