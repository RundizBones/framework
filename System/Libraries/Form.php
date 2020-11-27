<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\System\Libraries;


/**
 * Form class.
 * 
 * @since 0.1
 */
class Form
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
     * Check if the value and input value is matched.
     * 
     * @param mixed $value The value to check. Its type must be scalar or `null`.
     * @param mixed $inputValue The input value to check. Its type must be scalar, `null`, array, object. If it is array or object and one of value in this argument is matched then it will be return `true`.
     * @param bool $caseInSensitive Set to `true` to case insensitive check, `false` (default) to case sensitive check.
     * @return bool Return `true` if matched, return `false` for otherwise.
     */
    public function isMatched($value, $inputValue, bool $caseInSensitive = false): bool
    {
        if (!is_scalar($value) && !is_null($value)) {
            // if value type is unable to check.
            return false;
        }

        if (is_scalar($inputValue) || is_null($inputValue)) {
            // if input value for check is scalar or null.
            if ($caseInSensitive === true && strtolower($value) == strtolower($inputValue)) {
                return true;
            } elseif ($caseInSensitive === false && $value == $inputValue) {
                return true;
            }
        } elseif (is_array($inputValue) || is_object($inputValue)) {
            // if input value is array or object.
            foreach ($inputValue as $key => $item) {
                if (
                    $caseInSensitive === true && 
                    (is_scalar($item) || is_null($item)) && 
                    strtolower($value) == strtolower($item)
                ) {
                    return true;
                } elseif ($caseInSensitive === false && $value == $item) {
                    return true;
                }
            }// endforeach;
            unset($item, $key);
        }

        return false;
    }// isMatched


    /**
     * Set checked if value and input value is matched.
     * 
     * @param mixed $value The value to check. Its type must be scalar or `null`.
     * @param mixed $inputValue The input value to check. Its type must be scalar, `null`, array, object.
     * @param bool $caseInSensitive Set to `true` to case insensitive check, `false` (default) to case sensitive check.
     * @return string Return string `selected` if matched.
     */
    public function setChecked($value, $inputValue, bool $caseInSensitive = false): string
    {
        if ($this->isMatched($value, $inputValue, $caseInSensitive)) {
            return ' checked="checked"';
        }

        return '';
    }// setChecked


    /**
     * Set selected if value and input value is matched.
     * 
     * @param mixed $value The value to check. Its type must be scalar or `null`.
     * @param mixed $inputValue The input value to check. Its type must be scalar, `null`, array, object.
     * @param bool $caseInSensitive Set to `true` to case insensitive check, `false` (default) to case sensitive check.
     * @return string Return string `selected` if matched.
     */
    public function setSelected($value, $inputValue, bool $caseInSensitive = false): string
    {
        if ($this->isMatched($value, $inputValue, $caseInSensitive)) {
            return ' selected="selected"';
        }

        return '';
    }// setSelected


}
