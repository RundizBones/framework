<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Tests\System\Libraries;


class LoggerTest extends \Rdb\Tests\BaseTestCase
{


    public function tearDown()
    {
        $FileSystem = new \Rdb\System\Libraries\FileSystem(STORAGE_PATH . '/logs');
        $FileSystem->deleteFolder('tests', true);
    }// tearDown


    public function testWrite()
    {
        $Logger = new \Rdb\System\Libraries\Logger(new \Rdb\System\Container(), ['enable' => true, 'donotLogLevel' => 0]);
        $logResult = $Logger->write('tests/unit-test', 0, 'Debug message {hello}', ['hello' => 'Hello world']);
        $this->assertTrue($logResult);
        unset($Logger);

        $Logger = new \Rdb\System\Libraries\Logger(new \Rdb\System\Container(), ['enable' => true, 'donotLogLevel' => 1]);
        $this->assertFalse($Logger->write('tests/unit-test', 0, 'Log with debug level.'));
        $this->assertTrue($Logger->write('tests/unit-test', 1, 'Log with info level.'));
        $this->assertTrue($Logger->write('tests/unit-test', 4, 'Log with error level.'));
    }// testWrite


}
