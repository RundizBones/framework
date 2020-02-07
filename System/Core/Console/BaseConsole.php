<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\System\Core\Console;


use Symfony\Component\Console\Command\Command;


/**
 * Base console (CLI) class.
 * 
 * @since 0.1
 */
class BaseConsole extends Command
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
    public function __construct($name = null, \Rdb\System\Container $Container = null)
    {
        parent::__construct($name);

        if ($Container instanceof \Rdb\System\Container) {
            $this->Container = $Container;
        }
    }// __construct


}
