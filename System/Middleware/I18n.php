<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace System\Middleware;


/**
 * I18n or internationalisation for the framework.
 * 
 * This class detect language base on configuration that it is on the URL or cookie.<br>
 * After run this class you can get current language by accessing `$_SERVER['RUNDIZBONES_LANGUAGE']`.
 *
 * @since 0.1
 */
class I18n
{


    /**
     * @var \System\Config
     */
    protected $Config;


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

        if ($Container->has('Config')) {
            $this->Config = $Container->get('Config');
            $this->Config->setModule('');
        } else {
            $this->Config = new \System\Config();
        }
    }// __construct


    /**
     * Get locale to set in PHP.
     * 
     * @param string $languageId The language ID to match in config.
     * @param array $allLanguages The all languages in config file.
     * @return mixed Return locale in string or array.
     */
    protected function getLocale(string $languageId, array $allLanguages = [])
    {
        if (empty($allLanguages)) {
            $allLanguages = $this->get('languages', 'language', []);
        }

        $output = '';

        foreach ($allLanguages as $key => $language) {
            if ($languageId === $key) {
                if (isset($language['languageLocale'])) {
                    $output = $language['languageLocale'];
                    break;
                }
            }
        }// endforeach;
        unset($key, $language);

        if (empty($output)) {
            $defaultId = $this->Config->getDefaultLanguage($allLanguages);

            foreach ($allLanguages as $key => $language) {
                if ($defaultId === $key) {
                    if (isset($language['languageLocale'])) {
                        $output = $language['languageLocale'];
                        break;
                    }
                }
            }// endforeach;
            unset($defaultId, $key, $language);
        }

        if (empty($output)) {
            $output = 'en-US';
        }

        return $output;
    }// getLocale


    /**
     * Initialize the language/locale.
     * 
     * After this middleware worked, you can access current language by different ways.<br>
     * If config is using cookie, you can access it via `$_COOKIES['rundizbones_language' . $this->Container['Config']->get('suffix', 'cookie')]`
     * If config is using anything, you can access it via `$_SERVER['RUNDIZBONES_LANGUAGE']`
     * 
     * @param string|null $response
     * @return string|null
     */
    public function init($response = '')
    {
        $languageID = null;
        $allLanguages = $this->Config->get('languages', 'language', []);

        if ($this->Config->get('languageMethod', 'language', 'url') === 'cookie') {
            // if config set to detect language using cookie.
            $languageCookieName = 'rundizbones_language' . $this->Config->get('suffix', 'cookie');
            if (isset($_COOKIE[$languageCookieName])) {
                // if language cookie was set.
                $languageID = $_COOKIE[$languageCookieName];
            } else {
                // if language cookie was NOT set.
                // get default language.
                $languageID = $this->Config->getDefaultLanguage($allLanguages);
                $cookieExpires = 90;// unit in days.
                setcookie($languageCookieName, $languageID, (time() + (60*60*24*$cookieExpires)), '/');
                unset($cookieExpires, $languageCookieName, $languageID);
                // redirect to let app can be able to get cookie.
                $this->redirect();
            }
        } else {
            // if config set to detect language using URL
            $Url = new \System\Libraries\Url($this->Container);
            $urlPath = trim($Url->getPath(), '/');
            $urlSegments = explode('/', $urlPath);
            unset($urlPath);

            $defaultLanguage = $this->Config->getDefaultLanguage($allLanguages);

            // detect language ID from the URL.
            if (
                is_array($urlSegments) &&
                isset($urlSegments[0]) &&
                !empty($urlSegments[0]) &&
                is_array($allLanguages) &&
                array_key_exists($urlSegments[0], $allLanguages)
            ) {
                // if detected language ID in the URL. Example: http://localhost/myapp/en-US the `en-US` is the language ID.
                $languageID = $urlSegments[0];
            } else {
                // if cannot detected language ID in the URL.
                $languageID = $defaultLanguage;
                $cannotDetectLanguageUrl = true;
            }
            // end detect language ID from the URL.

            // redirection to correct the URL.
            if ($this->Config->get('languageUrlDefaultVisible', 'language', false) === true) {
                // if config was set to show default language in the URL.
                if (isset($cannotDetectLanguageUrl) && $cannotDetectLanguageUrl === true) {
                    // if cannot detect language from the URL, this means that using default language from config.
                    // redirect to show the language URL.
                    array_unshift($urlSegments, $languageID);
                    $newUrl = rtrim($Url->getAppBasedPath() . '/' . implode('/', $urlSegments), '/');
                    $newUrl = (empty($newUrl) ? '/' : $newUrl) . $Url->getQuerystring();
                    unset($urlSegments);
                    $this->redirect($newUrl, 301);
                }
            } else {
                // if config was set to NOT show default language in the URL.
                if ($languageID === $defaultLanguage && !isset($cannotDetectLanguageUrl)) {
                    // if detected language ID matched default language and [cannot detect language in URL] mark was set.
                    // redirect to remove the language URL.
                    $urlSegments = array_slice($urlSegments, 1);
                    $newUrl = rtrim($Url->getAppBasedPath() . '/' . implode('/', $urlSegments), '/');
                    $newUrl = (empty($newUrl) ? '/' : $newUrl) . $Url->getQuerystring();
                    $this->redirect($newUrl, 301);
                }
            }
            // end redirection to correct the URL.

            // set new REQUEST_URI to remove language URL.
            $_SERVER['RUNDIZBONES_ORIGINAL_REQUEST_URI'] = ($_SERVER['REQUEST_URI'] ?? '');
            if (!isset($cannotDetectLanguageUrl)) {
                // if [cannot detect language in URL] mark was set.
                // remove the language URL segment and set it back to REQUEST_URI.
                $urlSegments = array_slice($urlSegments, 1);
                $newUrl = rtrim($Url->getAppBasedPath() . '/' . implode('/', $urlSegments), '/');
                $newUrl = (empty($newUrl) ? '/' : $newUrl) . $Url->getQuerystring();
                $_SERVER['REQUEST_URI'] = $newUrl;
                unset($newUrl);
            }
            // end set new REQUEST_URI to remove language URL.
            
            unset($cannotDetectLanguageUrl, $defaultLanguage, $Url, $urlSegments);
        }// endif; languageMethod

        // set the language ID to server variable for easy access.
        $_SERVER['RUNDIZBONES_LANGUAGE'] = $languageID;

        // set the locale.
        $locale = $this->getLocale($languageID, $allLanguages);
        // set the language locale to server variable for easy access. the locale can be string, array, see config/[env]/language.php.
        // basically the server variable below is no need to use anymore because it will be set to LC_XXX here.
        $_SERVER['RUNDIZBONES_LANGUAGE_LOCALE'] = json_encode($locale);
        putenv('LC_ALL=' . $languageID);
        setlocale(LC_ALL, $locale);

        unset($allLanguages, $languageID, $locale);
        return $response;
    }// init


    /**
     * Redirect and then exit.
     * 
     * It must use `exit()` because if it returns then other middlewares will still working which is waste of resource.
     * 
     * @link https://stackoverflow.com/a/44658395/128761 Redirect 302, 303, 307 difference.
     * @param string $url The URL to be redirect to.
     * @param int $status HTTP header status.
     */
    protected function redirect(string $url = '', int $status = 307)
    {
        header('Expires: Fri, 01 Jan 1971 00:00:00 GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        http_response_code($status);

        if (empty($url)) {
            $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://';
            $url .= ($_SERVER['HTTP_HOST'] ?? '');
            $url .= ($_SERVER['REQUEST_URI'] ?? '');
        }
        header('Location: ' . $url, true);

        exit();
    }// redirect


}
