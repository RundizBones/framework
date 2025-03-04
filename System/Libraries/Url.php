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
    public function __construct(?\Rdb\System\Container $Container = null)
    {
        if ($Container instanceof \Rdb\System\Container) {
            $this->Container = $Container;
        }
    }// __construct


    /**
     * Build URL from `parse_url()` function.
     *
     * @since 1.0.5
     * @param array $parsedUrl The value from `parse_url()` function.
     * @return string Return built URL.
     */
    public function buildUrl(array $parsedUrl): string
    {
        $output = '';

        if (array_key_exists('scheme', $parsedUrl)) {
            $output .= $parsedUrl['scheme'] . ':';
        }
        if (
            array_key_exists('user', $parsedUrl) ||
            array_key_exists('host', $parsedUrl)
        ) {
            $output .= '//';
        }
        if (array_key_exists('user', $parsedUrl)) {
            $output .= $parsedUrl['user'];
        }
        if (array_key_exists('pass', $parsedUrl)) {
            $output .= ':' . $parsedUrl['pass'];
        }
        if (array_key_exists('user', $parsedUrl)) {
            $output .= '@';
        }
        if (array_key_exists('host', $parsedUrl)) {
            $output .= $parsedUrl['host'];
        }
        if (array_key_exists('port', $parsedUrl)) {
            $output .= ':' . $parsedUrl['port'];
        }
        if (array_key_exists('path', $parsedUrl)) {
            $output .= $parsedUrl['path'];
        }
        if (array_key_exists('query', $parsedUrl)) {
            $output .= '?' . $parsedUrl['query'];
        }
        if (array_key_exists('fragment', $parsedUrl)) {
            $output .= '#' . $parsedUrl['fragment'];
        }

        return $output;
    }// buildUrl


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
            $output = rtrim(($parsed['path'] ?? ''), '/');
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
     *                  Set to `false` to get only real URL without language locale URL.<br>
     *                  For example: If the URL in address bar is '/installDir/en-US'.<br>
     *                  If the 'en-US' is default language and it is set to hide default language...<br>
     *                  Set `$raw` to `true` will show URL segments as see in address bar (maybe visible language locale URL or not depend on configuration), set to `false` will show just '/installDir'.<br>
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
     * @param bool|null $forceHttps Set to `true` to force use HTTPS, `false` to force use HTTP. Default is `null` to auto detect current protocol. (since 1.1.2)
     * @return string
     */
    public function getDomainProtocol($forceHttps = null): string
    {
        if (!is_bool($forceHttps)) {
            $forceHttps = null;
        }

        if ($forceHttps === true) {
            $url = 'https://';
        } elseif ($forceHttps === false) {
            $url = 'http://';
        } else {
            $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://';
        }
        $url .= ($_SERVER['HTTP_HOST'] ?? '');
        return $url;
    }// getDomainProtocol


    /**
     * Get path of current URL but related from public URL without any query string and no trailing slash.
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
            $appBase = preg_replace('#^' . preg_quote($scriptName, '#') . '#u', $scriptNameUpper, $appBase, 1);
        }
        unset($scriptName, $scriptNameUpper);

        return rtrim($appBase, '/');
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
            $query = http_build_query($vars, '', ini_get('arg_separator.output'), PHP_QUERY_RFC3986);
            $output .= $query;
            unset($query, $vars);
        }

        return $output;
    }// getQuerystring


    /**
     * Get the specific URL segment.
     *
     * @since 1.0.6
     * @param int $number Segment number to get. Start from 1.
     * @return string Return value of the selected URL segment.
     */
    public function getSegment(int $number): string
    {
        $segments = $this->getSegments();

        $output = '';

        $number = ($number - 1);
        if (array_key_exists($number, $segments)) {
            $output = $segments[$number];
        }

        return $output;
    }// getSegment


    /**
     * Get URL segments.
     *
     * @since 1.0.6
     * @return array Return all URL segments except language URL if the configuration file was use detect language on the URL.
     */
    public function getSegments(): array
    {
        $path = $this->getPath();

        $expPath = explode('/', $path);
        if (is_array($expPath)) {
            if (isset($expPath[0]) && $expPath[0] === '') {
                unset($expPath[0]);
                $expPath = array_values($expPath);
            }
            return $expPath;
        }

        unset($expPath, $parsed);
        return [];
    }// getSegments


    /**
     * Encode all parts of the URL.
     * 
     * This will be encode username:password, path or segments, query string, fragment.
     *
     * @since 1.0.5
     * @param string $url The original URL without encoded.
     * @return string Return URL encoded only fragment.
     */
    public function rawUrlEncodeAllParts(string $url): string
    {
        $parsedUrl = parse_url($url);

        if (array_key_exists('user', $parsedUrl)) {
            $parsedUrl['user'] = rawurlencode($parsedUrl['user']);
        }
        if (array_key_exists('pass', $parsedUrl)) {
            $parsedUrl['pass'] = rawurlencode($parsedUrl['pass']);
        }

        if (array_key_exists('path', $parsedUrl) && is_scalar($parsedUrl['path'])) {
            $expPath = explode('/', $parsedUrl['path']);
            foreach ($expPath as $index => $segment) {
                $expPath[$index] = rawurlencode($segment);
            }
            $parsedUrl['path'] = implode('/', $expPath);
            unset($expPath);
        }

        if (array_key_exists('query', $parsedUrl) && !empty($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryStringArray);
            if (is_array($queryStringArray)) {
                $numericPrefix = '';
                $argSep = ini_get('arg_separator.output');
                if (stripos($url, '&amp;') !== false) {
                    $argSep = '&amp;';
                }
                $encType = PHP_QUERY_RFC3986;
                $parsedUrl['query'] = http_build_query($queryStringArray, $numericPrefix, $argSep, $encType);
                unset($argSep, $encType, $numericPrefix);
            }
            unset($queryStringArray);
        } elseif (array_key_exists('query', $parsedUrl) && empty($parsedUrl['query'])) {
            unset($parsedUrl['query']);
        }

        if (array_key_exists('fragment', $parsedUrl) && !empty($parsedUrl['fragment'])) {
            $parsedUrl['fragment'] = rawurlencode($parsedUrl['fragment']);
        } elseif (array_key_exists('fragment', $parsedUrl) && empty($parsedUrl['fragment'])) {
            unset($parsedUrl['fragment']);
        }

        $output = $this->buildUrl($parsedUrl);

        unset($parsedUrl);
        return $output;
    }// rawUrlEncodeAllParts


    /**
     * Encode the fragment (#anchor) on the URL use `rawurlencode()`.
     * 
     * This will not encode other parts.
     *
     * @since 1.0.5
     * @param string $url The original URL without encoded.
     * @return string Return URL encoded only fragment.
     */
    public function rawUrlEncodeFragment(string $url): string
    {
        $parsedUrl = parse_url($url);

        if (array_key_exists('fragment', $parsedUrl) && !empty($parsedUrl['fragment'])) {
            $parsedUrl['fragment'] = rawurlencode($parsedUrl['fragment']);
        } elseif (array_key_exists('fragment', $parsedUrl) && empty($parsedUrl['fragment'])) {
            unset($parsedUrl['fragment']);
        }

        // remove query, etc if empty.
        // @see https://bugs.php.net/bug.php?id=80431 for more info.
        if (array_key_exists('query', $parsedUrl) && empty($parsedUrl['query'])) {
            unset($parsedUrl['query']);
        }

        $output = $this->buildUrl($parsedUrl);

        unset($parsedUrl);
        return $output;
    }// rawUrlEncodeFragment


    /**
     * Use `RFC 3986` to encode the query string (same as `rawurlencode` function).
     * 
     * It will be encode only query string (Example: name1=value1&name2=value2&arr[]=valuearr1).<br>
     * The argument separator (`&`) is depend on the URL input, if it contains `&amp;` then it will be return as-is. Otherwise it will be return as setting in `arg_separator.output`.
     *
     * @since 1.0.4
     * @param string $url The original URL without encoded.
     * @return string Return URL encoded only query string.
     */
    public function rawUrlEncodeQuerystring(string $url): string
    {
        $parsedUrl = parse_url($url);
        $output = $this->removeQuerystring($url);

        if (array_key_exists('query', $parsedUrl) && !empty($parsedUrl['query'])) {
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

        // remove fragment, etc if empty.
        // @see https://bugs.php.net/bug.php?id=80431 for more info.
        if (array_key_exists('fragment', $parsedUrl) && empty($parsedUrl['fragment'])) {
            unset($parsedUrl['fragment']);
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
        $parsedUrl = parse_url($url);

        // encode each segment of path.
        if (array_key_exists('path', $parsedUrl) && is_scalar($parsedUrl['path'])) {
            $expPath = explode('/', $parsedUrl['path']);
            foreach ($expPath as $index => $segment) {
                $expPath[$index] = rawurlencode($segment);
            }
            $parsedUrl['path'] = implode('/', $expPath);
            unset($expPath);
        }

        // remove query, fragment, etc if empty.
        // @see https://bugs.php.net/bug.php?id=80431 for more info.
        if (array_key_exists('fragment', $parsedUrl) && empty($parsedUrl['fragment'])) {
            unset($parsedUrl['fragment']);
        }
        if (array_key_exists('query', $parsedUrl) && empty($parsedUrl['query'])) {
            unset($parsedUrl['query']);
        }

        $output = $this->buildUrl($parsedUrl);

        unset($parsedUrl);
        return $output;
    }// rawUrlEncodeSegments


    /**
     * Encode the username and password on the URL use `rawurlencode()`.
     * 
     * This will not encode other parts.
     *
     * @since 1.0.5
     * @param string $url The original URL without encoded.
     * @return string Return URL encoded only username and password.
     */
    public function rawUrlEncodeUsernamePassword(string $url): string
    {
        $parsedUrl = parse_url($url);

        if (array_key_exists('user', $parsedUrl)) {
            $parsedUrl['user'] = rawurlencode($parsedUrl['user']);
        }
        if (array_key_exists('pass', $parsedUrl)) {
            $parsedUrl['pass'] = rawurlencode($parsedUrl['pass']);
        }

        $output = $this->buildUrl($parsedUrl);

        unset($parsedUrl);
        return $output;
    }// rawUrlEncodeUsernamePassword


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
     * @since 1.0.2
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
