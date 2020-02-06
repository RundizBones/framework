<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace System\Libraries;


/**
 * Log class that is working on monolog.
 *
 * @author mr.v
 */
class Logger
{


    /**
     * @var \System\Config
     */
    protected $Config;


    /**
     * @var \System\Container
     */
    protected $Container;


    /**
     * @var int Do not log the level is UNDER selected.
     *                  0=debug (or log it *ALL*), 1=info, 2=notice, 3=warning, 4=error, 5=critical, 6=alert, 7=emergency
     */
    protected $donotLogLevel = 0;


    /**
     * @var bool Log enabled or not? `true` = yes, `false` = no.
     */
    protected $logEnabled = false;


    /**
     * @var int Log level from `\Monolog\Logger::CONSTANT`.
     */
    protected $logLevel;


    /**
     * @var string The log level as readable name such as debug, info, etc.
     */
    protected $logLevelName;


    /**
     * Class constructor.
     * 
     * @param \System\Container $Container The DI container class.
     * @param array $options The options that will be override config file. 
     *                                      The options array keys must be the same as keys in side `log` in `app.php` config file.
     *                                      Example: `['enable' => true, 'donotLogLevel' => 1]`.
     */
    public function __construct(\System\Container $Container, array $options = [])
    {
        $this->Container = $Container;

        if ($Container->has('Config')) {
            $this->Config = $Container->get('Config');
            $this->Config->setModule('');
        } else {
            $this->Config = new \System\Config();
        }

        $options += $this->Config->get('log', 'app', []);

        if (is_array($options) && !empty($options)) {
            if (isset($options['enable']) && $options['enable'] === true) {
                // if enable logging.
                $this->logEnabled = true;
            } else {
                // if disable logging.
                $this->logEnabled = false;
                return ;
            }// endif enable

            if (isset($options['donotLogLevel'])) {
                $this->donotLogLevel = (int) $options['donotLogLevel'];
            }
        }// endif; $options
    }// __construct


    /**
     * Get Logger class with specified channel.
     * 
     * If log level is lower than `donotLogLevel` configuration then it will return null.
     * 
     * @param string $channel The channel name.
     * @param int $logLevel The log level as integer. 0=debug, 1=info, 2=notice, 3=warning, 4=error, 5=critical, 6=alert, 7=emergency
     * @return \Monolog\Logger|null Return `Logger` object or `null`.
     */
    protected function getLogger(string $channel, int $logLevel)
    {
        if ($this->logEnabled === false) {
            return null;
        }

        if ($logLevel < $this->donotLogLevel) {
            return null;
        }

        $this->setLogLevelNameAndNumber($logLevel);

        if (empty($channel)) {
            $channel = 'rundizbones';
        }

        return new \Monolog\Logger($channel);
    }// getLogger


    /**
     * Set log level name (readable such as debug, info, etc...) and number from `\Monolog\Logger::CONSTANT`.
     * 
     * @param int $logLevel The log level number.
     */
    protected function setLogLevelNameAndNumber(int $logLevel)
    {
        if ($logLevel === 1) {
            $this->logLevel = \Monolog\Logger::INFO;
            $this->logLevelName = 'info';
        } elseif ($logLevel === 2) {
            $this->logLevel = \Monolog\Logger::NOTICE;
            $this->logLevelName = 'notice';
        } elseif ($logLevel === 3) {
            $this->logLevel = \Monolog\Logger::WARNING;
            $this->logLevelName = 'warning';
        } elseif ($logLevel === 4) {
            $this->logLevel = \Monolog\Logger::ERROR;
            $this->logLevelName = 'error';
        } elseif ($logLevel === 5) {
            $this->logLevel = \Monolog\Logger::CRITICAL;
            $this->logLevelName = 'critical';
        } elseif ($logLevel === 6) {
            $this->logLevel = \Monolog\Logger::ALERT;
            $this->logLevelName = 'alert';
        } elseif ($logLevel === 7) {
            $this->logLevel = \Monolog\Logger::EMERGENCY;
            $this->logLevelName = 'emergency';
        } else {
            $this->logLevel = \Monolog\Logger::DEBUG;
            $this->logLevelName = 'debug';
        }
    }// setLogLevelNameAndNumber


    /**
     * Write the message to log file.
     * 
     * @param string $channel The channel such as 'security', 'db', or other. Leave blank to use default channel.
     * @param int $logLevel The log level as integer. 0=debug, 1=info, 2=notice, 3=warning, 4=error, 5=critical, 6=alert, 7=emergency
     * @param string $message The message that will be write to log file. See https://github.com/Seldaek/monolog/blob/master/doc/message-structure.md for more information.
     * @param array $context The array data that will be passed with the message or it can be just additional data.
     * @param array $options The options for this method in associative array.
     *                                      Available options: `dontLogProfiler` (bool).
     * @return bool Return `true` or `false` wether the record has been processed.
     */
    public function write(string $channel, int $logLevel, string $message, array $context = [], array $options = []): bool
    {
        if ($this->logEnabled === false) {
            return false;
        }

        $Logger = $this->getLogger($channel, $logLevel);
        if ($Logger === null) {
            return false;
        }
        // get channel from where it was set to `Logger` class.
        $channel = $Logger->getName();

        // works with profiler to display log. -------------------------------------------
        if (
            $this->Container->has('Profiler') &&
            (
                !isset($options['dontLogProfiler']) ||
                (
                    isset($options['dontLogProfiler']) &&
                    $options['dontLogProfiler'] === false
                )
            )
        ) {
            // get caller file and line.
            $traces = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
            if (is_array($traces) && isset($traces[1]['file']) && isset($traces[1]['line'])) {
                $traceFile = $traces[0]['file'];
                $traceLine = $traces[0]['line'];
            } else {
                $traceFile = '';
                $traceLine = '';
            }
            unset($traces);

            $profilerLogMessage = 'logger: ' . $channel . ': ' . $message;
            if (!empty($context)) {
                $profilerLogMessage = [];
                $profilerLogMessage['channel'] = $channel;

                // make message replaced with context as same as visible in Monolog.
                $replace = [];
                foreach ($context as $key => $val) {
                    // check that the value can be casted to string
                    if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                        $replace['{' . $key . '}'] = $val;
                    }
                }// endforeach;
                unset($key, $val);

                $profilerLogMessage['message'] = strtr($message, $replace);
                unset($replace);

                $profilerLogMessage['context'] = $context;
            }// endif; $context

            // write the message to profiler log for display it.
            /* @var $Profiler \System\Libraries\Profiler */
            $Profiler = $this->Container->get('Profiler');
            $Profiler->Console->log($this->logLevelName, $profilerLogMessage, $traceFile, $traceLine);
            unset($profilerLogMessage, $traceFile, $traceLine);
        }// endif; $Profiler
        // end works with profiler. ------------------------------------------------------

        $logFile = STORAGE_PATH . '/logs/' . $this->logLevelName . '/' . $channel . '/' . date('Y-m-d') . '.log';
        $Logger->pushHandler(new \Monolog\Handler\StreamHandler($logFile, $this->logLevel));
        unset($logFile);
        $Logger->pushProcessor(new \Monolog\Processor\IntrospectionProcessor(\Monolog\Logger::DEBUG, ['System\\Libraries\\Db\\Logger', 'System\\Libraries\\Db\\Statement', 'System\\Libraries\\Logger']));
        $Logger->pushProcessor(new \Monolog\Processor\PsrLogMessageProcessor());

        if (strtolower(PHP_SAPI) !== 'cli') {
            $Logger->pushProcessor(new \Monolog\Processor\WebProcessor());
        }

        return $Logger->addRecord($this->logLevel, $message, $context);
    }// write


}
