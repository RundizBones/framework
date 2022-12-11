<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\System\Core\Models;


/**
 * Base model DB.
 * 
 * @since 0.1
 */
#[AllowDynamicProperties]
abstract class BaseModel
{


    /**
     * Allowed orders in MySQL, MariaDB.
     * @var array 
     */
    protected $allowedOrders = ['ASC', 'DESC', 'RAND', 'RAND()'];


    /**
     * @var \Rdb\System\Container
     */
    protected $Container;


    /**
     * @var \Rdb\System\Libraries\Db
     */
    protected $Db;


    /**
     * Base model class constructor.
     * 
     * @param \Rdb\System\Container $Container The DI container class.
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        if ($Container instanceof \Rdb\System\Container) {
            $this->Container = $Container;

            if ($Container->has('Db')) {
                $this->Db = $Container->get('Db');
            }
        }
    }// __construct


}
