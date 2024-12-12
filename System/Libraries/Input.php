<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\System\Libraries;


/**
 * Input class.
 * 
 * @since 1.1.3
 * @property-read array $httpAcceptContentTypes The HTTP accept content types. Sorted by quality values. This property can access after called `determintAcceptContentType()` method.
 */
class Input
{


    /**
     * @var \Rdb\System\Container
     */
    protected $Container;


    /**
     * @var array The HTTP accept content types. Sorted by quality values. This property can access after called `determintAcceptContentType()` method.
     */
    protected $httpAcceptContentTypes;


    /**
     * Class constructor.
     * 
     * @param \Rdb\System\Container $Container The DI container class. Only required for some method.
     */
    public function __construct(?\Rdb\System\Container $Container = null)
    {
        if ($Container instanceof \Rdb\System\Container) {
            $this->Container = $Container;
        }
    }// __construct


    /**
     * Magic get.
     * 
     * @param string $name The property name.
     * @return mixed Return its value if found the property, return null if not found.
     */
    public function __get($name)
    {
        if (is_string($name) && property_exists($this, $name)) {
            return $this->{$name};
        }
        return null;
    }// __get


    /**
     * Determine HTTP accept content-type.
     * 
     * @link https://developer.mozilla.org/en-US/docs/Glossary/Quality_values Reference about quality values (xxx/xx;q=0.8 - for example).
     * @return string Return determined content type.
     */
    public function determineAcceptContentType(): string
    {
        $httpAccept = ($_SERVER['HTTP_ACCEPT'] ?? '*/*');
        $expHttpAccept = explode(',', $httpAccept);

        if (count($expHttpAccept) > 1) {
            $arrayHttpAccepts = [];
            foreach ($expHttpAccept as $eachHttpAccept) {
                if (!is_scalar($eachHttpAccept)) {
                    continue;
                }

                $expQualityValues = explode(';', $eachHttpAccept);

                if (!array_key_exists(1, $expQualityValues)) {
                    $qualityValues = floatval(1.0);
                } else {
                    $expQualityValues[1] = filter_var($expQualityValues[1], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    $qualityValues = min(floatval(1.0), floatval($expQualityValues[1]));
                }

                $arrayHttpAccepts[trim($expQualityValues[0])] = $qualityValues;
                unset($expQualityValues, $qualityValues);
            }// endforeach;
            unset($eachHttpAccept);

            arsort($arrayHttpAccepts, SORT_NATURAL);
            $this->httpAcceptContentTypes = $arrayHttpAccepts;
            reset($arrayHttpAccepts);
            return key($arrayHttpAccepts);
        }
        unset($expHttpAccept);

        if (stripos($httpAccept, 'text/') !== false || stripos($httpAccept, 'application/') !== false) {
            if (is_scalar($httpAccept) && stripos($httpAccept, ';') !== false) {
                // if found quality values (;q=xxx) for example application/xml;q=0.9
                // remove quality values.
                $expQualityValues = explode(';', $httpAccept);
                $httpAccept = $expQualityValues[0];
                unset($expQualityValues);
            }
            $httpAccept = trim($httpAccept);
            $this->httpAcceptContentTypes = [$httpAccept => floatval(1.0)];
            return $httpAccept;
        }

        $this->httpAcceptContentTypes = ['text/html' => floatval(1.0)];
        return 'text/html';
    }// determineAcceptContentType


}
