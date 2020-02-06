<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace System;


/**
 * Views class.
 * 
 * @since 0.1
 */
class Views
{


    /**
     * @var \System\Modules
     */
    protected $Modules;


    /**
     * Load the views file.
     * 
     * @param \System\Container $Container The DI container class.
     */
    public function __construct(\System\Container $Container)
    {
        if ($Container->has('Modules')) {
            $this->Modules = $Container->get('Modules');
        }
    }// __construct


    /**
     * Locate views file.
     * 
     * @param string $viewsFile The views file name without .php extension.
     * @param string $currentModule The module folder name to locate views in there.
     *                                                  Default is null to auto detect current module but auto detect did not work in all case.
     * @return string Return full path to views file if the file was found.
     * @throws \RuntimeException Throw exception if views file was not found.
     */
    protected function locateViews(string $viewsFile, string $currentModule = null): string
    {
        if (empty($currentModule)) {
            // if current module was not set.
            // auto detect from back trace.
            $debugBacktrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
            if (
                isset($debugBacktrace[2]['class']) && 
                stripos($debugBacktrace[2]['class'], 'Modules\\') !== false
            ) {
                // if found modules in trace.
                $expClass = explode('\\', $debugBacktrace[2]['class']);
                if (isset($expClass[1])) {
                    // if found the module name.
                    $currentModule = $expClass[1];
                } else {
                    // if not found the module name.
                    $currentModule = $this->Modules->getCurrentModule();
                }
                unset($expClass);
            } else {
                // if not found modules in trace.
                $currentModule = $this->Modules->getCurrentModule();
            }
            unset($debugBacktrace);
        }

        $ds = DIRECTORY_SEPARATOR;
        if ($currentModule === 'System\\Core') {
            $moduleBasePath = ROOT_PATH . $ds . str_replace('\\', '/', $currentModule) . $ds . 'Views';
        } else {
            $moduleBasePath = MODULE_PATH . $ds . $currentModule . $ds . 'Views';
        }

        if (is_file($moduleBasePath . $ds . $viewsFile . '.php')) {
            unset($currentModule);
            return realpath($moduleBasePath . $ds . $viewsFile . '.php');
        } else {
            unset($ds);
            throw new \RuntimeException('The views file (' . $viewsFile . '.php) in the module (' . $currentModule . ') was not found.');
        }
    }// locateViews


    /**
     * Render the views file.
     * 
     * @param string $viewsFile The views file name.
     *                                          This depend on option `noLocateViews`.
     *                                          If this option was not set or set to `false` then the views file is locate from "Views" folder in the running module and no need to add ".php" extension.
     *                                          If this option was set to `true` then the views file MUST be full path to the file (.php extension is required).
     * @param array $data The data that will becomes variable in views file. Example `['foo' => 'bar'];` will becomes `echo $foo; // result is bar.`
     * @param array $options The options for render the views.
     *                                      `noLocateViews` Set to `true` to tell that views file is already full path, no need to locate full path to the file.<br>
     *                                      `viewsModule` Set the module folder name to locate views in there.
     * @return string Return views content.
     */
    public function render(string $viewsFile, array $data = [], array $options = []): string
    {
        $options += [
            'noLocateViews' => false,
        ];

        if (isset($options['noLocateViews']) && $options['noLocateViews'] === true) {
            // if option was set to NOT locate views, this means it is already full path.
            $viewsFullPath = $viewsFile;
        } else {
            // if option was set to locate views.
            if (
                isset($options['viewsModule']) && 
                is_string($options['viewsModule']) && 
                !empty(trim($options['viewsModule']))
            ) {
                $currentModule = trim($options['viewsModule']);
            } else {
                $currentModule = null;
            }

            $viewsFullPath = $this->locateViews($viewsFile, $currentModule);
        }

        extract($data);
        ob_start();
        include $viewsFullPath;// this can trigger error to notice devs if the file was not found.
        $viewsContent = ob_get_contents();
        ob_end_clean();

        unset($viewsFullPath);
        return $viewsContent;
    }// render


}
