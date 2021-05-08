<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Tests\System\Libraries;


class InputTest extends \Rdb\Tests\BaseTestCase
{


    /**
     * @var \Rdb\System\Libraries\Input
     */
    protected $Input;


    public function setup(): void
    {
        $this->Input = new \Rdb\System\Libraries\Input();
    }// setup


    public function testDetermineAcceptContentType()
    {
        $this->assertEquals('text/html', $this->Input->determineAcceptContentType());// not set http accept.

        $_SERVER['HTTP_ACCEPT'] = '*/*';
        $this->assertEquals('text/html', $this->Input->determineAcceptContentType());

        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        $this->assertEquals('application/json', $this->Input->determineAcceptContentType());

        $this->assertIsArray($this->Input->httpAcceptContentTypes);
        $this->assertNotEmpty($this->Input->httpAcceptContentTypes);

        $_SERVER['HTTP_ACCEPT'] = 'text/html,application/xhtml+xml,*/*;q=0.8,application/xml;q=0.9,image/webp';
        $this->assertEquals('text/html', $this->Input->determineAcceptContentType());
        $assert = [
            'text/html' => 1.0,
            'application/xhtml+xml' => 1.0,
            'image/webp' => 1.0,
            'application/xml' => 0.9,
            '*/*' => 0.8,
        ];
        $this->assertSame($assert, $this->Input->httpAcceptContentTypes);
    }// testDetermineAcceptContentType


}
