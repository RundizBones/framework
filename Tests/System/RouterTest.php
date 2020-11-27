<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Tests\System;


class RouterTest extends \Rdb\Tests\BaseTestCase
{


    /**
     * @var RouterExtended
     */
    protected $Router;


    public function setup(): void
    {
        $this->Router = new RouterExtended();
    }// setup


    public function testFilterMethod()
    {
        $this->assertSame(['GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH'], $this->Router->filterMethod('any'));
        $this->assertSame('POST', $this->Router->filterMethod('post'));
        $this->assertSame(['GET', 'POST'], $this->Router->filterMethod(['Get', 'post']));
    }// testFilterMethod


    public function testGetControllerMethodName()
    {
        $this->assertSame(['ContactController', 'indexAction'], $this->Router->getControllerMethodName('Contact:index'));
        $this->assertSame(['myContactFunction', ''], $this->Router->getControllerMethodName('myContactFunction'));
    }// testGetControllerMethodName


}
