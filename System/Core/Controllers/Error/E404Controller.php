<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace System\Core\Controllers\Error;


/**
 * Error 404 controller.
 * 
 * The error messages from Slim PHP.
 * 
 * @since 0.1
 */
class E404Controller extends \System\Core\Controllers\BaseController
{


    /**
     * Response not found message by content type.
     * 
     * @return string Return error message.
     */
    public function indexAction(): string
    {
        $httpAccept = ($_SERVER['HTTP_ACCEPT'] ?? '*/*');

        if (stripos($httpAccept, 'application/json') !== false) {
            $contentType = 'application/json';
            $output = $this->renderJsonOutput();
        } elseif (
            (
                stripos($httpAccept, 'application/xml') !== false || stripos($httpAccept, 'text/xml') !== false
            ) &&
            stripos($httpAccept, 'text/html') === false
        ) {
            $contentType = 'application/xml';
            $output = $this->renderXmlOutput();
        } elseif (stripos($httpAccept, 'text/html') !== false) {
            $contentType = 'text/html';
            $output = $this->Views->render('Error/E404/index_v');
        } else {
            $contentType = 'text/plain';
            $output = $this->renderTextOutput();
        }

        unset($httpAccept);

        if (!headers_sent()) {
            header('Content-Type: ' . $contentType);
        }
        return $output;
    }// indexAction


    /**
     * Render JSON not found message.
     * 
     * @return string
     */
    protected function renderJsonOutput(): string
    {
        return '{"message":"Not found"}';
    }// renderJsonOutput


    /**
     * Render not found message.
     * 
     * @return string
     */
    protected function renderTextOutput(): string
    {
        return 'Not found';
    }// renderTextOutput


    /**
     * Render XML not found message.
     * 
     * @return string
     */
    protected function renderXmlOutput(): string
    {
        return '<root><message>Not found</message></root>';
    }// renderXmlOutput


}
