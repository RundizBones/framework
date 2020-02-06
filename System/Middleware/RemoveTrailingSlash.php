<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace System\Middleware;


/**
 * Remove trailing slash from the URL by redirect it.
 *
 * @since 0.1
 */
class RemoveTrailingSlash
{


    /**
     * @var \System\Container
     */
    protected $Container;


    /**
     * The class constructor.
     * 
     * @param \System\Container $Container The DI container class.
     */
    public function __construct(\System\Container $Container)
    {
        $this->Container = $Container;
    }// __construct


    /**
     * Run the middleware.
     * 
     * @param string|null $response
     * @return string|null
     */
    public function run($response = '')
    {
        if (strtolower(PHP_SAPI) === 'cli') {
            // if running from CLI.
            // don't run this middleware here.
            return $response;
        }

        if ($this->Container->has('Profiler')) {
            /* @var $Profiler \System\Libraries\Profiler */
            $Profiler = $this->Container->get('Profiler');
            $Profiler->Console->timeload('Before begins trailing slash redirect process.', __FILE__, __LINE__, 'rdb_mdw_trailingslash');
        }

        $url = $_SERVER['REQUEST_URI'];

        $appInstalledPath = '';
        if (isset($_SERVER['SCRIPT_NAME'])) {
            if (stripos($url, $_SERVER['SCRIPT_NAME']) !== false) {
                $appInstalledPath = $_SERVER['SCRIPT_NAME'];
            } elseif (stripos($url, dirname($_SERVER['SCRIPT_NAME'])) !== false) {
                $appInstalledPath = dirname($_SERVER['SCRIPT_NAME']);
            }
        }

        $path = str_replace($appInstalledPath, '', $url);
        $Url = new \System\Libraries\Url();
        $path = $Url->removeQuerystring($path);
        unset($Url);

        if ($path !== '/' && substr($path, -1) === '/') {
            // if found trailing slash.
            $newUrl = $appInstalledPath . substr($path, 0, -1) . (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');

            if (isset($Profiler)) {
                $Profiler->Console->timeload('After trailing slash redirect process but before redirect.', __FILE__, __LINE__, 'rdb_mdw_trailingslash');
                unset($Profiler);
            }

            header('Expires: Fri, 01 Jan 1971 00:00:00 GMT');
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Cache-Control: post-check=0, pre-check=0', false);
            header('Pragma: no-cache');
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: ' . $newUrl);
            unset($newUrl, $url);
            exit();
        }

        if (isset($Profiler)) {
            $Profiler->Console->timeload('After trailing slash redirect process but has no redirect.', __FILE__, __LINE__, 'rdb_mdw_trailingslash');
            unset($Profiler);
        }

        unset($appInstalledPath, $path, $url);
        return $response;
    }// run


}
