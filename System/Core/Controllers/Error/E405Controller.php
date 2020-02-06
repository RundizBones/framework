<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace System\Core\Controllers\Error;


/**
 * Error 405 controller.
 * 
 * The error messages from Slim PHP.
 * 
 * @since 0.1
 */
class E405Controller extends \System\Core\Controllers\BaseController
{


    public function indexAction(): string
    {
        $methods = func_get_args();
        $httpAccept = ($_SERVER['HTTP_ACCEPT'] ?? '*/*');

        if (stripos($httpAccept, 'application/json') !== false) {
            $contentType = 'application/json';
            $output = $this->renderJsonOutput($methods);
        } elseif (
            (
                stripos($httpAccept, 'application/xml') !== false || stripos($httpAccept, 'text/xml') !== false
            ) &&
            stripos($httpAccept, 'text/html') === false
        ) {
            $contentType = 'application/xml';
            $output = $this->renderXmlOutput($methods);
        } elseif (stripos($httpAccept, 'text/html') !== false) {
            $contentType = 'text/html';
            $output = $this->Views->render('Error/E405/index_v', ['allowedString' => implode(', ', $methods), 'allowedArray' => $methods]);
        } else {
            $contentType = 'text/plain';
            $output = $this->renderTextOutput($methods);
        }

        unset($httpAccept, $methods);

        if (!headers_sent()) {
            header('Content-Type: ' . $contentType);
        }
        return $output;
    }// indexAction


    /**
     * Render JSON not found message.
     * 
     * @param array $methods The allowed methods.
     * @return string
     */
    protected function renderJsonOutput(array $methods): string
    {
        $allow = implode(', ', $methods);
        return '{"message":"Method not allowed. Must be one of: ' . $allow . '"}';
    }// renderJsonOutput


    /**
     * Render not found message.
     * 
     * @param array $methods The allowed methods.
     * @return string
     */
    protected function renderTextOutput(array $methods): string
    {
        $allow = implode(', ', $methods);
        return 'Allowed methods: ' . $allow;
    }// renderTextOutput


    /**
     * Render XML not found message.
     * 
     * @param array $methods The allowed methods.
     * @return string
     */
    protected function renderXmlOutput(array $methods): string
    {
        $allow = implode(', ', $methods);
        return '<root><message>Method not allowed. Must be one of: ' . $allow . '</message></root>';
    }// renderXmlOutput


}
