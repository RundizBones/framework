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
     * @since 1.1.1
     * @var array The HTTP accept content types. Sorted by quality values. This property can access after called `responseAcceptType()` method.
     */
    protected $httpAcceptContentTypes;


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
     * Determine HTTP accept content-type.
     * 
     * @todo [rdb] Remove this method (`determineAcceptContentType`) in v2.0
     * @deprecated since v1.1.3 Use this method from `Input` class instead.
     * @link https://developer.mozilla.org/en-US/docs/Glossary/Quality_values Reference about quality values (xxx/xx;q=0.8 - for example).
     * @since 1.1.1
     * @return string Return determined content type.
     */
    protected function determineAcceptContentType(): string
    {
        $Input = new \Rdb\System\Libraries\Input($this->Container);
        $httpAccept = $Input->determineAcceptContentType();
        $this->httpAcceptContentTypes = $Input->httpAcceptContentTypes;
        return $httpAccept;
    }// determineAcceptContentType


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
        $Input = new \Rdb\System\Libraries\Input($this->Container);
        $httpAccept = $Input->determineAcceptContentType();
        $this->httpAcceptContentTypes = $Input->httpAcceptContentTypes;

        switch ($httpAccept) {
            case 'application/json':
                return $this->responseJson($output);
            case 'application/xml':
            case 'text/xml':
                return $this->responseXml($output);
            default:
                if (!headers_sent()) {
                    header('Content-Type: ' . $httpAccept);
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
