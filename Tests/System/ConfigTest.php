<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Tests\System;


class ConfigTest extends \Rdb\Tests\BaseTestCase
{


    public function setup(): void
    {
        $this->runApp('get', '/');
    }// setup


    public function testLoad()
    {
        $Config = new \Rdb\System\Config();
        $result1 = $Config->load('app');
        $result2 = $Config->load('config-file-never-exists-' . mt_rand());
        unset($Config);

        $this->assertTrue($result1);
        $this->assertFalse($result2);
        unset($result1, $result2);
    }// testLoad


    public function testGet()
    {
        $Config = new \Rdb\System\Config();
        $result1 = $Config->get('profiler', 'app', 'none');
        $result2 = $Config->get('config-key-never-exists-' . mt_rand(), 'app', 'notexist2');
        $result3 = $Config->get('config-key-never-exists-' . mt_rand(), 'config-file-never-exists-' . mt_rand(), 'notexist3');

        $this->assertTrue($result1);
        $this->assertEquals('notexist2', $result2);
        $this->assertEquals('notexist3', $result3);
        $this->assertGreaterThanOrEqual(1, count($Config->traceLoadedFiles));
        $this->assertArrayHasKey('system\\core', $Config->loadedFiles);
        $this->assertArrayHasKey('app', $Config->loadedFiles['system\\core']);
        $this->assertArrayHasKey('profiler', $Config->loadedFiles['system\\core']['app']);
        unset($Config, $result1, $result2, $result3);
    }// testGet


    public function testGetDefaultLanguage()
    {
        $Config = new \Rdb\System\Config();
        $result1 = $Config->getDefaultLanguage($Config->get('languages', 'language', []));
        $result2 = $Config->getDefaultLanguage();
        unset($Config);

        $this->assertTrue(is_string($result1) && strlen($result1) >= 1);
        $this->assertTrue(is_string($result2) && strlen($result2) >= 1);
        $this->assertEquals($result1, $result2);
        unset($result1, $result2);
    }// testGetDefaultLanguage


    public function testGetWithoutCache()
    {
        $Config = new \Rdb\System\Config();
        $result1 = $Config->getWithoutCache('profiler', 'app', 'none');
        $result2 = $Config->getWithoutCache('config-key-never-exists-' . mt_rand(), 'app', 'notexist2');
        $result3 = $Config->getWithoutCache('config-key-never-exists-' . mt_rand(), 'config-file-never-exists-' . mt_rand(), 'notexist3');

        $this->assertTrue($result1);
        $this->assertEquals('notexist2', $result2);
        $this->assertEquals('notexist3', $result3);
        $this->assertEquals(0, count($Config->traceLoadedFiles));// couldn't found any loaded because it was get without cache.
        $this->assertArrayNotHasKey('system\\core', $Config->loadedFiles);
        unset($result1, $result2, $result3);

        $result1 = $Config->get('profiler', 'app', 'none');// using normal get which will be cached.
        $this->assertTrue($result1);
        $this->assertGreaterThanOrEqual(1, count($Config->traceLoadedFiles));// now it can count to 1 or more.
        $this->assertArrayHasKey('system\\core', $Config->loadedFiles);
        $this->assertArrayHasKey('app', $Config->loadedFiles['system\\core']);
        $this->assertArrayHasKey('profiler', $Config->loadedFiles['system\\core']['app']);
        unset($Config);
    }// testGetWithoutCache


    public function testSet()
    {
        $Config = new \Rdb\System\Config();
        $Config->load('app');
        $Config->set('app', 'newconfigkey', 'newconfigvalue');
        $result1 = $Config->get('newconfigkey', 'app', 'none');
        unset($Config);

        $this->assertEquals('newconfigvalue', $result1);
        unset($result1);
    }// testSet


}
