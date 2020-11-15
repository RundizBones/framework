<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\System\Libraries;


/**
 * URL class.
 * 
 * @since 0.1
 */
class Url
{


    /**
     * @var \Rdb\System\Container
     */
    protected $Container;


    /**
     * Class constructor.
     * 
     * @param \Rdb\System\Container $Container The DI container class. This is only required for some method, leave null if you don't use it.
     */
    public function __construct(\Rdb\System\Container $Container = null)
    {
        if ($Container instanceof \Rdb\System\Container) {
            $this->Container = $Container;
        }
    }// __construct


    /**
     * Get application based path without trailing slash.
     * 
     * For example: if you install this framework on /myapp sub folder then it will return /myapp.<br>
     * If you install this framework on root folder then it will return empty string.<br>
     * If you install this framework on /myapp and URL contain index.php such as http://localhost/myapp/index.php, it will return /myapp/index.php.
     * 
     * @param bool $raw Set to `true` if you want to get the current URL exactly the same as you see in the address bar.<br>
     *                  Set to `false` to get only real url without language locale URL.<br>
     *                  For example: If the URL in address bar is '/installDir/en-US'.<br>
     *                  If the 'en-US' is default language and it is set to hide default language...<br>
     *                  Set `$raw` to `true` will show url segments as see in address bar (maybe visible language locale URL or not depend on configuration), set to `false` will show just '/installDir'.<br>
     *                  If the 'en-US' is default language and it is set to show default language...<br>
     *                  Set `$raw` to `true` will show language locale URL as see in address bar, set to `false` will show just '/installDir'.
     * @return string Return application based path without trailing slash. It can return empty string if it is installed on root folder.
     */
    public function getAppBasedPath(bool $raw = false): string
    {
        // process here if raw is true means it is unable to get language URL. otherwise raw must be false.
        $getPath = $this->getPath();
        $output = '';

        if ($getPath === '' || $getPath === '/') {
            // if found no path (nothing after index.php such as /mycontroller/method was not found).
            $parsed = parse_url($_SERVER['REQUEST_URI']);
            unset($getPath);
            // the return value can be empty string or app based path without trailing slash.
            // if the URL contain index.php then it will be return app based path with index.php but no trailing slash.
            $output = rtrim($parsed['path'], '/');
        } else {
            $scriptNameUpper = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
            if (mb_substr($_SERVER['REQUEST_URI'], 0, mb_strlen($_SERVER['SCRIPT_NAME'])) === $_SERVER['SCRIPT_NAME']) {
                // if found /install-dir/index.php (install dir with file name).
                $output = rtrim($_SERVER['SCRIPT_NAME'], '/');
            } elseif (mb_substr($_SERVER['REQUEST_URI'], 0, mb_strlen(dirname($_SERVER['SCRIPT_NAME']))) === $scriptNameUpper) {
                // if found just /install-dir.
                $output = rtrim($scriptNameUpper, '/');
            }
            unset($scriptNameUpper);
        }

        unset($getPath);

        if ($raw === true) {
            if ($this->Container != null && $this->Container->has('Config')) {
                /* @var $Config \Rdb\System\Config */
                $Config = $this->Container->get('Config');
                $Config->setModule('');
            } else {
                $Config = new \Rdb\System\Config();
            }

            if ($Config->get('languageMethod', 'language', 'url') === 'url') {
                // if config set to detect language using url.
                if (
                    $Config->get('languageUrlDefaultVisible', 'language', false) === true &&
                    isset($_SERVER['RUNDIZBONES_LANGUAGE'])
                ) {
                    // if config was set to show default language in the URL.
                    $output .= '/' . $_SERVER['RUNDIZBONES_LANGUAGE'];
                } else {
                    // if config was set to hide default language in the URL.
                    if (
                        isset($_SERVER['RUNDIZBONES_LANGUAGE']) && 
                        $_SERVER['RUNDIZBONES_LANGUAGE'] !== $Config->getDefaultLanguage($Config->get('languages', 'language', []))
                    ) {
                        $output .= '/' . $_SERVER['RUNDIZBONES_LANGUAGE'];
                    }
                }
                return $output;
            }// endif; config get language via url.
        }// endif $raw is true

        return $output;
    }// getAppBasedPath


    /**
     * Get current URL.
     * 
     * @param bool $raw Set to `true` if you want to get the current URL exactly the same as you see in the address bar.<br>
     *                  Set to `false` to get only real url without language locale URL.<br>
     *                  For example: If the URL in address bar is '/installDir/en-US'.<br>
     *                  If the 'en-US' is default language and it is set to hide default language...<br>
     *                  Set `$raw` to `true` will show url segments as see in address bar (maybe visible language locale URL or not depend on configuration), set to `false` will show just '/installDir'.<br>
     *                  If the 'en-US' is default language and it is set to show default language...<br>
     *                  Set `$raw` to `true` will show language locale URL as see in address bar, set to `false` will show just '/installDir'.
     * @return string Return current URL without query string and without trailing slash. Example: /installDir/public/my-current-uri
     */
    public function getCurrentUrl(bool $raw = false): string
    {
        if ($raw === true) {
            if (isset($_SERVER['RUNDIZBONES_ORIGINAL_REQUEST_URI'])) {
                return rtrim(strtok($_SERVER['RUNDIZBONES_ORIGINAL_REQUEST_URI'], '?'), '/');
            } else {
                $output = $this->getAppBasedPath() . '/';
                $output .= $this->getPath();
                $output = str_replace(['////', '///', '//'], '/', $output);
                if ($output != '/') {
                    $output = rtrim($output, '/');
                }

                return $output;
            }
        } else {
            if (isset($_SERVER['REQUEST_URI'])) {
                return rtrim(strtok($_SERVER['REQUEST_URI'], '?'), '/');
            } else {
                return '';
            }
        }
    }// getCurrentUrl


    /**
     * Get domain with protocol. Example: https://mydomain.com
     * 
     * @return string
     */
    public function getDomainProtocol(): string
    {
        $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://';
        $url .= ($_SERVER['HTTP_HOST'] ?? '');
        return $url;
    }// getDomainProtocol


    /**
     * Get path from current URL without any query string and no trailing slash.
     * 
     * @return string Return the URL path. 
     *                          Example: URL is http://localhost/myapp/index.php/mycontroller/method will be /mycontroller/method.
     *                          This can return empty string if nothing after app based path.
     */
    public function getPath(): string
    {
        $urlPath = ($_SERVER['REQUEST_URI'] ?? '');

        if (isset($_SERVER['SCRIPT_NAME'])) {
            $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
            if (mb_substr($urlPath, 0, mb_strlen($scriptName)) === $scriptName) {
                $urlPath = mb_substr($urlPath, mb_strlen($scriptName));// remove '/myapp/index.php', '/index.php'
            }

            $upperScriptName = str_replace('\\', '/', dirname($scriptName));
            if ($upperScriptName !== '/' && mb_substr($urlPath, 0, mb_strlen($upperScriptName)) === $upperScriptName) {
                $urlPath = mb_substr($urlPath, mb_strlen($upperScriptName));// remove '/myapp'
            }
            unset($scriptName, $upperScriptName);
        }

        $urlPath = $this->removeQuerystring($urlPath);
        $urlPath = rtrim($urlPath, '/');

        if (!empty($urlPath) && mb_substr($urlPath, 0, 1) !== '/') {
            // if not found beginning with slash, prepend it.
            $urlPath = '/' . $urlPath;
        }

        return $urlPath;
    }// getPath


    /**
     * Get public URL.
     * 
     * Example: If you install this framework on /myapp and your index.php (public folder) is in /myapp URL.<br>
     * It will be return `/myapp`.
     *
     * @since 1.0.3
     * @return string Return the URL start with /app-based-path. This will not return trailing slash.
     */
    public function getPublicUrl(): string
    {
        $scriptName = (isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME']) : '');
        $scriptNameUpper = str_replace('\\', '/', dirname($scriptName));
        $appBase = $this->getAppBasedPath();

        if (mb_substr($appBase, 0, mb_strlen($_SERVER['SCRIPT_NAME'])) === $scriptName) {
            // if found /install-dir/index.php (install dir with file name).
            $appBase = preg_replace('#^' . preg_quote($scriptName) . '#u', $scriptNameUpper, $appBase, 1);
        }
        unset($scriptName, $scriptNameUpper);

        return $appBase;
    }// getPublicUrl


    /**
     * Get public/Modules URL from specific module path.
     * 
     * This method require class constructor to contain `\Rdb\System\Container` object.
     * 
     * Example: If you install this framework on /myapp and your index.php (public folder) is in /myapp URL.<br>
     * If your module is Contact then it will return `/myapp/Modules/Contact`.
     * 
     * @param string $modulePath The full path to any files in your module.
     * @return string Return the URL start with /app-based-path and follow with your public URL with /Modules/ModuleName.
     *                          This will not return trailing slash.
     */
    public function getPublicModuleUrl(string $modulePath): string
    {
        $appBase = $this->getPublicUrl();

        $output = $appBase . '/Modules';
        if ($this->Container != null && $this->Container->has('Modules')) {
            /* @var $Modules \Rdb\System\Modules */
            $Modules = $this->Container->get('Modules');
        } else {
            /* @var $Modules \Rdb\System\Modules */
            $Modules = new \Rdb\System\Modules(new \Rdb\System\Container());
        }
        $output .= '/' . $Modules->getModuleSystemName($modulePath);
        $output = str_replace(['///', '//'], '/', $output);
        unset($Modules);

        return rtrim($output, '/');
    }// getPublicModuleUrl


    /**
     * Get current query string (?param=value) with question mark sign (?) if it is not empty.
     * 
     * The query string will be automatically url encoded.
     * 
     * @return string Return query string value with question mark sign (?) if not empty.
     */
    public function getQuerystring(): string
    {
        $output = '';

        if (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) {
            $output .= '?';
            parse_str($_SERVER['QUERY_STRING'], $vars);
            $query = http_build_query($vars, null, ini_get('arg_separator.output'), PHP_QUERY_RFC3986);
            $output .= $query;
            unset($query, $vars);
        }

        return $output;
    }// getQuerystring


    /**
     * Use `RFC 3986` to encode the query string (same as `rawurlencode` function).
     * 
     * It will be encode only query string (Example: name1=value1&name2=value2&arr[]=valuearr1).<br>
     * The argument separator (`&`) is depend on the URL input, if it contains `&amp;` then it will be return as-is. Otherwise it will be return as setting in `arg_separator.output`.
     *
     * @param string $url The original URL without encoded.
     * @return string Return URL encoded only query string.
     */
    public function rawUrlEncodeQuerystring(string $url): string
    {
        $parsedUrl = parse_url($url);
        $output = $this->removeQuerystring($url);

        if (array_key_exists('query', $parsedUrl)) {
            $output .= '?';
            parse_str($parsedUrl['query'], $queryStringArray);
            if (is_array($queryStringArray)) {
                $numericPrefix = '';
                $argSep = ini_get('arg_separator.output');
                if (stripos($url, '&amp;') !== false) {
                    $argSep = '&amp;';
                }
                $encType = PHP_QUERY_RFC3986;
                $queryString = http_build_query($queryStringArray, $numericPrefix, $argSep, $encType);
                unset($argSep, $encType, $numericPrefix);
                $output .= $queryString;
                unset($queryString);
            }
            unset($queryStringArray);
        }

        if (array_key_exists('fragment', $parsedUrl)) {
            $output .= '#' . $parsedUrl['fragment'];
        }

        unset($parsedUrl);

        return $output;
    }// rawUrlEncodeQuerystring


    /**
     * Use `rawurlencode()` to encode multiple segments.
     * 
     * It will not encode slash (/) to `%2F`. It will also not encode the query string.<br>
     * Example: The URL is 'hello/สวัสดี/ลาก่อน' the result will be 'hello/%E0%B8%AA%E0%B8%A7%E0%B8%B1%E0%B8%AA%E0%B8%94%E0%B8%B5/%E0%B8%A5%E0%B8%B2%E0%B8%81%E0%B9%88%E0%B8%AD%E0%B8%99'.<br>
     * 'hello/สวัสดี?query=string' the result will be 'hello/%E0%B8%AA%E0%B8%A7%E0%B8%B1%E0%B8%AA%E0%B8%94%E0%B8%B5?query=string'.
     *
     * @since 1.0.1
     * @param string $url The original URL without encoded.
     * @return string Return URL encoded only in each segments but not slash.
     */
    public function rawUrlEncodeSegments(string $url): string
    {
        $queryString = parse_url($url, PHP_URL_QUERY);
        $urlNoQuery = $this->removeQuerystring($url);
        $urlExploded = explode('/', $urlNoQuery);
        $newUrlEncoded = [];
        foreach ($urlExploded as $segment) {
            $newUrlEncoded[] = rawurlencode($segment);
        }// endforeach;
        unset($segment, $urlExploded, $urlNoQuery);

        if (is_array($newUrlEncoded) && !empty($newUrlEncoded)) {
            $output = implode('/', $newUrlEncoded);
            if (strpos($url, '?')!== false) {
                $output .= '?';
            }
            if (!empty($queryString)) {
                $output .= $queryString;
            }
            return $output;
        }
        return $url;
    }// rawUrlEncodeSegments


    /**
     * Remove query string.
     * 
     * @param string $url
     * @return string
     */
    public function removeQuerystring(string $url): string
    {
        if (false !== $pos = strpos($url, '?')) {
            // if found query string (?foo=bar).
            $url = substr($url, 0, $pos);
        }

        unset($pos);
        return $url;
    }// removeQuerystring


    /**
     * Remove unsafe URL characters but not URL encode.
     * 
     * This will not remove new line (if `$alphanumOnly` is `false`).
     * 
     * @link https://www.w3.org/Addressing/URL/url-spec.html URL specific.
     * @link https://help.marklogic.com/Knowledgebase/Article/View/251/0/using-url-encoding-to-handle-special-characters-in-a-document-uri Reference.
     * @link https://perishablepress.com/stop-using-unsafe-characters-in-urls/ Reference.
     * @link https://stackoverflow.com/questions/12317049/how-to-split-a-long-regular-expression-into-multiple-lines-in-javascript Multiple line regular expression reference.
     * @param string $name The URL name.
     * @param bool $alphanumOnly Alpha-numeric only or not. Default is `false` (not).
     * @returns string Return formatted URL name.
     */
    public function removeUnsafeUrlCharacters(string $name, bool $alphanumOnly = false): string
    {
        // replace multiple spaces, tabs, new lines.
        // @link https://stackoverflow.com/questions/1981349/regex-to-replace-multiple-spaces-with-a-single-space Reference.
        $name = preg_replace('/\s\s+/', ' ', $name);
        // replace space to dash (-).
        $name = str_replace(' ', '-', $name);

        if ($alphanumOnly === true) {
            // if alpha-numeric only.
            $name = preg_replace('/[^a-zA-Z0-9\-_\.]/', '', $name);
            return $name;
        }

        $pattern = [
            '$@&+', // w3 - safe
            '!*"\'(),', // w3 - extra
            '=;/#?:', // w3 - reserved
            '%', // w3 - escape
            '{}[]\\^~', // w3 - national
            '<>', // w3 - punctuation
            '|', // other unsafe characters.
        ];
        $pattern = preg_quote(implode('', $pattern), '/');
        $name = preg_replace('/[' . $pattern . ']/', '', $name);

        return $name;
    }// removeUnsafeUrlCharacters


}
