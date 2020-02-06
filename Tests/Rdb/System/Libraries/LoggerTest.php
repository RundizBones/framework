<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Tests\Rdb\System\Libraries;


class LoggerTest extends \Tests\Rdb\BaseTestCase
{


    public function tearDown()
    {
        $FileSystem = new \System\Libraries\FileSystem(STORAGE_PATH . '/logs');
        $FileSystem->deleteFolder('tests', true);
    }// tearDown


    public function testWrite()
    {
        $Logger = new \System\Libraries\Logger(new \System\Container(), ['enable' => true, 'donotLogLevel' => 0]);
        $logResult = $Logger->write('tests/unit-test', 0, 'Debug message {hello}', ['hello' => 'Hello world']);
        $this->assertTrue($logResult);
        unset($Logger);

        $Logger = new \System\Libraries\Logger(new \System\Container(), ['enable' => true, 'donotLogLevel' => 1]);
        $this->assertFalse($Logger->write('tests/unit-test', 0, 'Log with debug level.'));
        $this->assertTrue($Logger->write('tests/unit-test', 1, 'Log with info level.'));
        $this->assertTrue($Logger->write('tests/unit-test', 4, 'Log with error level.'));
    }// testWrite


}
