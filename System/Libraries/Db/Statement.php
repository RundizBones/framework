<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\System\Libraries\Db;


/**
 * DB statement class.
 * 
 * @since 0.1
 * @link https://stackoverflow.com/a/7716896/128761 Copied the ideas and source code from here.
 */
class Statement extends \PDOStatement
{


    /**
     * @var \Rdb\System\Container
     */
    protected $Container;


    /**
     * @since 1.1.2
     * @var mixed The input data type from `bindXXX()`.
     */
    protected $inputDataTypes;


    /**
     * @var mixed The input parameters from `bindXXX()`, `execute()`.
     */
    protected $inputParams;


    /**
     * Class constructor is required.
     * 
     * @param \Rdb\System\Container $Container The DI container class.
     */
    protected function __construct(\Rdb\System\Container $Container = null)
    {
        if ($Container instanceof \Rdb\System\Container) {
            $this->Container = $Container;
        } else {
            $this->Container = new \Rdb\System\Container();
        }
    }// __construct


    /**
     * {@inheritDoc}
     */
    public function bindParam($parameter, &$variable, $data_type = null, $length = null, $driverdata = null)
    {
        if (!is_array($this->inputParams) && is_null($this->inputParams)) {
            $this->inputParams = [];
        }
        if (!is_array($this->inputDataTypes) && is_null($this->inputDataTypes)) {
            $this->inputDataTypes = [];
        }

        if (is_null($data_type)) {
            $data_type = \PDO::PARAM_STR;
        }

        $this->inputParams[$parameter] = &$variable;
        $this->inputDataTypes[$parameter] = $data_type;

        return parent::bindParam($parameter, $variable, $data_type, $length, $driverdata);
    }// bindParam


    /**
     * {@inheritDoc}
     */
    public function bindValue($parameter, $value, $data_type = null)
    {
        if (!is_array($this->inputParams) && is_null($this->inputParams)) {
            $this->inputParams = [];
        }
        if (!is_array($this->inputDataTypes) && is_null($this->inputDataTypes)) {
            $this->inputDataTypes = [];
        }

        if (is_null($data_type)) {
            $data_type = \PDO::PARAM_STR;
        }

        $this->inputParams[$parameter] = $value;
        $this->inputDataTypes[$parameter] = $data_type;

        parent::bindValue($parameter, $value, $data_type);
    }// bindValue


    /**
     * {@inheritDoc}
     */
    public function execute($input_parameters = null)
    {
        // input params can be `array(':name1' => 'value1', ':name2' => 'value2')`, or `array('value1', 'value2')`, or `null`
        if (
            !is_null($input_parameters) && 
            (
                is_array($input_parameters) && 
                !empty($input_parameters)
            )
        ) {
            $this->inputParams = $input_parameters;
        }// endif;

        if (strspn(strtolower($this->queryString), 'explain ') === 0) {
            // if not found `EXPLAIN ` statement query.
            $Logger = new Logger($this->Container);
            $Logger->queryLog($this->queryString, $this->inputParams, $this->inputDataTypes);
            unset($Logger);
        }

        return parent::execute($input_parameters);
    }// execute


}
