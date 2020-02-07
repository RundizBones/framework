<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Tests\System\Libraries;


class UrlTest extends \Rdb\Tests\BaseTestCase
{


    /**
     * @var \Rdb\System\Config
     */
    protected $Config;


    /**
     * @var \Rdb\System\Libraries\Url 
     */
    protected $Url;


    /**
     * Run app with some of middleware that needed to test.
     * 
     * @see Rdb\Tests\BaseTestCase::runApp()
     */
    protected function runAppWithMiddleWare(string $method, string $url, array $cookies = [], array $additionalData = [])
    {
        $this->runApp($method, $url, $cookies, $additionalData);

        $I18n = new \Rdb\System\Middleware\I18n($this->Container);
        $I18n->init();
    }// runAppWithMiddleWare


    public function setup()
    {
        $this->runApp('get', '/my/controller/method?name1=value1&name2=value2&encoded1=hello%3Dworld%26goodbye%3Dworld');

        $this->Container = $this->RdbApp->getContainer();
        if ($this->Container->has('Config')) {
            $this->Config = $this->Container->get('Config');
            $this->Config->setModule('');
        } else {
            $this->Container['Config'] = function ($c) {
                return new \Rdb\System\Config();
            };
            $this->Config = $this->Container['Config'];
        }

        $this->Url = new \Rdb\System\Libraries\Url($this->Container);
    }// setup


    public function testGetAppBasedPath()
    {
        $this->Config->load('language');
        $this->Config->set('language', 'languageMethod', 'url');
        $this->Config->set('language', 'languageUrlDefaultVisible', false);
        // set languages data for test.
        $languages = [
            'en-US' => [
                'languageLocale' => ['en-US.UTF-8', 'en-US', 'en'],
                'languageName' => 'English',
                'languageDefault' => false,
            ],
            'th' => [
                'languageLocale' => ['th-TH.UTF-8', 'th-TH', 'th'],
                'languageName' => 'Thai',
                'languageDefault' => true,
            ],
        ];
        $this->Config->set('config', 'languages', $languages);
        $defaultLanguage = $this->Config->getDefaultLanguage();
        $this->assertSame('th', $defaultLanguage);

        // tests with default language HIDDEN. -----------------------------------
        // will not test with /myapp/[default-lang]/admin because it will be redirect and `exit()`.

        $_SERVER['SCRIPT_NAME'] = '/index.php';// required
        $this->runAppWithMiddleWare('get', '/en-US/admin');
        $this->assertSame('', $this->Url->getAppBasedPath());
        $this->assertSame('/en-US', $this->Url->getAppBasedPath(true));// get the raw URL (as seen in address bar).

        $_SERVER['SCRIPT_NAME'] = '/myapp/index.php';// required
        $this->runAppWithMiddleWare('get', '/myapp/en-US/admin');
        $this->assertSame('/myapp', $this->Url->getAppBasedPath());
        $this->assertSame('/myapp/en-US', $this->Url->getAppBasedPath(true));

        $this->runAppWithMiddleWare('get', '/myapp/en-US/admin?s=searchval&order=desc');
        $this->assertSame('/myapp', $this->Url->getAppBasedPath());
        $this->assertSame('/myapp/en-US', $this->Url->getAppBasedPath(true));

        // will not test with /myapp/index.php/[default-lang]/admin because it will be redirect and `exit()`.

        $_SERVER['SCRIPT_NAME'] = '/myapp/index.php';// required
        $this->runAppWithMiddleWare('get', '/myapp/index.php/en-US/admin');
        $this->assertSame('/myapp/index.php', $this->Url->getAppBasedPath());
        $this->assertSame('/myapp/index.php/en-US', $this->Url->getAppBasedPath(true));// get the raw URL (as seen in address bar).

        $this->Config->set('language', 'languageUrlDefaultVisible', true);
        // tests with default language VISIBLE. -----------------------------------
        // will not test with /myapp/admin or myapp/index.php/admin without language because it will be redirect and `exit()`.

        $_SERVER['SCRIPT_NAME'] = '/index.php';// required
        $this->runAppWithMiddleWare('get', '/' . $defaultLanguage . '/admin');
        $this->assertSame('', $this->Url->getAppBasedPath());
        $this->assertSame('/th', $this->Url->getAppBasedPath(true));// get the raw URL (as seen in address bar).

        $this->runAppWithMiddleWare('get', '/en-US/admin');
        $this->assertSame('', $this->Url->getAppBasedPath());
        $this->assertSame('/en-US', $this->Url->getAppBasedPath(true));// get the raw URL (as seen in address bar).

        $_SERVER['SCRIPT_NAME'] = '/myapp/index.php';// required
        $this->runAppWithMiddleWare('get', '/myapp/' . $defaultLanguage . '/admin');// this will not redirect.
        $this->assertSame('/myapp', $this->Url->getAppBasedPath());
        $this->assertSame('/myapp/th', $this->Url->getAppBasedPath(true));// get the raw URL (as seen in address bar).

        $this->runAppWithMiddleWare('get', '/myapp/' . $defaultLanguage . '/admin?s=searchval&order=desc');
        $this->assertSame('/myapp', $this->Url->getAppBasedPath());
        $this->assertSame('/myapp/th', $this->Url->getAppBasedPath(true));// get the raw URL (as seen in address bar).
        
        $this->runAppWithMiddleWare('get', '/myapp/en-US/admin');
        $this->assertSame('/myapp', $this->Url->getAppBasedPath());
        $this->assertSame('/myapp/en-US', $this->Url->getAppBasedPath(true));// get the raw URL (as seen in address bar).
        
        $this->runAppWithMiddleWare('get', '/myapp/en-US/admin?s=searchval&order=desc');
        $this->assertSame('/myapp', $this->Url->getAppBasedPath());
        $this->assertSame('/myapp/en-US', $this->Url->getAppBasedPath(true));// get the raw URL (as seen in address bar).

        $_SERVER['SCRIPT_NAME'] = '/myapp/index.php';// required
        $this->runAppWithMiddleWare('get', '/myapp/index.php/' . $defaultLanguage . '/admin');
        $this->assertSame('/myapp/index.php', $this->Url->getAppBasedPath());
        $this->assertSame('/myapp/index.php/th', $this->Url->getAppBasedPath(true));// get the raw URL (as seen in address bar).

        $this->runAppWithMiddleWare('get', '/myapp/index.php/' . $defaultLanguage . '/admin?s=searchval&order=desc');
        $this->assertSame('/myapp/index.php', $this->Url->getAppBasedPath());
        $this->assertSame('/myapp/index.php/th', $this->Url->getAppBasedPath(true));// get the raw URL (as seen in address bar).
        
        $this->runAppWithMiddleWare('get', '/myapp/index.php/en-US/admin');
        $this->assertSame('/myapp/index.php', $this->Url->getAppBasedPath());
        $this->assertSame('/myapp/index.php/en-US', $this->Url->getAppBasedPath(true));// get the raw URL (as seen in address bar).
        
        $this->runAppWithMiddleWare('get', '/myapp/index.php/en-US/admin?s=searchval&order=desc');
        $this->assertSame('/myapp/index.php', $this->Url->getAppBasedPath());
        $this->assertSame('/myapp/index.php/en-US', $this->Url->getAppBasedPath(true));// get the raw URL (as seen in address bar).
    }// testGetAppBasedPath


    public function testGetCurrentUrl()
    {
        $this->Config->load('language');
        $this->Config->set('language', 'languageMethod', 'url');
        $this->Config->set('language', 'languageUrlDefaultVisible', false);
        // set languages data for test.
        $languages = [
            'en-US' => [
                'languageLocale' => ['en-US.UTF-8', 'en-US', 'en'],
                'languageName' => 'English',
                'languageDefault' => false,
            ],
            'th' => [
                'languageLocale' => ['th-TH.UTF-8', 'th-TH', 'th'],
                'languageName' => 'Thai',
                'languageDefault' => true,
            ],
        ];
        $this->Config->set('config', 'languages', $languages);
        $defaultLanguage = $this->Config->getDefaultLanguage();
        $this->assertSame('th', $defaultLanguage);

        // tests with default language HIDDEN. -----------------------------------
        // will not test with /myapp/[default-lang] because it will be redirect and `exit()`.

        $_SERVER['SCRIPT_NAME'] = '/index.php';// required
        $this->runAppWithMiddleWare('get', '/en-US/admin');
        $this->assertSame('/admin', $this->Url->getCurrentUrl());
        $this->assertSame('/en-US/admin', $this->Url->getCurrentUrl(true));// get the raw URL (as seen in address bar).

        $this->runAppWithMiddleWare('get', '/en-US');
        $this->assertSame('', $this->Url->getCurrentUrl());
        $this->assertSame('/en-US', $this->Url->getCurrentUrl(true));// get the raw URL (as seen in address bar).

        $_SERVER['SCRIPT_NAME'] = '/myapp/index.php';// required
        $this->runAppWithMiddleWare('get', '/myapp/en-US/admin');
        $this->assertSame('/myapp/admin', $this->Url->getCurrentUrl());
        $this->assertSame('/myapp/en-US/admin', $this->Url->getCurrentUrl(true));// get the raw URL (as seen in address bar).

        $this->runAppWithMiddleWare('get', '/myapp/en-US/admin?s=searchval&order=desc');
        $this->assertSame('/myapp/admin', $this->Url->getCurrentUrl());
        $this->assertSame('/myapp/en-US/admin', $this->Url->getCurrentUrl(true));// get the raw URL (as seen in address bar).

        // will not test with /myapp/index.php/[default-lang]/admin because it will be redirect and `exit()`.

        $_SERVER['SCRIPT_NAME'] = '/myapp/index.php';// required
        $this->runAppWithMiddleWare('get', '/myapp/index.php/en-US/admin');
        $this->assertSame('/myapp/index.php/admin', $this->Url->getCurrentUrl());
        $this->assertSame('/myapp/index.php/en-US/admin', $this->Url->getCurrentUrl(true));// get the raw URL (as seen in address bar).

        $this->Config->set('language', 'languageUrlDefaultVisible', true);
        // tests with default language VISIBLE. -----------------------------------
        // will not test with /myapp/admin or myapp/index.php/admin without language because it will be redirect and `exit()`.

        $_SERVER['SCRIPT_NAME'] = '/index.php';// required
        $this->runAppWithMiddleWare('get', '/' . $defaultLanguage . '/admin');
        $this->assertSame('/admin', $this->Url->getCurrentUrl());
        $this->assertSame('/th/admin', $this->Url->getCurrentUrl(true));// get the raw URL (as seen in address bar).

        $this->runAppWithMiddleWare('get', '/' . $defaultLanguage);
        $this->assertSame('', $this->Url->getCurrentUrl());
        $this->assertSame('/th', $this->Url->getCurrentUrl(true));// get the raw URL (as seen in address bar).

        $this->runAppWithMiddleWare('get', '/en-US/admin');
        $this->assertSame('/admin', $this->Url->getCurrentUrl());
        $this->assertSame('/en-US/admin', $this->Url->getCurrentUrl(true));// get the raw URL (as seen in address bar).

        $this->runAppWithMiddleWare('get', '/en-US');
        $this->assertSame('', $this->Url->getCurrentUrl());
        $this->assertSame('/en-US', $this->Url->getCurrentUrl(true));// get the raw URL (as seen in address bar).

        $_SERVER['SCRIPT_NAME'] = '/myapp/index.php';// required
        $this->runAppWithMiddleWare('get', '/myapp/' . $defaultLanguage . '/admin');// this will not redirect.
        $this->assertSame('/myapp/admin', $this->Url->getCurrentUrl());
        $this->assertSame('/myapp/th/admin', $this->Url->getCurrentUrl(true));// get the raw URL (as seen in address bar).

        $this->runAppWithMiddleWare('get', '/myapp/' . $defaultLanguage . '/admin?s=searchval&order=desc');
        $this->assertSame('/myapp/admin', $this->Url->getCurrentUrl());
        $this->assertSame('/myapp/th/admin', $this->Url->getCurrentUrl(true));// get the raw URL (as seen in address bar).
        
        $this->runAppWithMiddleWare('get', '/myapp/en-US/admin');
        $this->assertSame('/myapp/admin', $this->Url->getCurrentUrl());
        $this->assertSame('/myapp/en-US/admin', $this->Url->getCurrentUrl(true));// get the raw URL (as seen in address bar).
        
        $this->runAppWithMiddleWare('get', '/myapp/en-US/admin?s=searchval&order=desc');
        $this->assertSame('/myapp/admin', $this->Url->getCurrentUrl());
        $this->assertSame('/myapp/en-US/admin', $this->Url->getCurrentUrl(true));// get the raw URL (as seen in address bar).

        $_SERVER['SCRIPT_NAME'] = '/myapp/index.php';// required
        $this->runAppWithMiddleWare('get', '/myapp/index.php/' . $defaultLanguage . '/admin');
        $this->assertSame('/myapp/index.php/admin', $this->Url->getCurrentUrl());
        $this->assertSame('/myapp/index.php/th/admin', $this->Url->getCurrentUrl(true));// get the raw URL (as seen in address bar).

        $this->runAppWithMiddleWare('get', '/myapp/index.php/' . $defaultLanguage . '/admin?s=searchval&order=desc');
        $this->assertSame('/myapp/index.php/admin', $this->Url->getCurrentUrl());
        $this->assertSame('/myapp/index.php/th/admin', $this->Url->getCurrentUrl(true));// get the raw URL (as seen in address bar).
        
        $this->runAppWithMiddleWare('get', '/myapp/index.php/en-US/admin');
        $this->assertSame('/myapp/index.php/admin', $this->Url->getCurrentUrl());
        $this->assertSame('/myapp/index.php/en-US/admin', $this->Url->getCurrentUrl(true));// get the raw URL (as seen in address bar).
        
        $this->runAppWithMiddleWare('get', '/myapp/index.php/en-US/admin?s=searchval&order=desc');
        $this->assertSame('/myapp/index.php/admin', $this->Url->getCurrentUrl());
        $this->assertSame('/myapp/index.php/en-US/admin', $this->Url->getCurrentUrl(true));// get the raw URL (as seen in address bar).
    }// testGetCurrentUrl


    public function testGetDomainProtocol()
    {
        $this->assertEquals('http://', $this->Url->getDomainProtocol());

        $this->runApp('get', 'https://mydomain.com/myapp/hello/%E0%B8%AA%E0%B8%A7%E0%B8%B1%E0%B8%AA%E0%B8%94%E0%B8%B5');
        $this->assertEquals('https://mydomain.com', $this->Url->getDomainProtocol());
    }// testGetDomainProtocol


    public function testGetPath()
    {
        $this->assertEquals('/my/controller/method', $this->Url->getPath());

        $this->runApp('get', '/hello/%E0%B8%AA%E0%B8%A7%E0%B8%B1%E0%B8%AA%E0%B8%94%E0%B8%B5');
        $this->assertEquals('/hello/%E0%B8%AA%E0%B8%A7%E0%B8%B1%E0%B8%AA%E0%B8%94%E0%B8%B5', $this->Url->getPath());

        $this->runApp('get', '/');
        $this->assertEquals('', $this->Url->getPath());

        $_SERVER['SCRIPT_NAME'] = '/index.php';// required
        $this->runAppWithMiddleWare('get', '/en-US');
        $this->assertSame('', $this->Url->getPath());// get the raw URL (as seen in address bar).

        $_SERVER['SCRIPT_NAME'] = '/myapp/index.php';// required
        $this->runAppWithMiddleWare('get', '/myapp/en-US');
        $this->assertSame('', $this->Url->getPath());// get the raw URL (as seen in address bar).

        $_SERVER['SCRIPT_NAME'] = '/myapp/index.php';// required
        $this->runAppWithMiddleWare('get', '/myapp/en-US/mycontroller/method');
        $this->assertSame('/mycontroller/method', $this->Url->getPath());// get the raw URL (as seen in address bar).

        // will not test with /myapp/[default-lang]/mycontroller/method because it will be redirect and `exit()`.
    }// testGetPath


    public function testGetPublicModuleUrl()
    {
        $Modules = new \Rdb\System\Modules($this->Container);
        $this->Container['Modules'] = function ($c) use ($Modules) {
            return $Modules;
        };
        unset($Modules);

        $_SERVER['SCRIPT_NAME'] = '/index.php';// required
        $this->runAppWithMiddleWare('get', '/en-US');
        $this->assertSame('/Modules/MyContact', $this->Url->getPublicModuleUrl(MODULE_PATH . '/MyContact/Files.php'));
        $this->assertSame('/Modules/SystemCore', $this->Url->getPublicModuleUrl(''));

        // will not test with /[default-lang] because it will be redirect and `exit()`.

        $_SERVER['SCRIPT_NAME'] = '/myapp/index.php';// required
        $this->runAppWithMiddleWare('get', '/myapp/en-US');
        $this->assertSame('/myapp/Modules/MyContact', $this->Url->getPublicModuleUrl(MODULE_PATH . '/MyContact/Files.php'));
        $this->assertSame('/myapp/Modules/SystemCore', $this->Url->getPublicModuleUrl(''));

        // will not test with /myapp/[default-lang] because it will be redirect and `exit()`.
        // will not test with /myapp/index.php/[default-lang] because it will be redirect and `exit()`.

        $this->runAppWithMiddleWare('get', '/myapp/en-US/admin');
        $this->assertSame('/myapp/Modules/MyContact', $this->Url->getPublicModuleUrl(MODULE_PATH . '/MyContact/Files.php'));
        $this->assertSame('/myapp/Modules/SystemCore', $this->Url->getPublicModuleUrl(''));

        $this->runAppWithMiddleWare('get', '/myapp/index.php/en-US/admin');
        $this->assertSame('/myapp/Modules/MyContact', $this->Url->getPublicModuleUrl(MODULE_PATH . '/MyContact/Files.php'));
        $this->assertSame('/myapp/Modules/SystemCore', $this->Url->getPublicModuleUrl(''));
    }// testGetPublicModuleUrl


    public function testGetQuerystring()
    {
        $this->assertEquals('?name1=value1&name2=value2&encoded1=hello%3Dworld%26goodbye%3Dworld', $this->Url->getQuerystring());

        $this->runApp('get', '/');
        $this->assertEquals('', $this->Url->getQuerystring());

        $this->runApp('get', '/?name1=value1&url=http://rundiz.com/test/urlencode?search=searchvalue&sort=desc&order=name');
        $this->assertEquals('?name1=value1&url=http%3A%2F%2Frundiz.com%2Ftest%2Furlencode%3Fsearch%3Dsearchvalue&sort=desc&order=name', $this->Url->getQuerystring());

        $this->runApp('get', '/?name1=value1&space=a and b');
        $this->assertEquals('?name1=value1&space=a%20and%20b', $this->Url->getQuerystring());

        $this->runApp('get', '/?name1=value1&url=http%3A%2F%2Frundiz.com%2Ftest%2Furlencode%3Fsearch%3Dsearchvalue%26sort%3Ddesc%26order%3Dname&name2=value2');
        $this->assertEquals('?name1=value1&url=http%3A%2F%2Frundiz.com%2Ftest%2Furlencode%3Fsearch%3Dsearchvalue%26sort%3Ddesc%26order%3Dname&name2=value2', $this->Url->getQuerystring());
    }// testGetQuerystring


    public function testRemoveQuerystring()
    {
        $this->assertEquals('/my/controller/method', $this->Url->removeQuerystring('/my/controller/method?name1=value1&name2=value2&encoded1=hello%3Dworld%26goodbye%3Dworld'));
    }// testRemoveQuerystring


}
