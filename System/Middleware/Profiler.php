<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\System\Middleware;


/**
 * Profiler or benchmark the application.
 * 
 * This class is running on Rundiz\Profiler.
 *
 * @since 0.1
 */
class Profiler
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
     * The class constructor.
     * 
     * @param \Rdb\System\Container $Container The DI container class.
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        $this->Container = $Container;
    }// __construct


    /**
     * Append profiler to html body.
     * 
     * @param string|null $response
     * @return string|null
     */
    protected function appendProfiler($response = '')
    {
        /* @var $Profiler \Rdb\System\Libraries\Profiler */
        $Profiler = $this->Container['Profiler'];
        $displayResult = $Profiler->display($this->Container['Db'], [$this, 'displayDb']);

        if (stripos($response, '</body>') !== false) {
            $response = str_replace('</body>', "\n\n<!-- profiler begins -->" . $displayResult . "<!-- profiler end -->\n\n\n" . '    </body>', $response);
        } else {
            $response .= "\n\n<!-- profiler begins -->" . $displayResult . "<!-- profiler end -->\n\n";
        }

        $profileTimeLoad = $Profiler->getReadableTime(
            ($Profiler->getMicrotime() - $Profiler->getMicrotime(true))*1000
        );
        $response .= "\n\n" . '<!-- Time from begins until profiler generated is ' . $profileTimeLoad . '-->' . "\n";
        $response .= '<script>console.log(\'Time from begins until profiler generated is ' . $profileTimeLoad . '\');</script>'."\n\n";

        unset($displayResult);

        return $response;
    }// appendProfiler


    /**
     * Display Profiler DB.<br>
     * This part is not accessible by any URL. it can be used via `$Profiler->display()` only. So, it must be public scope.
     */
    public function displayDb()
    {
        /* @var $Dbh \Rdb\System\Libraries\Db */
        /* @var $Profiler \Rdb\System\Libraries\Profiler */
        list($Profiler, $Dbh, $dataValues) = func_get_args();

        if ($Dbh == null) {
            return ;
        }

        if (is_array($dataValues)) {
            if (array_key_exists('time_start', $dataValues) && array_key_exists('time_end', $dataValues)) {
                echo '<div class="rdprofiler-log-db-timetake">' . PHP_EOL;
                echo $Profiler->getReadableTime(($dataValues['time_end']-$dataValues['time_start'])*1000);
                echo '</div>' . PHP_EOL;
            }

            if (array_key_exists('memory_end', $dataValues) && array_key_exists('memory_start', $dataValues) && is_int($dataValues['memory_end']) && is_int($dataValues['memory_start'])) {
                echo '<div class="rdprofiler-log-memory">';
                echo $Profiler->getReadableFileSize($dataValues['memory_end']-$dataValues['memory_start']);
                echo '</div>' . PHP_EOL;
            }
        }

        if (strpos($dataValues['data'], ';') !== false) {
            // prevent sql injection! example: SELECT * FROM table where username = 'john'; DROP TABLE table;' this can execute 2 queries. explode them and just get the first!
            $expData = explode(';', str_replace('; ', ';', $dataValues['data']));
            $dataValues['data'] = $expData[0];
        }

        // use try ... catch to prevent any error by EXPLAIN. Example: EXPLAIN SHOW CHARACTER SET; <-- this will throw errors!
        // make sure that PDO options in config file is using exception mode.
        try {
            $Stmt = $Dbh->PDO($Dbh->currentConnectionKey())->prepare('EXPLAIN '.$dataValues['data']);
            $Stmt->execute();
            if ($Stmt) {
                echo '<div class="rdprofiler-log-newrow">' . PHP_EOL;
                echo '<div class="rdprofiler-log-db-explain">' . PHP_EOL;
                if (isset($expData) && is_array($expData)) {
                    foreach ($expData as $key => $sqldata) {
                        if ($key != 0 && !empty($sqldata)) {
                            echo htmlspecialchars($sqldata, ENT_QUOTES).' cannot be explain due to it might be SQL injection!<br>' . PHP_EOL;
                        }
                    }// endforeach;
                    unset($key, $sqldata);
                }
                $result = $Stmt->fetchAll();
                $Stmt->closeCursor();
                if ($result) {
                    foreach ($result as $row) {
                        if (is_array($row) || is_object($row)) {
                            foreach ($row as $key => $val) {
                                echo $key . ' = ' . $val;
                                if (end($result) != $val) {
                                    echo ', ';
                                }
                            }// endforeach;
                        }
                        echo '<br>' . PHP_EOL;
                    }// endforeach;
                }
                unset($key, $result, $row, $val);
                echo '</div>' . PHP_EOL;
                echo '</div>' . PHP_EOL;
            }
            unset($Stmt);
        } catch (\Exception $e) {
            echo '<div class="rdprofiler-log-newrow">' . PHP_EOL;
            echo '<div class="rdprofiler-log-db-explain">' . PHP_EOL;
            echo '<!-- ' . $e->getMessage() . ' -->' . PHP_EOL;
            echo '</div>' . PHP_EOL;
            echo '</div>' . PHP_EOL;
        }
        unset($expData);

        if (is_array($dataValues) && array_key_exists('call_trace', $dataValues)) {
            echo '<div class="rdprofiler-log-newrow">' . PHP_EOL;
            echo '<div class="rdprofiler-log-db-trace">' . PHP_EOL;
            echo '<strong>Call trace:</strong><br>' . PHP_EOL;
            foreach ($dataValues['call_trace'] as $traceItem) {
                echo $traceItem['file'].', line '.$traceItem['line'].'<br>' . PHP_EOL;
            }
            unset($traceItem);
            echo '</div>' . PHP_EOL;
            echo '</div>' . PHP_EOL;
        }

        unset($dataValues, $Dbh, $Profiler);
    }// displayDb


    /**
     * End profiler.
     * 
     * @param string|null $response
     * @return string|null
     */
    public function end($response = '')
    {
        if ($this->Container->has('Profiler')) {
            // check that the profiler should be display or not?
            $donotDisplayProfiler = false;// false = display it!

            $httpAccept = ($_SERVER['HTTP_ACCEPT'] ?? '*/*');
            if (stripos($httpAccept, 'text/html') === false) {
                // if request header did not accept text/html then do not display profiler.
                $donotDisplayProfiler = true;
            }
            unset($httpAccept);
            
            $responseHeaders = headers_list();
            if (is_array($responseHeaders)) {
                foreach ($responseHeaders as $header) {
                    if (stripos($header, 'content-type:') !== false && stripos($header, 'text/html') === false) {
                        // if found content-type but it is not text/html then do not display profiler.
                        $donotDisplayProfiler = true;
                    }
                }// endforeach;
                unset($header);
            }
            unset($responseHeaders);

            $requestHeaders = apache_request_headers();
            if (is_array($requestHeaders)) {
                $requestHeaders = array_change_key_case($requestHeaders);
                if (
                    array_key_exists('rundizbones-no-profiler', $requestHeaders) && 
                    $requestHeaders['rundizbones-no-profiler'] === 'true'
                ) {
                    // if there is request header named 'rundizbones-no-profiler' then do not display profiler.
                    $donotDisplayProfiler = true;
                }
            }
            unset($requestHeaders);

            if (
                isset($_SERVER['RUNDIZBONES_SUBREQUEST']) && 
                $_SERVER['RUNDIZBONES_SUBREQUEST'] === true
            ) {
                // if it is rundizbones sub request then do not display profiler.
                $donotDisplayProfiler = true;
            }

            if (isset($_REQUEST['rundizbones-no-profiler']) && $_REQUEST['rundizbones-no-profiler'] === 'true') {
                // if there is GET or POST parameter named 'rundizbones-no-profiler' then do not display profiler.
                $donotDisplayProfiler = true;
            }

            /* @var $Profiler \Rdb\System\Libraries\Profiler */
            $Profiler = $this->Container->get('Profiler');
            $Profiler->Console->timeload('Profiler end (after middleware).', __FILE__, __LINE__, 'rdb_profiler_middleware');
            $Profiler->Console->memoryUsage('Profiler end (after middleware).', __FILE__, (__LINE__ - 1), 'rdb_profiler_middleware');

            if (isset($donotDisplayProfiler) && $donotDisplayProfiler === false) {
                // if allowed to display profiler.
                $response = $this->appendProfiler($response);
            }
        }

        return $response;
    }// end


    /**
     * Initialize the profiler.
     * 
     * @param string|null $response
     * @return string|null
     */
    public function init($response = '')
    {
        if (strtolower(PHP_SAPI) === 'cli') {
            // If running from CLI.
            // don't run this middleware here.
            return $response;
        }

        if ($this->Container->has('Profiler')) {
            return $response;
        }

        if ($this->Container->has('Config')) {
            $this->Config = $this->Container->get('Config');
            $this->Config->setModule('');
        } else {
            $this->Config = new \Rdb\System\Config();
        }

        $this->Config->load('app');
        if ($this->Config->get('profiler', 'app', false) === true) {
            // if config enabled for profiler and class exists.
            $Profiler = new \Rdb\System\Libraries\Profiler();
            $Profiler->minifyHtml = true;
            $Profiler->Console->registerLogSections($this->Config->get('profilerSections', 'app', ['Logs']));
            $Profiler->Console->timeload('Profiler started (before middleware).', __FILE__, __LINE__, 'rdb_profiler_middleware');
            $Profiler->Console->memoryUsage('Profiler started (before middleware).', __FILE__, (__LINE__ - 1), 'rdb_profiler_middleware');
            $this->Container['Profiler'] = function ($c) use ($Profiler) {
                return $Profiler;
            };
        }

        return $response;
    }// init


}
