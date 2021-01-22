<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\System\Core\Controllers\Error;


/**
 * Error 405 controller.
 * 
 * @since 0.1
 */
class E405Controller extends \Rdb\System\Core\Controllers\BaseController
{


    /**
     * Response method not allowed message by content type.
     * 
     * @return string Return error message.
     */
    public function indexAction(): string
    {
        http_response_code(405);
        $methods = func_get_args();
        $httpAccept = ($_SERVER['HTTP_ACCEPT'] ?? '*/*');

        if (
            stripos($httpAccept, 'text/html') !== false ||
            stripos($httpAccept, 'application/xhtml+xml') !== false
        ) {
            // if html or xhtml.
            return $this->Views->render('Error/E405/index_v', ['allowedString' => implode(', ', $methods), 'allowedArray' => $methods]);
        } else {
            // if anything else.
            $output = [];
            $allow = implode(', ', $methods);
            $output['message'] = 'Method not allowed. Must be one of: ' . $allow . '';
            return $this->responseAcceptType($output);
        }
    }// indexAction


}
