<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\System\Middleware;


/**
 * Error handler for the framework.
 * 
 * These will be depend on configuration in **config/[environment]/app.php**.  
 * If the configuration 'log.enable' was not set to `true` then it will be use `error_log` on php.ini setting.
 * If the configuration 'log.enable' was set to `true` then it will be set PHP error log path to inside storage/logs.
 * 
 * If the configuration 'log.donotLogLevel' was set then the it will be check for current error level must be matched and then set the ini `log_errors` to `true` or `false` depend on matched or not.
 * If the configuration 'log.donotLogLevel' was not set then it will be use `log_errors` on php.ini setting.
 *
 * @since 1.1.7
 */
class ErrorHandler
{


    /**
     * @var \Rdb\System\Config
     */
    protected $Config;


    /**
     * @var \Rdb\System\Container
     */
    protected $Container;


    /**
     * @var int Do not log when log level is UNDER selected. (read more in config/default/app.php.)
     */
    protected $donotLogLevel;


    /**
     * The class constructor.
     * 
     * @param \Rdb\System\Container $Container The DI container class.
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        $this->Container = $Container;

        if ($Container->has('Config')) {
            $this->Config = $Container->get('Config');
            $this->Config->setModule('');
        } else {
            $this->Config = new \Rdb\System\Config();
        }
    }// __construct


    /**
     * Change `log_errors` in the php.ini depend on `log.donotLogLevel` in config/[environment]/app.php file.
     * 
     * @link https://www.php.net/manual/en/function.set-error-handler.php Document.
     * @param int $errno The first parameter, `errno`, will be passed the level of the error raised, as an integer.
     * @param string $errstr The second parameter, `errstr`, will be passed the error message, as a string.
     * @param string|null $errfile If the callback accepts a third parameter, `errfile`, it will be passed the filename that the error was raised in, as a string.
     * @param int|null $errline If the callback accepts a fourth parameter, `errline`, it will be passed the line number where the error was raised, as an integer.
     * @return bool If the function returns `false` then the normal error handler continues.
     */
    public function changeLogErrorsIniSetting(int $errno, string $errstr, ?string $errfile, ?int$errline)
    {
        /* @var $eqFwDonotLogLevel int Equivalent framework's do not log. */
        $eqFwDonotLogLevel = 0;

        switch ($errno) {
            case E_STRICT:
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                $eqFwDonotLogLevel = 1;
                break;
            case E_NOTICE:
            case E_USER_NOTICE:
                $eqFwDonotLogLevel = 2;
                break;
            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_USER_WARNING:
                $eqFwDonotLogLevel = 3;
                break;
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
            case E_RECOVERABLE_ERROR:
                $eqFwDonotLogLevel = 4;
                break;
            case E_PARSE:
                $eqFwDonotLogLevel = 5;
                break;
        }

        if (isset($eqFwDonotLogLevel) && $this->donotLogLevel <= $eqFwDonotLogLevel) {
            ini_set('log_errors', true);
        } else {
            ini_set('log_errors', false);
        }

        unset($eqFwDonotLogLevel);
        return false;
    }// changeLogErrorsIniSetting


    /**
     * Initialize the error handler.
     * 
     * Please read the class doc block.
     * 
     * @param string|null $response
     * @return string|null
     */
    public function init($response = '')
    {
        $configLog = $this->Config->get('log', 'app', []);

        if (isset($configLog['enable']) && $configLog['enable'] === true) {
            // if config is set to enable log.
            $logPath = STORAGE_PATH . '/logs/php_error.log';
            ini_set('error_log', $logPath);
            unset($logPath);
        }

        if (isset($configLog['donotLogLevel']) && is_numeric($configLog['donotLogLevel'])) {
            // if there is config about do not log level.
            $this->donotLogLevel = intval($configLog['donotLogLevel']);
            set_error_handler([$this, 'changeLogErrorsIniSetting']);
        }

        unset($configLog);
        return $response;
    }// init


}
