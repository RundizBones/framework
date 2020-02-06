<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace System\Libraries;


/**
 * Array utilities class.
 * 
 * @since 0.1
 */
class ArrayUtil
{


    /**
     * Call static magic method.
     * 
     * @param string $name
     * @param mixed $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        if (stripos($name, 'static') === 0) {
            $name = lcfirst(substr($name, 6));
            return call_user_func_array([new self, $name], $arguments);
        }
    }// __callStatic


    /**
     * Checks if a value exists in an array (case insensitive).
     * 
     * @link https://stackoverflow.com/questions/2166512/php-case-insensitive-in-array-function code reference.
     * @link https://www.php.net/manual/en/function.in-array.php `in_array()` function reference.
     * @param mixed $needle The searched value.
     * @param array $haystack The array.
     * @return bool Returns `true` if needle is found in the array, `false` otherwise.
     */
    public function inArrayI($needle, array $haystack): bool
    {
        if (is_string($needle)) {
            return in_array(strtolower($needle), array_map('strtolower', $haystack));
        } else {
            return in_array($needle, $haystack);
        }
    }// inArrayI


    /**
     * Check if associative array or indexed array?
     * 
     * @link https://stackoverflow.com/questions/173400/how-to-check-if-php-array-is-associative-or-sequential Original source code.
     * @param array $array The array to check.
     * @return bool Return `true` if it is associative array, `false` for otherwise.
     */
    public function isAssoc(array $array): bool
    {
        foreach ($array as $key => $value) {
            if (is_string($key)) {
                return true;
            }
        }

        return false;
    }// isAssoc


    /**
     * Recursive sort array key.
     * 
     * @link https://www.php.net/manual/en/function.ksort.php `ksort()` function.
     * @link https://www.php.net/manual/en/function.sort.php `sort()` function.
     * @link https://stackoverflow.com/a/4501406/128761 Original source code.
     * @param array $array The array.
     * @param int $sortFlags You may modify the behavior of the sort using the optional parameter `sortFlag`, for details see `sort()`.
     */
    public function recursiveKsort(array &$array, $sortFlags = SORT_REGULAR)
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->recursiveKsort($value, $sortFlags);
            }
        }
        ksort($array, $sortFlags);
    }// recursiveKsort


    /**
     * Static version of `recursiveKsort()`.
     * 
     * @see \System\Libraries\ArrayUtil:recursiveKsort()
     */
    public static function staticRecursiveKsort(array &$array, $sortFlags = SORT_REGULAR)
    {
        $thisClass = new self();
        $thisClass->recursiveKsort($array, $sortFlags);
    }// staticRecursiveKsort


}
