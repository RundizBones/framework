<?php
/** 
 * PHP string polyfill helpers.
 * 
 * @license http://opensource.org/licenses/MIT MIT
 * @since 1.1.7
 */


if (!function_exists('str_contains')) {
    /**
     * Performs a case-sensitive check indicating if needle is contained in haystack.
     * 
     * @link https://www.php.net/manual/en/function.str-contains.php#125977 Original source code.
     * @since 1.1.7
     * @param string $haystack The string to search in.
     * @param string $needle The substring to search for in the haystack.
     * @return bool Returns true if needle is in haystack, false otherwise.
     */
    function str_contains(string $haystack, string $needle): bool
    {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }// str_contains
}// endif;
