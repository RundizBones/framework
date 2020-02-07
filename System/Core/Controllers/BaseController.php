<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\System\Core\Controllers;


/**
 * BasedController class.
 * 
 * @since 0.1
 */
abstract class BaseController
{


    /**
     * @var \Rdb\System\Container
     */
    protected $Container;


    /**
     * @var \Rdb\System\Libraries\Db
     */
    protected $Db;


    /**
     * @var \Rdb\System\Modules
     */
    protected $Modules;


    /**
     * @var \Rdb\System\Views
     */
    protected $Views;


    /**
     * Based controller.
     * 
     * @param \Rdb\System\Container $Container The DI container class.
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        $this->Container = $Container;

        if ($this->Container->has('Modules')) {
            $this->Modules = $this->Container->get('Modules');
            $this->Modules->setCurrentModule(get_called_class());// detect current module from child controller.
        }

        if ($this->Container->has('Db')) {
            $this->Db = $this->Container->get('Db');
        }

        $this->Views = new \Rdb\System\Views($this->Container);
    }// __construct


    /**
     * Response the `$output` content by `accept` type in request header.
     * 
     * This method can detect `accept` in request header and response to certain content type automatically.
     * 
     * @param mixed $output The content will be response. If this is array and `accept` is JSON or XML then it will automatically converted.
     * @return string Return content type header and `$output` body.
     */
    protected function responseAcceptType($output): string
    {
        $httpAccept = ($_SERVER['HTTP_ACCEPT'] ?? '*/*');

        if (stripos($httpAccept, 'application/json') !== false) {
            $contentType = 'application/json';
        } elseif (
            (
                stripos($httpAccept, 'application/xml') !== false || stripos($httpAccept, 'text/xml') !== false
            ) &&
            stripos($httpAccept, 'text/html') === false
        ) {
            $contentType = 'application/xml';
        } elseif (stripos($httpAccept, 'text/html') !== false) {
            $contentType = 'text/html';
        } else {
            $contentType = 'text/plain';
        }

        unset($httpAccept);

        switch ($contentType) {
            case 'application/json':
                return $this->responseJson($output);
            case 'application/xml':
                return $this->responseXml($output);
            default:
                if (!headers_sent()) {
                    header('Content-Type: ' . $contentType);
                }

                if (!is_scalar($output)) {
                    $output = json_encode($output);
                }

                return (string) $output;
        }
    }// responseAcceptType


    /**
     * Set application/json header and return json encoded.
     * 
     * @param mixed $output The content will be json encode.
     * @return string Return json encoded string.
     */
    protected function responseJson($output): string
    {
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }

        return json_encode($output);
    }// responseJson


    /**
     * Send no cache headers.
     * 
     * This should be called before response body. It is very useful with redirect to prevent redirect cached.
     */
    protected function responseNoCache()
    {
        if (!headers_sent()) {
            header('Expires: Fri, 01 Jan 1971 00:00:00 GMT');
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Cache-Control: post-check=0, pre-check=0', false);
            header('Pragma: no-cache');
        }
    }// responseNoCache


    /**
     * Set application/xml header and return XML converted from array.
     * 
     * @param mixed $output The content will be XML. Recommended type is array.
     * @return string Return XML converted content from `$output` array.
     */
    protected function responseXml($output): string
    {
        if (!is_array($output)) {
            $output = array(json_encode($output));
        }

        if (!headers_sent()) {
            header('Content-Type: application/xml');
        }

        $SimpleXml = new \SimpleXMLElement('<?xml version="1.0"?><data></data>');
        $Xml = new \Rdb\System\Libraries\Xml();
        $Xml->fromArray($output, $SimpleXml);
        $content = $SimpleXml->asXML();
        unset($SimpleXml, $Xml);

        return $content;
    }// responseXml


}
