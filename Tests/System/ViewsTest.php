<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Tests\System;


class ViewsTest extends \Rdb\Tests\BaseTestCase
{


    public function testRender()
    {
        $Modules = new \Rdb\System\Modules(new \Rdb\System\Container());
        $Modules->setCurrentModule('Rdb\\System\\Core');

        $Container = new \Rdb\System\Container();
        $Container['Modules'] = $Modules;
        unset($Modules);

        $Views = new \Rdb\System\Views($Container);
        $rendered = $Views->render('Default/index_v', ['controllerPath' => __FILE__]);
        $this->assertGreaterThanOrEqual(5, strlen($rendered));
    }// testRender


}
