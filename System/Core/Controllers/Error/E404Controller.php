<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\System\Core\Controllers\Error;


/**
 * Error 404 controller.
 * 
 * @since 0.1
 */
class E404Controller extends \Rdb\System\Core\Controllers\BaseController
{


    /**
     * Response not found message by content type.
     * 
     * @return string Return error message.
     */
    public function indexAction(): string
    {
        http_response_code(404);
        $httpAccept = ($_SERVER['HTTP_ACCEPT'] ?? '*/*');

        if (
            stripos($httpAccept, 'text/html') !== false ||
            stripos($httpAccept, 'application/xhtml+xml') !== false
        ) {
            // if html or xhtml.
            return $this->Views->render('Error/E404/index_v');
        } else {
            // if anything else.
            $output = [];
            $output['message'] = 'Not found';
            return $this->responseAcceptType($output);
        }
    }// indexAction


}
