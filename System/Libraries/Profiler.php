<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\System\Libraries;


/**
 * Profiler class that extends Rundiz\Profiler\Profiler.
 * 
 * @since 0.1
 */
class Profiler extends \Rundiz\Profiler\Profiler
{


    /**
     * Profiler class constructor.
     * 
     * You maybe load this class via framework's `Container` object named `Profiler`. Example: `$Profiler = $Container->get('Profiler');`.<br>
     * This is depended on if `Profiler` middleware is loaded or not.
     */
    public function __construct()
    {
        parent::__construct();
    }// __construct


}
