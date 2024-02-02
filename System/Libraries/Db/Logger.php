<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\System\Libraries\Db;


/**
 * DB Logger class.
 * 
 * @since 0.1
 * @link https://stackoverflow.com/a/7716896/128761 Copied the ideas and source code from here.
 */
class Logger
{


    /**
     * @var \Rdb\System\Container
     */
    protected $Container;


    /**
     * Class constructor.
     * 
     * @param \Rdb\System\Container $Container The DI container class.
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        $this->Container = $Container;
    }// __construct


    /**
     * Log the DB statement query.
     * 
     * @param string $statement The DB statement.
     * @param mixed $inputParams The input parameters from `bindXXX()`, `execute()`. This value can be `null`.
     * @param mixed $inputDataTypes The input data type from `bindXXX()`. This value can be `null`.
     * @return void|null Return nothing (`void`) or maybe return `null` if config was set to do not write log and profiler is disabled.
     */
    public function queryLog(string $statement, $inputParams = null, $inputDataTypes = null)
    {
        $originalStatement = $statement;

        if ($this->Container->has('Config')) {
            /* @var $Config \Rdb\System\Config */
            $Config = $this->Container->get('Config');
            $Config->setModule('');
            $configLog = $Config->get('log', 'app', []);
            if (!isset($configLog['enable']) || isset($configLog['enable']) && $configLog['enable'] === false) {
                $dontWriteLog = true;
            }
            unset($Config, $configLog);
        }

        if ($this->Container->has('Profiler')) {
            /* @var $Profiler \Rdb\System\Libraries\Profiler */
            $Profiler = $this->Container->get('Profiler');
        }

        if (isset($dontWriteLog) && $dontWriteLog === true && !isset($Profiler)) {
            // if config was set to do not write log and profiler is not enabled.
            // no need to do anything here.
            return ;
        }

        // the `$inputParams` can be called via `bindParam()` which there is reference variable.
        // so, copy and disable its reference to prevent the mess with real `\PDO` class.
        // copied this code from https://stackoverflow.com/a/186008/128761
        $cloneParams = unserialize(serialize($inputParams));

        // starting to replace placeholder to its value. ------------------------------------------------------------------
        // normalize `$cloneParams` to begins the key with `:`.
        // example: `['param1' => 'val1', ':param2' => 'val2']` will be `[':param1' => 'val1', ':param2' => 'val2']`
        if (is_array($cloneParams)) {
            foreach ($cloneParams as $key => $val) {
                if (is_string($key)) {
                    unset($cloneParams[$key]);
                    $key = ':' . ltrim($key, ':');
                    $cloneParams[$key] = $val;
                }
            }// endforeach;
            unset($key, $val);
        }

        $keys = [];
        $values = $cloneParams;
        $valuesLimit = [];

        preg_match_all('/:\b([\w\d\-_]+)\b/', $statement, $matches);
        if (isset($matches[0])) {
            $wordsRepeated = array_count_values($matches[0]);
        } else {
            $wordsRepeated = [];
        }

        if (is_array($cloneParams)) {
            foreach ($cloneParams as $key => $val) {
                if (is_string($key)) {
                    $key = ltrim($key, ':');
                    $keys[] = '/:' . $key . '/';
                    $key = ':' . $key;
                    $valuesLimit[$key] = (isset($wordsRepeated[$key]) ? intval($wordsRepeated[$key]) : 1);
                } else {
                    $keys[] = '/[?]/';
                    $valuesLimit = [];
                }

                if (is_string($val)) {
                    if (
                        is_array($inputDataTypes) &&
                        array_key_exists($key, $inputDataTypes) && 
                        $inputDataTypes[$key] === \PDO::PARAM_INT
                    ) {
                        $values[$key] = $val;
                    } else {
                        $values[$key] = '\'' . $val . '\'';
                    }
                }
                if (is_array($val)) {
                    $values[$key] = "'" . implode("','", $val) . "'";
                }
                if (is_null($val)) {
                    $values[$key] = 'NULL';
                }
            }// endforeach;
            unset($key, $val);
        }

        $ArrayUtil = new \Rdb\System\Libraries\ArrayUtil();
        if (is_array($values) && $ArrayUtil->isAssoc($values)) {
            // if values is array and it is associative array.
            foreach ($values as $key => $val) {
                if (is_scalar($key) && is_scalar($val)) {
                    if (isset($valuesLimit[$key])) {
                        $statement = preg_replace(['/'. str_replace(':', ':\b', $key) .'\b/'], [$val], $statement, $valuesLimit[$key], $count);
                    } else {
                        $statement = preg_replace(['/'. str_replace(':', ':\b', $key) .'\b/'], [$val], $statement, 1, $count);
                    }
                }
            }
            unset($key, $val);
        } else {
            if (is_string($values) || is_array($values)) {
                $statement = preg_replace($keys, $values, $statement, 1, $count);
            }
        }
        unset($ArrayUtil, $keys, $values, $valuesLimit, $wordsRepeated);

        if (strpos($statement, ';') === false) {
            $statement .= ';';
        }
        // end replace placeholder. ---------------------------------------------------------------------------------------

        if (isset($Profiler)) {
            $Profiler->Console->database($statement, $Profiler->getMicrotime(), memory_get_usage());
            unset($Profiler);
        }

        if (!isset($dontWriteLog) || (isset($dontWriteLog) && $dontWriteLog === false)) {
            // if config was set to write log.
            if ($this->Container->has('Logger')) {
                /* @var $Logger \Rdb\System\Libraries\Logger */
                $Logger = $this->Container['Logger'];
                $Logger->write('system/libraries/db/logger', 0, 'Original SQL statement: {originalStatement}', ['originalStatement' => $originalStatement], ['dontLogProfiler' => true]);
                $Logger->write('system/libraries/db/logger', 0, $statement, ($cloneParams !== null ? ['params' => $cloneParams, 'dataType' => $inputDataTypes] : []), ['dontLogProfiler' => true]);
                unset($Logger);
            }
        }
        unset($originalStatement);
    }// queryLog


}
