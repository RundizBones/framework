<?php
/** 
 * Contain error handler.
 * 
 * The array key is error code and its value is controller handler.
 * Example:
 * <pre>
 * return array(
 *     '404' => '\Rdb\System\Core\Controllers\Error\E404:index',
 *     '405' => '\Rdb\System\Core\Controllers\Error\E405:index',
 * );
 * </pre>
 * The class in handler will be automatically add `Controller` suffix, and the method in handler will be automatically add `Action` suffix.
 * 
 * @license http://opensource.org/licenses/MIT MIT
 */


return [
    '404' => '\Rdb\System\Core\Controllers\Error\E404:index',
    '405' => '\Rdb\System\Core\Controllers\Error\E405:index',
];