<?php
/**
 * RundizBones main application class.
 *
 * @package RundizBones
 * @version 1.1.9dev-20241226
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\System;


/**
 * The framework kernel class.
 * 
 * @since 0.1
 */
class App
{


    /**
     * @var \Rdb\System\Config The system configuration class.
     */
    protected $Config;


    /**
     * @var \Rdb\System\Container The DI container.
     */
    protected $Container;


    /**
     * @var \Rdb\System\Libraries\Logger Logger class.
     */
    protected $Logger;


    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->checkRequirements();

        $this->Container = new Container();
        $this->Container['Config'] = function ($c) {
            return new \Rdb\System\Config();
        };
        $this->Config = $this->Container['Config'];
    }// __construct


    /**
     * Add dependency injection container to use between classes.
     */
    protected function addDependencyInjection()
    {
        // DB class.
        $this->Container['Db'] = function ($c) {
            return new Libraries\Db($c);
        };

        // register autoload modules.
        $Modules = new Modules($this->Container);
        $Modules->registerAutoload();
        $this->Container['Modules'] = function ($c) use ($Modules) {
            return $Modules;
        };
        unset($Modules);

        // inject logger to log message to file and profiler.
        $this->Container['Logger'] = function ($c) {
            return new Libraries\Logger($c);
        };
        $this->Logger = $this->Container['Logger'];
    }// addDependencyInjection


    /**
     * Check requirements such as constants, PHP version.
     * 
     * Maybe set constant or throw the exception if not defined.<br>
     * This method was called from `__construct()`.
     * 
     * @since 1.1.7
     * @throws \Exception Throw the exception if required constants are not defined.
     * @throws \RuntimeException Throw \RuntimeException if PHP version does not met requirement.
     */
    protected function checkRequirements()
    {
        if (!defined('APP_ENV')) {
            define('APP_ENV', 'production');
        }

        // Check required constants.
        $requiredConstants = ['ROOT_PATH', 'STORAGE_PATH', 'MODULE_PATH', 'PUBLIC_PATH'];
        foreach ($requiredConstants as $eachConstant) {
            if (!defined($eachConstant)) {
                throw new \Exception('The constant ' . $eachConstant . ' is not defined.');
            }
        }// endforeach;
        unset($eachConstant, $requiredConstants);

        // Check required PHP version.
        if (version_compare(phpversion(), '7.1', '<')) {
            throw new \RuntimeException('Required at least PHP 7.1');
        }
    }// checkRequirements


    /**
     * Get container object.
     * 
     * @return \Rdb\System\Container|null Return container object or `null` if it was not exists.
     */
    public function getContainer()
    {
        if ($this->Container instanceof Container) {
            return $this->Container;
        }

        return null;
    }// getContainer


    /**
     * Get middleware configuration from main app and modules.
     * 
     * @since 1.1.1
     * @param string $middlewareConfigPart Middleware configuration part. Accept 'beforeMiddleware', 'afterMiddleware'.
     * @return array Return middlewares from selected part.
     */
    private function getMiddlewareConfig(string $middlewareConfigPart = 'beforeMiddleware'): array
    {
        $middlewareConfig = $this->Config->get('ALL', 'middleware', []);
        if (!array_key_exists($middlewareConfigPart, $middlewareConfig)) {
            $middlewareConfig[$middlewareConfigPart] = [];
        }

        // merge config from other modules. ------------------------
        $Modules = $this->Container->get('Modules');
        /* @var $Modules \Rdb\System\Modules */
        $enabledModules = $Modules->getModules();
        unset($Modules);

        if (is_array($enabledModules)) {
            foreach ($enabledModules as $module) {
                $this->Config->setModule($module);// set module to get config from the specific module.
                $configValues = $this->Config->getWithoutCache('ALL', 'middleware', []);

                if (is_array($configValues) && !empty($configValues)) {
                    if (array_key_exists($middlewareConfigPart, $configValues)) {
                        $middlewareConfig[$middlewareConfigPart] = Libraries\ArrayUtil::staticArrayCustomMerge(
                            $middlewareConfig[$middlewareConfigPart], 
                            $configValues[$middlewareConfigPart]
                        );
                    }
                }

                unset($configValues);
            }// endforeach;
            $this->Config->setModule('');// restore config module to default.
            unset($module);
        }
        unset($enabledModules);
        // end merge config from other modules. --------------------

        return $middlewareConfig;
    }// getMiddlewareConfig


    /**
     * Get routes for error handler from main app and maybe replaced by other modules.
     * 
     * @since 1.1.1
     * @return array Return configuration of routes for error handler.
     */
    private function getRouteErrorConfig(): array
    {
        $errors = $this->Config->get('ALL', 'error', []);

        // replace config from other modules. ------------------------
        $Modules = $this->Container->get('Modules');
        /* @var $Modules \Rdb\System\Modules */
        $enabledModules = $Modules->getModules();
        unset($Modules);

        if (is_array($enabledModules)) {
            foreach ($enabledModules as $module) {
                $this->Config->setModule($module);// set module to get config from the specific module.
                $configValues = $this->Config->getWithoutCache('ALL', 'error', []);

                if (is_array($configValues) && !empty($configValues)) {
                    $errors = array_replace($errors, $configValues);
                }

                unset($configValues);
            }// endforeach;
            $this->Config->setModule('');// restore config module to default.
            unset($module);
        }
        unset($enabledModules);
        // end replace config from other modules. --------------------

        return $errors;
    }// getRouteErrorConfig


    /**
     * Load [after] middleware from config to run after the application started.
     * 
     * @param string|null $response The output content from application controller.
     */
    protected function loadAfterMiddleware(string $response)
    {
        $middlewareConfig = $this->getMiddlewareConfig('afterMiddleware');

        if (
            is_array($middlewareConfig) && 
            array_key_exists('afterMiddleware', $middlewareConfig) &&
            is_array($middlewareConfig['afterMiddleware'])
        ) {
            ksort($middlewareConfig['afterMiddleware']);
            foreach ($middlewareConfig['afterMiddleware'] as $middleware) {
                if (is_string($middleware) && strpos($middleware, ':') !== false) {
                    list($middlewareClass, $middlewareMethod) = explode(':', $middleware);
                    $this->Logger->write(
                        'system/app', 
                        0, 
                        'Calling [after] middleware {class}->{method}()', 
                        ['class' => $middlewareClass, 'method' => $middlewareMethod],
                        ['dontLogProfiler' => true]
                    );

                    // call middleware without conditional class exists.
                    // this is for throwing the error message for devs to notice it.
                    $newObject = new $middlewareClass($this->Container);
                    $response = call_user_func([$newObject, $middlewareMethod], $response);
                    unset($middlewareClass, $middlewareMethod, $newObject);
                }
            }// endforeach;
            unset($middleware);
        }

        unset($middlewareConfig);
        return $response;
    }// loadAfterMiddleware


    /**
     * Load [before] middleware from config to run before the application start.
     * 
     * The middleware is anything between kernel and application (MVC in this case).
     * 
     * @link https://en.wikipedia.org/wiki/Middleware Reference.
     * @link https://stackoverflow.com/questions/2904854/what-is-middleware-exactly Reference.
     * @return string|null Return the response content (before application controller start).
     */
    protected function loadBeforeMiddleware()
    {
        $middlewareConfig = $this->getMiddlewareConfig();

        $response = '';

        if (
            is_array($middlewareConfig) && 
            array_key_exists('beforeMiddleware', $middlewareConfig) && 
            is_array($middlewareConfig['beforeMiddleware'])
        ) {
            ksort($middlewareConfig['beforeMiddleware']);
            foreach ($middlewareConfig['beforeMiddleware'] as $middleware) {
                if (is_string($middleware) && strpos($middleware, ':') !== false) {
                    list($middlewareClass, $middlewareMethod) = explode(':', $middleware);
                    $this->Logger->write(
                        'system/app', 
                        0, 
                        'Calling [before] middleware {class}->{method}()', 
                        ['class' => $middlewareClass, 'method' => $middlewareMethod],
                        ['dontLogProfiler' => true]
                    );

                    // call middleware without conditional class exists.
                    // this is for throwing the error message for devs to notice it.
                    $newObject = new $middlewareClass($this->Container);
                    $response = call_user_func([$newObject, $middlewareMethod], $response);
                    unset($middlewareClass, $middlewareMethod, $newObject);
                }
            }// endforeach;
            unset($middleware);
        }

        unset($middlewareConfig);
        return $response;
    }// loadBeforeMiddleware


    /**
     * Process the controller and get the output content.
     * 
     * This method process 404 error if class, controller, method - one of these was not found.
     * 
     * @param string $handler Route handler.
     * @param array $arguments Route arguments.
     * @return string|null Return output content of that controller.
     */
    protected function processController(string $handler, array $arguments)
    {
        if (strtolower(PHP_SAPI) === 'cli') {
            return ;
        }

        if ($this->Container->has('Profiler')) {
            /* @var $Profiler \Rdb\System\Libraries\Profiler */
            $Profiler = $this->Container->get('Profiler');
            $Profiler->Console->timeload('Before process controller.', __FILE__, __LINE__, 'rdb_process_controller');
            $Profiler->Console->memoryUsage('Before process controller.', __FILE__, (__LINE__ - 1), 'rdb_process_controller');
        }

        $Router = new Router($this->Container);
        list($controllerClass, $method) = $Router->getControllerMethodName($handler);

        if (!class_exists($controllerClass)) {
            $pageNotFound = true;
        } else {
            $ReflectionClassTargetInstance = new \ReflectionClass('\\Rdb\\System\\Core\\Controllers\\BaseController');
            $ReflectionController = new \ReflectionClass($controllerClass);
            $controllerInstance = $ReflectionController->newInstanceWithoutConstructor();

            if (
                !$ReflectionClassTargetInstance->isInstance($controllerInstance) ||// controller not found OR
                !$ReflectionController->hasMethod($method)// method not found
            ) {
                $pageNotFound = true;
            }

            unset($controllerInstance, $ReflectionClassTargetInstance, $ReflectionController);
        }

        if (isset($pageNotFound) && $pageNotFound === true) {
            // if class not found or controller not found or method not found.
            // display in profiler.
            if (isset($Profiler)) {
                $Profiler->Console->log('error', 'Controller class or method was not found (' . $controllerClass . '::' . $method . ').', __FILE__, (__LINE__ - 16));
            }

            // force display 404 page.
            $errors = $this->getRouteErrorConfig();
            list($controllerClass, $method) = $Router->getControllerMethodName($errors['404']);
        }
        unset($pageNotFound, $Router);

        ob_start();
        $newObject = new $controllerClass($this->Container);
        $response = call_user_func_array([$newObject, $method], array_values($arguments));
        $responseWithoutReturn = ob_get_contents();// in case the controller just echo out immediately.
        unset($controllerClass, $method, $newObject);

        if (!empty($responseWithoutReturn)) {
            $responseWithoutReturn .= $response;// append the return content to the echo out.
            $response = $responseWithoutReturn;
        }

        unset($responseWithoutReturn);
        ob_end_clean();

        if (isset($Profiler)) {
            $Profiler->Console->timeload('After process controller.', __FILE__, __LINE__, 'rdb_process_controller');
            $Profiler->Console->memoryUsage('After process controller.', __FILE__, (__LINE__ - 1), 'rdb_process_controller');
            unset($Profiler);
        }

        return $response;
    }// processController


    /**
     * Process headers.
     * 
     * @param string|null $response The output content from application controller.
     */
    protected function processHeaders($response = '')
    {
        $addContentLength = $this->Config->get('addContentLengthHeader', 'app', true);

        if ($addContentLength === true && !headers_sent()) {
            // count response content as bytes ( https://www.php.net/manual/en/function.mb-strlen.php#77040 ) for display in content length.
            header('Content-Length: ' . mb_strlen($response, '8bit'));
        }
        unset($addContentLength);
    }// processHeaders


    /**
     * Process route.
     * 
     * Also process 404, 405 error.
     * 
     * @return array Return array these keys:<br>
     *                      `status` (string) The status of the route. Example: found, notfound, methodnotallowed.<br>
     *                      `handler` (string) The handler for this route. It is controller:method.<br>
     *                      `args` (array, optional) The route arguments. Maybe available if route was found.
     */
    protected function processRoute(): array
    {
        if (strtolower(PHP_SAPI) === 'cli') {
            return [];
        }

        if ($this->Container->has('Profiler')) {
            /* @var $Profiler \Rdb\System\Libraries\Profiler */
            $Profiler = $this->Container->get('Profiler');
            $Profiler->Console->timeload('Before process route.', __FILE__, __LINE__, 'rdb_process_route');
            $Profiler->Console->memoryUsage('Before process route.', __FILE__, (__LINE__ - 1), 'rdb_process_route');
        }

        $Router = new Router($this->Container);
        $routeInfo = $Router->dispatch();
        unset($Router);

        $output = [];

        if ($routeInfo[0] === \FastRoute\Dispatcher::FOUND) {
            $output['handler'] = $routeInfo[1];
            $output['args'] = $routeInfo[2];
            $output['status'] = 'found';
        } elseif ($routeInfo[0] === \FastRoute\Dispatcher::NOT_FOUND) {
            $output['status'] = 'notfound';
        } elseif ($routeInfo[0] === \FastRoute\Dispatcher::METHOD_NOT_ALLOWED) {
            $output['args'] = ($routeInfo[1] ?? '');
            $output['status'] = 'methodnotallowed';
        }

        if ($routeInfo[0] !== \FastRoute\Dispatcher::FOUND) {
            $errors = $this->getRouteErrorConfig();

            if ($output['status'] === 'notfound') {
                $output['handler'] = $errors['404'];
            } elseif ($output['status'] === 'methodnotallowed') {
                $output['handler'] = $errors['405'];
            }
            unset($errors);
        }

        if (isset($Profiler)) {
            $Profiler->Console->timeload('After process route.', __FILE__, __LINE__, 'rdb_process_route');
            $Profiler->Console->memoryUsage('After process route.', __FILE__, (__LINE__ - 1), 'rdb_process_route');
            unset($Profiler);
        }

        return $output;
    }// processRoute


    /**
     * Run the application.
     */
    public function run()
    {
        // add dependency injection container.
        $this->addDependencyInjection();

        $this->Logger->write(
            'system/app',
            0,
            'Application run. This is most beginning part of the application after add dependency injection container.',
            [],
            ['dontLogProfiler' => true]
        );

        // load [before] middleware from config to run before the application start.
        $response = $this->loadBeforeMiddleware();

        if (strtolower(PHP_SAPI) !== 'cli') {
            // if it is not running via CLI.
            // process route (including 404, 405 error).
            $routeInfo = $this->processRoute();
            $args = [];
            if (isset($routeInfo['args'])) {
                $args = $routeInfo['args'];
            }

            $this->Logger->write(
                'system/app',
                0,
                'Application run after processed route, starting to processing controller.',
                ['status' => $routeInfo['status'], 'handler' => $routeInfo['handler'], 'args' => $args],
                ['dontLogProfiler' => true]
            );

            // process the controller for certain route (including 404, 405 error).
            if (isset($routeInfo['handler'])) {
                $response .= $this->processController($routeInfo['handler'], $args);
            }
            unset($args, $routeInfo);
        } else {
            // if running via CLI.
            $this->Logger->write(
                'system/app',
                0,
                'Application is starting to run CLI command.',
                [],
                ['dontLogProfiler' => true]
            );

            $Cli = new Cli($this->Container);
            $Cli->run();
            unset($Cli);
        }

        // load [after] middleware from config to run after the application started.
        $response = $this->loadAfterMiddleware($response);
        // process headers.
        $this->processHeaders($response);

        $this->Logger->write(
            'system/app',
            0,
            'Application run response. Finish all process, displaying the result.',
            [],
            ['dontLogProfiler' => true]
        );

        echo $response;
    }// run


}
