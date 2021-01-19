<?php
/**
 * RundizBones system configuration class.
 *
 * Working on load configuration files for the framework.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\System;


/**
 * Load config files from /config folder from main application or modules in each environment that was set in the /public/index.php file.
 * 
 * This class can load config file, get config value, set config name and value (but not write to the file).
 *
 * @since 0.1
 */
class Config
{


    /**
     * @var array Contain loaded files with config name and values. The array format will be as follow.<pre>
     * array(
     *     'system\\core' => array(
     *         'config_file_without_extension' => 'its values', // (this is mixed type. maybe string, array, etc.)
     *         'other_file' => array(),
     *         // ...
     *     ),
     *     'ModuleSystemName' => array(
     *         // ...
     *     ),
     * )
     * </pre>
     */
    protected $loadedFiles = [];


    /**
     * @var string Module system name (folder name) that this class will get config.
     */
    protected $moduleSystemName = 'system\\core';


    /**
     * @var array Contain loaded files for use in debug.
     */
    public $traceLoadedFiles = [];


    /**
     * Magic get.
     * 
     * @since 1.1.1
     * @param string $name
     */
    public function __get(string $name)
    {
        if (property_exists($this, $name)) {
            return $this->{$name};
        }
    }// __get


    /**
     * Get the config value from specified name.
     * 
     * This is depend on `setModule()` method that telling it will get from main app or modules.
     * 
     * @param string $configKey The config name. this can set to `ALL` to get all the config values.
     * @param string $file Config file name that were loaded. this is without extension.
     * @param mixed $default Default value if config name is not found.
     * @return mixed Return config values.
     */
    public function get(string $configKey, string $file, $default = '')
    {
        $this->isLoaded($file);

        if (
            is_array($this->loadedFiles) && 
            array_key_exists($this->moduleSystemName, $this->loadedFiles) &&
            array_key_exists($file, $this->loadedFiles[$this->moduleSystemName])
        ) {
            if (
                $configKey != 'ALL' && 
                is_array($this->loadedFiles[$this->moduleSystemName][$file]) && 
                array_key_exists($configKey, $this->loadedFiles[$this->moduleSystemName][$file])
            ) {
                return $this->loadedFiles[$this->moduleSystemName][$file][$configKey];
            } elseif ($configKey == 'ALL') {
                return $this->loadedFiles[$this->moduleSystemName][$file];
            } else {
                // config key not found.
            }
        } else {
            // config file could not be loaded.
        }

        return $default;
    }// get


    /**
     * Get path to config file depend on environment and file that exists.
     * 
     * This is depend on `setModule()` method that telling it will get from main app or modules.
     * 
     * @param string $file The config file name without extension.
     * @return string Return full path to config file that different depend on environment.
     */
    public function getFile(string $file): string
    {
        if (APP_ENV == 'production') {
            $configEnvDir = 'production';
        } else {
            $configEnvDir = 'development';
        }

        if (empty($this->moduleSystemName) || $this->moduleSystemName === 'system\\core') {
            $configBaseDir = ROOT_PATH;
        } else {
            $configBaseDir = MODULE_PATH . DIRECTORY_SEPARATOR . $this->moduleSystemName;
        }

        if (is_file($configBaseDir.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.$configEnvDir.DIRECTORY_SEPARATOR.$file.'.php')) {
            // if found config file from /config/ENV/.
            return $configBaseDir.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.$configEnvDir.DIRECTORY_SEPARATOR.$file.'.php';
        } else {
            $envs = ['production', 'development', 'default'];
            foreach ($envs as $configEnvDir) {
                if (is_file($configBaseDir.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.$configEnvDir.DIRECTORY_SEPARATOR.$file.'.php')) {
                    // if found config file from /config/ENV (by order in $envs)/.
                    return $configBaseDir.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.$configEnvDir.DIRECTORY_SEPARATOR.$file.'.php';
                }
            }// endforeach;
            unset($envs);
        }

        unset($configBaseDir, $configEnvDir);
        return '';
    }// getFile


    /**
     * Get default language.
     * 
     * @param array $languages The languages list from config file. Leave this empty to get it from config file again.
     * @return string Return the language locale string (languageLocaleUrl).
     */
    public function getDefaultLanguage(array $languages = []): string
    {
        if (empty($languages)) {
            $languages = $this->get('languages', 'language', []);
        }

        $i = 0;
        $languageLocaleForNoDefault = '';
        foreach ($languages as $key => $language) {
            if ($i = 0) {
                // if first loop.
                $languageLocaleForNoDefault = $key;
            }

            if (
                isset($language['languageDefault']) && 
                $language['languageDefault'] === true
            ) {
                // if found default language.
                return $key;
            }

            $i++;
        }// endforeach;
        unset($i, $key, $language);

        if (!empty($languageLocaleForNoDefault)) {
            // if not found default language, use first language found in config.
            return $languageLocaleForNoDefault;
        }
        unset($languageLocaleForNoDefault);

        // if really found nothing.
        return 'en-US';
    }// getDefaultLanguage


    /**
     * Check if config file was loaded. If config file was not loaded, load the config file.
     * 
     * @param string $file The config file name without extension. case sensitive.
     * @return bool Return true on loaded, false for otherwise.
     */
    private function isLoaded(string $file): bool
    {
        if (
            !is_array($this->loadedFiles) || 
            (
                is_array($this->loadedFiles) && 
                (
                    !array_key_exists($this->moduleSystemName, $this->loadedFiles) ||
                    !array_key_exists($file, $this->loadedFiles[$this->moduleSystemName])
                )
            )
        ) {
            // config file did not load yet. load it.
            return $this->load($file);
        }

        return true;
    }// isLoaded


    /**
     * Load the config file into property array.
     * 
     * This is depend on `setModule()` method that telling it will get from main app or modules.
     * 
     * @param string $file The config file name without extension. Case sensitive.
     * @return bool Return true on loaded, false for otherwise.
     */
    public function load(string $file): bool
    {
        if (
            is_array($this->loadedFiles) && 
            array_key_exists($this->moduleSystemName, $this->loadedFiles) &&
            array_key_exists($file, $this->loadedFiles[$this->moduleSystemName])
        ) {
            return true;
        }

        $configFullPath = $this->getFile($file);
        if (!empty($configFullPath)) {
            $this->loadedFiles[$this->moduleSystemName][$file] = require $configFullPath;
            $this->traceLoadedFiles[] = $configFullPath;
            unset($configFullPath);
            return true;
        }
        return false;
    }// load


    /**
     * Set config name (key) and value to an existing config file.
     * 
     * This is depend on `setModule()` method that telling it will set to main app or modules.
     * 
     * Warning! this will not write to the config file.
     * 
     * @param string $file Config file name only, no extension. Case sensitive.
     * @param string $configKey Config name.
     * @param mixed $configValue Config value.
     */
    public function set(string $file, string $configKey, $configValue)
    {
        $this->isLoaded($file);

        if (
            is_array($this->loadedFiles) && 
            array_key_exists($this->moduleSystemName, $this->loadedFiles) &&
            array_key_exists($file, $this->loadedFiles[$this->moduleSystemName])
        ) {
            if (is_array($this->loadedFiles[$this->moduleSystemName][$file])) {
                $this->loadedFiles[$this->moduleSystemName][$file][$configKey] = $configValue;
            //} else {
                // config file that were loaded is not an array.
            }
        //} else {
            // config file could not be loaded.
        }
    }// set


    /**
     * Set the module that this class will be get config from it.
     * 
     * @param string $moduleSystemName The module system name (folder name). Set to empty string to get it from main app config.
     */
    public function setModule(string $moduleSystemName)
    {
        if ($moduleSystemName === '') {
            $moduleSystemName = 'system\\core';
        }

        $this->moduleSystemName = $moduleSystemName;
    }// setModule


}