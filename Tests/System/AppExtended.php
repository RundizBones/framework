<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Tests\System;


/**
 * Extended the framework's App class to allow access protected method.
 */
class AppExtended extends \Rdb\System\App
{


    public function addDependencyInjection()
    {
        return parent::addDependencyInjection();
    }// addDependencyInjection


}
