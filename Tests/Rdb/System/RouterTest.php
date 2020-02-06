<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Tests\Rdb\System;


class RouterTest extends \Tests\Rdb\BaseTestCase
{


    /**
     * @var RouterExtended
     */
    protected $Router;


    public function setup()
    {
        $this->Router = new RouterExtended();
    }// setup


    public function testFilterMethod()
    {
        $this->assertArraySubset(['GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH'], $this->Router->filterMethod('any'));
        $this->assertEquals('POST', $this->Router->filterMethod('post'));
        $this->assertArraySubset(['GET', 'POST'], $this->Router->filterMethod(['Get', 'post']));
    }// testFilterMethod


    public function testGetControllerMethodName()
    {
        $this->assertArraySubset(['ContactController', 'indexAction'], $this->Router->getControllerMethodName('Contact:index'));
        $this->assertArraySubset(['myContactFunction', ''], $this->Router->getControllerMethodName('myContactFunction'));
    }// testGetControllerMethodName


}
