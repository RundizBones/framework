<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace System\Core\Models;


/**
 * Base model DB.
 * 
 * @since 0.1
 */
abstract class BaseModel
{


    /**
     * Allowed orders in MySQL, MariaDB.
     * @var array 
     */
    protected $allowedOrders = ['ASC', 'DESC', 'RAND', 'RAND()'];


    /**
     * @var \System\Container
     */
    protected $Container;


    /**
     * @var \System\Libraries\Db
     */
    protected $Db;


    /**
     * Base model class constructor.
     * 
     * @param \System\Container $Container The DI container class.
     */
    public function __construct(\System\Container $Container)
    {
        if ($Container instanceof \System\Container) {
            $this->Container = $Container;

            if ($Container->has('Db')) {
                $this->Db = $Container->get('Db');
            }
        }
    }// __construct


}
