<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\System\Libraries;


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
            return call_user_func_array([new self, $name], array_values($arguments));
        }
    }// __callStatic


    /**
     * Array custom merge. Preserve indexed array key (numbers) but overwrite string key (same as PHP's `array_merge()` function).
     * 
     * If the another array key is string, it will be overwrite the first array.<br>
     * If the another array key is integer, it will be add to first array depend on duplicated key or not. 
     * If it is not duplicate key with the first, the key will be preserve and add to the first array.
     * If it is duplicated then it will be re-index the number append to the first array.
     *
     * @since 1.1.1
     * @param array $array1 The first array is main array.
     * @param array ...$arrays The another arrays to merge with the first.
     * @return array Return merged array.
     */
    public function arrayCustomMerge(array $array1, array ...$arrays): array
    {
        foreach ($arrays as $additionalArray) {
            foreach ($additionalArray as $key => $item) {
                if (is_string($key)) {
                    // if associative array.
                    // item on the right will always overwrite on the left.
                    $array1[$key] = $item;
                } elseif (is_int($key) && !array_key_exists($key, $array1)) {
                    // if key is number. this should be indexed array.
                    // and if array 1 is not already has this key.
                    // add this array with the preserved key to array 1.
                    $array1[$key] = $item;
                } else {
                    // if anything else...
                    // get all keys from array 1 (numbers only).
                    $array1Keys = array_filter(array_keys($array1), 'is_int');
                    // next key index = get max array key number + 1.
                    $nextKeyIndex = (intval(max($array1Keys)) + 1);
                    unset($array1Keys);
                    // check again that next key in first array is not exists.
                    if (array_key_exists($nextKeyIndex, $array1)) {
                        // if still found exists on array1.
                        // use random things.
                        $nextKeyIndex = uniqid('arrayCustomMerge');
                    }
                    // set array with the next key index.
                    $array1[$nextKeyIndex] = $item;
                    unset($nextKeyIndex);
                }
            }// endforeach; $additionalArray
            unset($item, $key);
        }// endforeach;
        unset($additionalArray);

        return $array1;
    }// arrayCustomMerge


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
     * The method `recursiveKsort()` can't call using magic call static because it is required to call itself using `$this`.<br>
     * This static version will initialize the class with `new self()`.
     * 
     * @see \Rdb\System\Libraries\ArrayUtil:recursiveKsort()
     */
    public static function staticRecursiveKsort(array &$array, $sortFlags = SORT_REGULAR)
    {
        $thisClass = new self();
        $thisClass->recursiveKsort($array, $sortFlags);
    }// staticRecursiveKsort*/


}
