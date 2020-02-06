<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Tests\Rdb\System;


class ViewsTest extends \Tests\Rdb\BaseTestCase
{


    public function testRender()
    {
        $Modules = new \System\Modules(new \System\Container());
        $Modules->setCurrentModule('System\\Core');

        $Container = new \System\Container();
        $Container['Modules'] = $Modules;
        unset($Modules);

        $Views = new \System\Views($Container);
        $rendered = $Views->render('Default/index_v', ['controllerPath' => __FILE__]);
        $this->assertGreaterThanOrEqual(5, strlen($rendered));
    }// testRender


}
