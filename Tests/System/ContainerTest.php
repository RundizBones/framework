<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Tests\System;


class ContainerTest extends \Rdb\Tests\BaseTestCase
{


    public function testOffsets()
    {
        $Container = new \Rdb\System\Container();
        $Container['Config'] = function ($c) {
            return new \Rdb\System\Config();
        };
        $Container['Db'] = function ($c) {
            return new Libraries\Db($c['Config']);
        };

        $this->assertInstanceOf(\Rdb\System\Config::class, $Container['Config']);
        unset($Container['Config']);
        $this->assertFalse(isset($Container['Config']));
    }// testOffsets


    /**
     * @expectedException \Pimple\Exception\UnknownIdentifierException
     */
    public function testOffsetException()
    {
        $Container = new \Rdb\System\Container();
        $Container['Config'] = function ($c) {
            return new \Rdb\System\Config();
        };

        unset($Container['Config']);
        $Container['Config'];// throw errors.
        $this->expectException($Container['Config']);// throw errors.
    }// testOffsetException


    public function testPsr()
    {
        $Container = new \Rdb\System\Container();
        $Container['Config'] = function ($c) {
            return new \Rdb\System\Config();
        };

        $this->assertTrue($Container->has('Config'));
        $this->assertInstanceOf(\Rdb\System\Config::class, $Container->get('Config'));
        unset($Container['Config']);
        $this->assertFalse($Container->has('Config'));
    }// testPsr


}
