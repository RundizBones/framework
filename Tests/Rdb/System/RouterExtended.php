<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Tests\Rdb\System;


class RouterExtended extends \System\Router
{


    public function __construct(\System\Container $Container = null)
    {
        if ($Container === null) {
            $Container = new \System\Container();
        }

        parent::__construct($Container);
    }// __construct


    /**
     * {@inheritdoc}
     */
    public function filterMethod($method)
    {
        return parent::filterMethod($method);
    }// filterMethod


}
