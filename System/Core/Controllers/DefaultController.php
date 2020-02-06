<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace System\Core\Controllers;


/**
 * DefaultController
 * 
 * @since 0.1
 */
class DefaultController extends BaseController
{


    /**
     * Default action for controller.
     * 
     * @return string
     */
    public function indexAction()
    {
        $data['controllerPath'] = __FILE__;

        return $this->Views->render('Default/index_v', $data);
    }// indexAction


}
