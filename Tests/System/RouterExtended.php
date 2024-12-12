<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Tests\System;


class RouterExtended extends \Rdb\System\Router
{


    public function __construct(?\Rdb\System\Container $Container = null)
    {
        if ($Container === null) {
            $Container = new \Rdb\System\Container();
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
