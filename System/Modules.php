<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\System;


/**
 * Modules class.
 * 
 * @since 0.1
 */
class Modules
{


    /**
     * @var \Rdb\System\Container
     */
    protected $Container;


    /**
     * @var string The current module, detected from `setCurrentModule()` method.
     */
    protected $currentModule = '';


    /**
     * @var array The module system name (folder name) that was registered to auto load and also enabled.
     */
    protected $modules = [];


    /**
     * The class constructor.
     * 
     * @param \Rdb\System\Container $Container The DI container class.
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        $this->Container = $Container;
    }// __construct


    /**
     * Copy moduleComposer.json to main application composer.json.
     * 
     * @param string $moduleSystemName The module system name (folder name).
     * @return bool Return `true` on success, `false` on failure.
     */
    public function copyComposer(string $moduleSystemName): bool
    {
        $moduleComposerString = file_get_contents(MODULE_PATH . DIRECTORY_SEPARATOR . $moduleSystemName . DIRECTORY_SEPARATOR . 'moduleComposer.json');
        $rootComposerString = file_get_contents(ROOT_PATH . DIRECTORY_SEPARATOR . 'composer.json');
        $ModuleComposerObject = json_decode($moduleComposerString);
        $RootComposerObject = json_decode($rootComposerString);
        unset($moduleComposerString, $rootComposerString);

        // begins add require and require-dev (if available) to application composer.json.
        if (isset($ModuleComposerObject->require) && is_object($ModuleComposerObject->require)) {
            $RootComposerObject = $this->copyComposerPackageIfNewer($RootComposerObject, $ModuleComposerObject);
        }
        if (isset($ModuleComposerObject->{'require-dev'}) && is_object($ModuleComposerObject->{'require-dev'})) {
            $RootComposerObject = $this->copyComposerPackageIfNewer($RootComposerObject, $ModuleComposerObject, 'require-dev');
        }

        // finished add, write to json string with pretty print and then write into file.
        $newRootComposerString = json_encode($RootComposerObject, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
        $writeComposerResult = file_put_contents(ROOT_PATH . DIRECTORY_SEPARATOR . 'composer.json', $newRootComposerString);

        // clear
        unset($ModuleComposerObject, $newRootComposerString, $RootComposerObject);

        // finished copy composer.json
        if ($writeComposerResult !== false) {
            return true;
        } else {
            return false;
        }
    }// copyComposer


    /**
     * Copy composer package from module to root if module is using newer version range.
     * 
     * This method was called from `copyComposer()`.
     * 
     * @since 1.1.4
     * @param object $RootComposerObject
     * @param object $ModuleComposerObject
     * @param string $copyProperty The property on moduleComposer.json that will be copy. Accept 'require', 'require-dev'.
     * @return object Return modified root composer object.
     */
    private function copyComposerPackageIfNewer($RootComposerObject, $ModuleComposerObject, string $copyProperty = 'require')
    {
        if (!is_object($RootComposerObject) || !is_object($ModuleComposerObject)) {
            return $RootComposerObject;
        }
        if (!in_array($copyProperty, ['require', 'require-dev'])) {
            return $RootComposerObject;
        }

        if (isset($ModuleComposerObject->{$copyProperty}) && is_object($ModuleComposerObject->{$copyProperty})) {
            // if found module's composer object and its target copy property.
            foreach ($ModuleComposerObject->{$copyProperty} as $name => $versionRange) {
                $allowedMerge = true;
                if (isset($RootComposerObject->{$copyProperty}->{$name})) {
                    // if this composer package is already in root composer.json.
                    $VersionP = new \Composer\Semver\VersionParser();
                    $RootCPVersionRange = $VersionP->parseConstraints($RootComposerObject->{$copyProperty}->{$name});// root's composer package version range.
                    $ModuleCPVersionRange = $VersionP->parseConstraints($versionRange);// module's composer package version range.
                    if ($RootCPVersionRange->getUpperBound()->compareTo($ModuleCPVersionRange->getUpperBound(), '>')) {
                        // if root's composer package version range is using newer than module's one.
                        // don't allowed.
                        $allowedMerge = false;
                    }
                    unset($ModuleCPVersionRange, $RootCPVersionRange, $VersionP);
                }

                if (true === $allowedMerge) {
                    $RootComposerObject->{$copyProperty}->{$name} = $versionRange;
                }
                unset($allowedMerge);
            }// endforeach;
            unset($name, $versionRange);
        }

        return $RootComposerObject;
    }// copyComposerPackageIfNewer


    /**
     * Copy moduleComposer.json from all modules into main app's composer.json
     * 
     * @param bool $enabledOnly Set to `true` to strict to enabled modules only, `false` for any modules existing.
     * @return array Return associative array with `modulesWithComposer` key as total modules that has moduleComposer.json, 
     *                          `successCopied` key as total success copied moduleComposer.json,
     *                          `failedModules` key as list of failed module that unable to copy moduleComposer.json
     */
    public function copyComposerAllModules(bool $enabledOnly = true): array
    {
        $modules = $this->getModules($enabledOnly);
        $success = 0;
        $totalModulesHasComposer = 0;
        $failedModules = [];

        if (is_array($modules)) {
            foreach ($modules as $module) {
                if (
                    is_file(MODULE_PATH . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'moduleComposer.json') &&
                    is_file(ROOT_PATH . DIRECTORY_SEPARATOR . 'composer.json')
                ) {
                    if ($enabledOnly === true && is_file(MODULE_PATH . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . '.disabled')) {
                        // if enabled only but found .disabled file, skip it.
                        continue;
                    }
                    $totalModulesHasComposer++;
                    $result = $this->copyComposer($module);

                    if ($result === true) {
                        $success++;
                    } else {
                        $failedModules[] = $module;
                    }
                    unset($result);
                }
            }// endforeach;
            unset($module);
        }

        unset($modules);
        return [
            'modulesWithComposer' => $totalModulesHasComposer,
            'successCopied' => $success,
            'failedModules' => $failedModules,
        ];
    }// copyComposerAllModules


    /**
     * Set module to be disabled or enabled depend on `$enable` argument.
     * 
     * @since 1.1.6
     * @param bool $enable Set to `true` (default) to enable the module, set to `false` to disable it.
     * @return bool Return `true` on success, `false` on failure.
     */
    public function enable(string $moduleSystemName, bool $enable = true): bool
    {
        $FileSystem = new \Rdb\System\Libraries\FileSystem(MODULE_PATH . DIRECTORY_SEPARATOR . $moduleSystemName);
        if (true === $enable) {
            // if command to enable
            $deleteResult = $FileSystem->deleteFile('.disabled');
            $fileExists = $FileSystem->isFile('.disabled', false);
            return ($deleteResult === true && $fileExists === false);
        } elseif (false === $enable) {
            // if command to disable
            if (!$FileSystem->isFile('.disabled', false)) {
                $createResult = $FileSystem->createFile('.disabled', '');
            } else {
                $createResult = true;
            }
            $fileExists = $FileSystem->isFile('.disabled', false);
            return ($createResult !== false && $fileExists === true);
        }

        return false;
    }// enable


    /**
     * Execute a module controller/method.
     * 
     * This is useful for widgetize, modular process.
     * 
     * To check that is this a module execute, use `$_SERVER['RUNDIZBONES_MODULEEXECUTE']`.<br>
     * Example:
     * 
     * <pre>
     * if (!isset($_SERVER['RUNDIZBONES_MODULEEXECUTE']) || (isset($_SERVER['RUNDIZBONES_MODULEEXECUTE']) && $_SERVER['RUNDIZBONES_MODULEEXECUTE'] !== 'true')) {
     *     // this is NOT module execute.
     * } else {
     *     // this is module execute.
     * }
     * </pre>
     * 
     * Before call this method:<br>
     * Make sure that the module you specified is enabled otherwise the autoload will not working.
     * 
     * @param string $controllerMethod The module's `controller:method`. The class name and its method should not has suffix.
     *                                                      Example: `Rdb\Modules\MyModule\Controllers\MyPage:index` will be automatically converted to `Rdb\Modules\MyModule\Controllers\MyPageController:indexAction`.
     * @param array $args The arguments of controller's method.
     * @return string Return response content of `controller:method`.
     * @throws \InvalidArgumentException Throw invalid argument exception if `controller:method` format is invalid.
     */
    public function execute(string $controllerMethod, array $args = []): string
    {
        $controllerMethod = '\\' . ltrim($controllerMethod, '\\');

        if (stripos($controllerMethod, ':') === false) {
            throw new \InvalidArgumentException('Invalid argument value for $controllerMethod. The controller method format must be Rdb\Modules\YourModule\Controller:method.');
        }

        $Router = new Router($this->Container);
        list($controllerClass, $method) = $Router->getControllerMethodName($controllerMethod);
        unset($Router);

        if ($this->Container->has('Logger')) {
            // if there is logger class.
            /* @var $Logger \Rdb\System\Libraries\Logger */
            $Logger = $this->Container->get('Logger');
            // get backtrace for logging.
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
            $backtraceCalledFrom = 'not found!';
            if (is_array($backtrace) && isset($backtrace[0]['file']) && isset($backtrace[0]['line'])) {
                $backtraceCalledFrom = $backtrace[0]['file'] . ' line ' . $backtrace[0]['line'];
            }
            unset($backtrace);
        }

        if (class_exists($controllerClass)) {
            // if controller class exists.
            $ReflectionController = new \ReflectionClass($controllerClass);
            if ($ReflectionController->hasMethod($method)) {
                // if method of that controller exists.
                if (!isset($_SERVER['RUNDIZBONES_MODULEEXECUTE'])) {
                    $_SERVER['RUNDIZBONES_MODULEEXECUTE'] = 'true';
                }

                ob_start();
                $newObject = $ReflectionController->newInstance($this->Container);
                $output = call_user_func_array([$newObject, $method], array_values($args));
                $responseWithoutReturn = ob_get_contents();// in case the controller just echo out immediately.
                if (!empty($responseWithoutReturn)) {
                    $responseWithoutReturn .= $output;// append the return content to the echo out.
                    $output = $responseWithoutReturn;
                }
                unset($newObject, $responseWithoutReturn);
                ob_end_clean();

                if (isset($Logger)) {
                    $Logger->write(
                        'system/modules', 
                        0, 
                        'The module class method was executed. ({class_method})', 
                        ['class_method' => $controllerClass . ':' . $method, 'caller' => $backtraceCalledFrom]
                    );
                }
            } else {
                // if method is not exists.
                if (isset($Logger)) {
                    $Logger->write(
                        'system/modules', 
                        2, 
                        'The module class method was not exists. ({class_method})', 
                        ['class_method' => $controllerClass . ':' . $method, 'caller' => $backtraceCalledFrom]
                    );
                }
            }
            unset($ReflectionController);
        } else {
            // if controller class is not exists.
            if (isset($Logger)) {
                $Logger->write(
                    'system\modules', 
                    2, 
                    'The module controller was not exists. ({class})', 
                    ['class' => $controllerClass, 'caller' => $backtraceCalledFrom]
                );
            }
        }

        unset($backtraceCalledFrom, $controllerClass, $Logger, $method);

        if (isset($output)) {
            return $output;
        }
        return '';
    }// execute


    /**
     * Check if module exists.
     * 
     * @param string $moduleSystemName The module system name (folder name).
     * @param bool $enabledOnly Set to `true` to check for enabled module only. Set to `false` to check for existing but don't care that it is enabled or not.
     * @return bool Return `true` if exists, `false` for not exists.
     */
    public function exists(string $moduleSystemName, bool $enabledOnly = true): bool
    {
        if ($enabledOnly === true) {
            return in_array($moduleSystemName, $this->modules);
        } else {
            return is_dir(MODULE_PATH . DIRECTORY_SEPARATOR . $moduleSystemName);
        }
    }// exists


    /**
     * Get current module.
     * 
     * To use this, you must call to `setCurrentModule()` method before.
     * 
     * @return string Return the current module.
     */
    public function getCurrentModule(): string
    {
        return $this->currentModule;
    }// getCurrentModule


    /**
     * Get list of modules.
     * 
     * To get all modules after called to `registerAutoload()` method.
     * 
     * @param bool $enabledOnly Set to `true` (default) to get only enabled modules, set to `false` to get all.
     * @return array Return modules system name in array.
     */
    public function getModules(bool $enabledOnly = true): array
    {
        if ($enabledOnly === true) {
            return $this->modules;
        } else {
            $It = new \FilesystemIterator(MODULE_PATH);
            $modules = [];

            foreach ($It as $FileInfo) {
                if ($FileInfo->isDir()) {
                    $modules[] = $FileInfo->getFilename();
                }
            }// endforeach;

            unset($FileInfo, $It);
            return $modules;
        }
    }// getModules


    /**
     * Get module system name (module folder name) from full path to such as module folder or any file in that module.
     * 
     * @param string $path Full path to module folder or file in that module.
     * @return string Return just module system name if found that full path is in module folder. If full path is not in module folder then it will return SystemCore.
     */
    public function getModuleSystemName(string $path): string
    {
        $output = 'SystemCore';

        // normalize $path and MODULE_PATH to replace any directory seperator to slash (/).
        $path = str_replace([DIRECTORY_SEPARATOR, '\\', '/'], '/', $path);
        $modulePath = str_replace([DIRECTORY_SEPARATOR, '\\', '/'], '/', MODULE_PATH);

        if (stripos($path, $modulePath) !== false) {
            $removeModulePath = str_replace($modulePath, '', $path);
            $pathArray = explode('/', $removeModulePath);
            unset($removeModulePath);

            if (is_array($pathArray)) {
                if (isset($pathArray[0]) && !empty($pathArray[0])) {
                    $output = $pathArray[0];
                } elseif (isset($pathArray[1]) && !empty($pathArray[1])) {
                    $output = $pathArray[1];
                }
            }

            unset($pathArray);
        }

        unset($modulePath);
        return $output;
    }// getModuleSystemName


    /**
     * Register auto load for modules that is not disabled.
     * 
     * This class was called at very first from `\Rdb\System\App` class. So, it has nothing like `$Profiler` to access.
     */
    public function registerAutoload()
    {
        $It = new \FilesystemIterator(MODULE_PATH);
        // use autoload from composer as we already use composer. see https://getcomposer.org/doc/01-basic-usage.md#autoloading for reference.
        $Loader = require ROOT_PATH . DIRECTORY_SEPARATOR . 'System' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
        foreach ($It as $FileInfo) {
            if ($FileInfo->isDir()) {
                if (!is_file($FileInfo->getRealPath() . DIRECTORY_SEPARATOR . '.disabled')) {
                    // if there is no .disabled file in this module then it is enabled, register auto load for it.
                    $this->modules[] = $FileInfo->getFilename();
                    $Loader->addPsr4('Rdb\\Modules\\' . $FileInfo->getFilename() . '\\', $FileInfo->getRealPath());
                }
            }
        }// endforeach;
        unset($FileInfo, $It, $Loader);
    }// registerAutoload


    /**
     * Set current module from specific controller.
     * 
     * @param string $controller The controller class to check. To detect controller class, use `get_called_class()` function in the controller.
     */
    public function setCurrentModule(string $controller)
    {
        $controller = trim($controller, '\\');
        $explodedClass = explode('\\', $controller);

        if (
            isset($explodedClass[1]) && 
            isset($explodedClass[2]) &&
            $explodedClass[1] === 'System' &&
            $explodedClass[2] === 'Core'
        ) {
            $this->currentModule = 'Rdb\\System\\Core';
        } elseif (isset($explodedClass[2])) {
            $this->currentModule = $explodedClass[2];
        }

        unset($explodedClass);
    }// setCurrentModule


}
