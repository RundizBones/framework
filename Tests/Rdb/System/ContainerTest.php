<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Tests\Rdb\System;


class ContainerTest extends \Tests\Rdb\BaseTestCase
{


    public function testOffsets()
    {
        $Container = new \System\Container();
        $Container['Config'] = function ($c) {
            return new \System\Config();
        };
        $Container['Db'] = function ($c) {
            return new Libraries\Db($c['Config']);
        };

        $this->assertInstanceOf(\System\Config::class, $Container['Config']);
        unset($Container['Config']);
        $this->assertFalse(isset($Container['Config']));
    }// testOffsets


    /**
     * @expectedException \Pimple\Exception\UnknownIdentifierException
     */
    public function testOffsetException()
    {
        $Container = new \System\Container();
        $Container['Config'] = function ($c) {
            return new \System\Config();
        };

        unset($Container['Config']);
        $Container['Config'];// throw errors.
        $this->expectException($Container['Config']);// throw errors.
    }// testOffsetException


    public function testPsr()
    {
        $Container = new \System\Container();
        $Container['Config'] = function ($c) {
            return new \System\Config();
        };

        $this->assertTrue($Container->has('Config'));
        $this->assertInstanceOf(\System\Config::class, $Container->get('Config'));
        unset($Container['Config']);
        $this->assertFalse($Container->has('Config'));
    }// testPsr


}
