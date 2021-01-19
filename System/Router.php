<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\System;


/**
 * Router class.
 * 
 * @since 0.1
 * @method mixed filterMethod(mixed $method) Filter method to replace `any` method to all available methods and upper case for method array.
 */
class Router
{


    /**
     * @var \Rdb\System\Container
     */
    protected $Container;


    /**
     * Router class.
     * 
     * @param \Rdb\System\Container $Container The DI container class.
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        $this->Container = $Container;
    }// __construct


    /**
     * Setup and then dispatch the route.
     * 
     * @return array
     */
    public function dispatch()
    {
        $method = ($_SERVER['REQUEST_METHOD'] ?? '');

        $Url = new Libraries\Url();
        $urlPath = $Url->getPath();
        unset($Url);

        // decode URL
        $urlPath = rawurldecode($urlPath);

        if (empty($urlPath)) {
            // if url is nothing, this means it is root url. add just slash '/' to prevent 404 error.
            $urlPath = '/';
        }

        return $this
            ->setupRoute()
            ->dispatch(
                $method, 
                $urlPath
            );
    }// dispatch


    /**
     * Filter method to replace `any` method to all available methods and upper case for method array.
     * 
     * @param mixed $method
     * @return mixed
     */
    protected function filterMethod($method)
    {
        if (is_string($method) && strtolower($method) === 'any') {
            return ['GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH'];
        }

        if (is_array($method)) {
            $method = array_map('strtoupper', $method);
        } elseif (is_string($method)) {
            $method = strtoupper($method);
        }

        return $method;
    }// filterMethod


    /**
     * Get full controller class name and full method name from string or handler.
     * 
     * @param string $controllerMethod The handle or controller, class string. Example: `'Contact:index'` will be `'ContactController'` and `'indexAction'`.
     * @return array Return 2D array where the first array is the controller class name and second array is method name.
     *                          If the `$controllerMethod` is not valid Controller:method then it will be return only first array value and second array is empty.
     */
    public function getControllerMethodName(string $controllerMethod): array
    {
        if (stripos($controllerMethod, ':') === false) {
            return [$controllerMethod, ''];
        }

        list($controllerClass, $method) = explode(':', $controllerMethod);

        $controllerClass .= 'Controller';
        $method .= 'Action';

        return [
            $controllerClass,
            $method,
        ];
    }// getControllerMethodName


    /**
     * Setup the routes and create dispatcher.
     * 
     * @return \FastRoute\Dispatcher
     */
    protected function setupRoute()
    {
        if ($this->Container->has('Profiler')) {
            /* @var $Profiler \Rdb\System\Libraries\Profiler */
            $Profiler = $this->Container->get('Profiler');
            $Profiler->Console->timeload('Before setup route.', __FILE__, __LINE__, 'rdb_setup_route');
            $Profiler->Console->memoryUsage('Before setup route.', __FILE__, (__LINE__ - 1), 'rdb_setup_route');
        }

        /* @var $Config \Rdb\System\Config */
        if ($this->Container->has('Config')) {
            $Config = $this->Container->get('Config');
            $Config->setModule('');
        } else {
            $Config = new Config();
        }

        /* @var $Modules \Rdb\System\Modules */
        if ($this->Container->has('Modules')) {
            $Modules = $this->Container->get('Modules');
            $Modules->setCurrentModule('');
        } else {
            $Modules = new Modules($this->Container);
        }

        // create anonymous function for route definition call.
        // this will not being called if config was set to use cache.
        $routeDefinition = function (\FastRoute\RouteCollector $Rc) use ($Config, $Modules) {
            /* @var $Config \Rdb\System\Config */
            /* @var $Modules \Rdb\System\Modules */
            require_once $Config->getFile('routes');
        
            // get routes config in all modules.
            $enabledModules = $Modules->getModules();
            if (is_array($enabledModules)) {
                foreach ($enabledModules as $module) {
                    $Config->setModule($module);// set module to get config from the specific module.
                    $configFile = $Config->getFile('routes');
                    if (!empty($configFile)) {
                        require_once $configFile;
                    }
                    unset($configFile);
                    $Config->setModule('');// always reset `setModule()` to get config from main app.
                }// endforeach;
                unset($module);
            }
            unset($enabledModules);
        };

        $cacheFolder = 'cache/fastroute';
        $cacheFile = 'routes.php';
        if ($Config->get('routesCache', 'app', false) === true) {
            $cacheDisabled = false;
            $cacheExpireDate = $Config->get('routesCacheExpire', 'app', 30);

            $FileSystem = new Libraries\FileSystem(STORAGE_PATH);
            // create routes cache folder if not exists.
            $FileSystem->createFolder($cacheFolder);

            // check that cache file expired then delete it.
            $fileTs = $FileSystem->getTimestamp($cacheFolder . '/' . $cacheFile);
            if ($fileTs !== false) {
                $dayOld = ((time()-$fileTs)/60/60/24);
                if ($dayOld > $cacheExpireDate) {
                    unlink(STORAGE_PATH . '/' . $cacheFolder . '/' . $cacheFile);
                }
                unset($dayOld);
            }
            unset($cacheExpireDate, $FileSystem, $fileTs);
        } else {
            $cacheDisabled = true;
        }

        $Dispatcher = \FastRoute\cachedDispatcher($routeDefinition, [
            'cacheFile' => STORAGE_PATH . '/' . $cacheFolder . '/' . $cacheFile,
            'cacheDisabled' => $cacheDisabled,
        ]);
        unset($cacheDisabled, $cacheFile, $cacheFolder, $routeDefinition);

        if (isset($Profiler)) {
            $Profiler->Console->timeload('After setup route.', __FILE__, __LINE__, 'rdb_setup_route');
            $Profiler->Console->memoryUsage('After setup route.', __FILE__, (__LINE__ - 1), 'rdb_setup_route');
            unset($Profiler);
        }

        return $Dispatcher;
    }// setupRoute


}
