<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace System\Core\Console;


use Symfony\Component\Console\Command\Command;


/**
 * Base console (CLI) class.
 * 
 * @since 0.1
 */
class BaseConsole extends Command
{


    /**
     * @var \System\Container
     */
    protected $Container;


    /**
     * Class constructor.
     * 
     * @param \System\Container $Container The DI container class.
     */
    public function __construct($name = null, \System\Container $Container = null)
    {
        parent::__construct($name);

        if ($Container instanceof \System\Container) {
            $this->Container = $Container;
        }
    }// __construct


}
