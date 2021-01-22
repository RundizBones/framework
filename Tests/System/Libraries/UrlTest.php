<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Tests\System\Libraries;

use Rdb\System\Middleware\I18n;

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


    public function setup(): void
    {
        $this->runApp('get', '/my/controller/method?name1=value1&name2=value2&encoded1=hello%3Dworld%26goodbye%3Dworld');

        $I18n = new I18n($this->RdbApp->getContainer());
        $I18n->init();// set locale before test because parse_url will not work with unicode.

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


    public function testBuildUrl()
    {
        $url = 'http://my@email.tld:password@localhost.localhost:80/lang/ภาษาไทย/question/คำตอบ?question=คำตอบ&one=หนึ่ง&arr[]=1&arr[]=2&ques?tion=answer#anchor';
        $parsedUrl = parse_url($url);
        $this->assertSame($url, $this->Url->buildUrl($parsedUrl));
    }


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


    public function testGetCurrentUrlRelatedFromPublic()
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
        $this->assertSame('/admin', $this->Url->getCurrentUrlRelatedFromPublic());

        $this->runAppWithMiddleWare('get', '/en-US');
        $this->assertSame('', $this->Url->getCurrentUrlRelatedFromPublic());

        $_SERVER['SCRIPT_NAME'] = '/myapp/index.php';// required
        $this->runAppWithMiddleWare('get', '/myapp/en-US/admin');
        $this->assertSame('/admin', $this->Url->getCurrentUrlRelatedFromPublic());

        $this->runAppWithMiddleWare('get', '/myapp/en-US/admin?s=searchval&order=desc');
        $this->assertSame('/admin', $this->Url->getCurrentUrlRelatedFromPublic());

        // will not test with /myapp/index.php/[default-lang]/admin because it will be redirect and `exit()`.

        $_SERVER['SCRIPT_NAME'] = '/myapp/index.php';// required
        $this->runAppWithMiddleWare('get', '/myapp/index.php/en-US/admin');
        $this->assertSame('/admin', $this->Url->getCurrentUrlRelatedFromPublic());

        $this->Config->set('language', 'languageUrlDefaultVisible', true);
        // tests with default language VISIBLE. -----------------------------------
        // will not test with /myapp/admin or myapp/index.php/admin without language because it will be redirect and `exit()`.

        $_SERVER['SCRIPT_NAME'] = '/index.php';// required
        $this->runAppWithMiddleWare('get', '/' . $defaultLanguage . '/admin');
        $this->assertSame('/admin', $this->Url->getCurrentUrlRelatedFromPublic());

        $this->runAppWithMiddleWare('get', '/' . $defaultLanguage);
        $this->assertSame('', $this->Url->getCurrentUrlRelatedFromPublic());

        $this->runAppWithMiddleWare('get', '/en-US/admin');
        $this->assertSame('/admin', $this->Url->getCurrentUrlRelatedFromPublic());

        $this->runAppWithMiddleWare('get', '/en-US');
        $this->assertSame('', $this->Url->getCurrentUrlRelatedFromPublic());

        $_SERVER['SCRIPT_NAME'] = '/myapp/index.php';// required
        $this->runAppWithMiddleWare('get', '/myapp/' . $defaultLanguage . '/admin');// this will not redirect.
        $this->assertSame('/admin', $this->Url->getCurrentUrlRelatedFromPublic());

        $this->runAppWithMiddleWare('get', '/myapp/' . $defaultLanguage . '/admin?s=searchval&order=desc');
        $this->assertSame('/admin', $this->Url->getCurrentUrlRelatedFromPublic());
        
        $this->runAppWithMiddleWare('get', '/myapp/en-US/admin');
        $this->assertSame('/admin', $this->Url->getCurrentUrlRelatedFromPublic());
        
        $this->runAppWithMiddleWare('get', '/myapp/en-US/admin?s=searchval&order=desc');
        $this->assertSame('/admin', $this->Url->getCurrentUrlRelatedFromPublic());

        $_SERVER['SCRIPT_NAME'] = '/myapp/index.php';// required
        $this->runAppWithMiddleWare('get', '/myapp/index.php/' . $defaultLanguage . '/admin');
        $this->assertSame('/admin', $this->Url->getCurrentUrlRelatedFromPublic());

        $this->runAppWithMiddleWare('get', '/myapp/index.php/' . $defaultLanguage . '/admin?s=searchval&order=desc');
        $this->assertSame('/admin', $this->Url->getCurrentUrlRelatedFromPublic());
        
        $this->runAppWithMiddleWare('get', '/myapp/index.php/en-US/admin');
        $this->assertSame('/admin', $this->Url->getCurrentUrlRelatedFromPublic());
        
        $this->runAppWithMiddleWare('get', '/myapp/index.php/en-US/admin?s=searchval&order=desc');
        $this->assertSame('/admin', $this->Url->getCurrentUrlRelatedFromPublic());
    }// testGetCurrentUrlRelatedFromPublic


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


    public function testGetPublicUrl()
    {
        $_SERVER['SCRIPT_NAME'] = '/index.php';// required
        $this->runAppWithMiddleWare('get', '/en-US');
        $this->assertSame('', $this->Url->getPublicUrl());
        
        $_SERVER['SCRIPT_NAME'] = '/myapp/index.php';// required
        $this->runAppWithMiddleWare('get', '/myapp/en-US');
        $this->assertSame('/myapp', $this->Url->getPublicUrl());

        // will not test with /myapp/[default-lang] because it will be redirect and `exit()`.
        // will not test with /myapp/index.php/[default-lang] because it will be redirect and `exit()`.

        $this->runAppWithMiddleWare('get', '/myapp/en-US/admin');
        $this->assertSame('/myapp', $this->Url->getPublicUrl());
    }// testGetPublicUrl


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


    public function testGetSegment()
    {
        $_SERVER['SCRIPT_NAME'] = '/index.php';// required
        $this->runAppWithMiddleWare('get', '/en-US');
        $this->assertSame('', $this->Url->getSegment(1));

        $_SERVER['SCRIPT_NAME'] = '/index.php';// required
        $this->runAppWithMiddleWare('get', '/en-US/categories/food/steak?some=query&string=not-affect');
        $this->assertSame('', $this->Url->getSegment(0));
        $this->assertSame('categories', $this->Url->getSegment(1));
        $this->assertSame('food', $this->Url->getSegment(2));
        $this->assertSame('steak', $this->Url->getSegment(3));
    }// testGetSegment


    public function testGetSegments()
    {
        $_SERVER['SCRIPT_NAME'] = '/index.php';// required
        $this->runAppWithMiddleWare('get', '/en-US');
        $segments = $this->Url->getSegments();
        $this->assertTrue(is_array($segments));
        $this->assertSame([], $segments);

        $_SERVER['SCRIPT_NAME'] = '/index.php';// required
        $this->runAppWithMiddleWare('get', '/en-US/categories/food/steak');
        $segments = $this->Url->getSegments();
        $this->assertSame(['categories', 'food', 'steak'], $segments);
    }// testGetSegment
    

    public function testRawUrlEncodeAllParts()
    {
        $this->assertSame(
            'http://my%40email.tld:%E0%B8%9C%E0%B9%88%E0%B8%B2%E0%B8%99word@localhost.localhost:80'
            . '/lang/%E0%B8%A0%E0%B8%B2%E0%B8%A9%E0%B8%B2%E0%B9%84%E0%B8%97%E0%B8%A2/question/%E0%B8%84%E0%B8%B3%E0%B8%95%E0%B8%AD%E0%B8%9A'
            . '?question=%E0%B8%84%E0%B8%B3%E0%B8%95%E0%B8%AD%E0%B8%9A&one=%E0%B8%AB%E0%B8%99%E0%B8%B6%E0%B9%88%E0%B8%87&arr%5B0%5D=1&arr%5B1%5D=2&ques%3Ftion=answer'
            . '#anchor%E0%B8%A0%E0%B8%B2%E0%B8%A9%E0%B8%B2%E0%B9%84%E0%B8%97%E0%B8%A2', 
            $this->Url->rawUrlEncodeAllParts(
                'http://my@email.tld:ผ่านword@localhost.localhost:80'
                . '/lang/ภาษาไทย/question/คำตอบ'
                . '?question=คำตอบ&one=หนึ่ง&arr[]=1&arr[]=2&ques?tion=answer'
                . '#anchorภาษาไทย'
            )
        );
        $this->assertSame(
            'http://my%40email.tld:%E0%B8%9C%E0%B9%88%E0%B8%B2%E0%B8%99word@localhost.localhost:80'
            . '/lang/%E0%B8%A0%E0%B8%B2%E0%B8%A9%E0%B8%B2%E0%B9%84%E0%B8%97%E0%B8%A2/question/%E0%B8%84%E0%B8%B3%E0%B8%95%E0%B8%AD%E0%B8%9A'
            . '?question=%E0%B8%84%E0%B8%B3%E0%B8%95%E0%B8%AD%E0%B8%9A&one=%E0%B8%AB%E0%B8%99%E0%B8%B6%E0%B9%88%E0%B8%87&arr%5B0%5D=1&arr%5B1%5D=2&ques%3Ftion=answer',
            $this->Url->rawUrlEncodeAllParts(
                'http://my@email.tld:ผ่านword@localhost.localhost:80'
                . '/lang/ภาษาไทย/question/คำตอบ'
                . '?question=คำตอบ&one=หนึ่ง&arr[]=1&arr[]=2&ques?tion=answer'
                . '#'
            )
        );
        $this->assertSame(
            'http://my%40email.tld:%E0%B8%9C%E0%B9%88%E0%B8%B2%E0%B8%99word@localhost.localhost:80'
            . '/lang/%E0%B8%A0%E0%B8%B2%E0%B8%A9%E0%B8%B2%E0%B9%84%E0%B8%97%E0%B8%A2/question/%E0%B8%84%E0%B8%B3%E0%B8%95%E0%B8%AD%E0%B8%9A',
            $this->Url->rawUrlEncodeAllParts(
                'http://my@email.tld:ผ่านword@localhost.localhost:80'
                . '/lang/ภาษาไทย/question/คำตอบ'
                . '?'
                . '#'
            )
        );
    }// testRawUrlEncodeAllParts


    public function testRawUrlEncodeFragment()
    {
        $this->assertSame('http://my@email.tld:password@localhost.localhost:80/lang/ภาษาไทย/question/คำตอบ?question=คำตอบ&one=หนึ่ง&arr[]=1&arr[]=2&ques?tion=answer#anchor%E0%B8%A0%E0%B8%B2%E0%B8%A9%E0%B8%B2%E0%B9%84%E0%B8%97%E0%B8%A2', $this->Url->rawUrlEncodeFragment('http://my@email.tld:password@localhost.localhost:80/lang/ภาษาไทย/question/คำตอบ?question=คำตอบ&one=หนึ่ง&arr[]=1&arr[]=2&ques?tion=answer#anchorภาษาไทย'));
        $this->assertSame('http://my@email.tld:password@localhost.localhost:80/lang/ภาษาไทย/question/คำตอบ#anchor%E0%B8%A0%E0%B8%B2%E0%B8%A9%E0%B8%B2%E0%B9%84%E0%B8%97%E0%B8%A2', $this->Url->rawUrlEncodeFragment('http://my@email.tld:password@localhost.localhost:80/lang/ภาษาไทย/question/คำตอบ?#anchorภาษาไทย'));
        $this->assertSame('http://my@email.tld:password@localhost.localhost:80/lang/ภาษาไทย/question/คำตอบ', $this->Url->rawUrlEncodeFragment('http://my@email.tld:password@localhost.localhost:80/lang/ภาษาไทย/question/คำตอบ?#'));
    }// testRawUrlEncodeFragment


    public function testRawUrlEncodeQuerystring()
    {
        $this->assertSame('http://localhost.localhost/lang/ภาษาไทย/question/คำตอบ?question=%E0%B8%84%E0%B8%B3%E0%B8%95%E0%B8%AD%E0%B8%9A', $this->Url->rawUrlEncodeQuerystring('http://localhost.localhost/lang/ภาษาไทย/question/คำตอบ?question=คำตอบ'));
        $this->assertSame('http://localhost.localhost/lang/ภาษาไทย/question/คำตอบ?question=%E0%B8%84%E0%B8%B3%E0%B8%95%E0%B8%AD%E0%B8%9A', $this->Url->rawUrlEncodeQuerystring('http://localhost.localhost/lang/ภาษาไทย/question/คำตอบ?question=คำตอบ#'));
        $this->assertSame('http://localhost.localhost/lang/ภาษาไทย/question/คำตอบ', $this->Url->rawUrlEncodeQuerystring('http://localhost.localhost/lang/ภาษาไทย/question/คำตอบ?#'));
        $this->assertSame('http://localhost.localhost/lang/ภาษาไทย/question/คำตอบ?question=%E0%B8%84%E0%B8%B3%E0%B8%95%E0%B8%AD%E0%B8%9A&one=%E0%B8%AB%E0%B8%99%E0%B8%B6%E0%B9%88%E0%B8%87&arr%5B0%5D=1&arr%5B1%5D=2#anchorไทย', $this->Url->rawUrlEncodeQuerystring('http://localhost.localhost/lang/ภาษาไทย/question/คำตอบ?question=คำตอบ&one=หนึ่ง&arr[]=1&arr[]=2#anchorไทย'));
    }// testRawUrlEncodeQuerystring


    public function testRawUrlEncodeSegments()
    {
        $this->assertSame('hello/%E0%B8%AA%E0%B8%A7%E0%B8%B1%E0%B8%AA%E0%B8%94%E0%B8%B5/%E0%B8%A5%E0%B8%B2%E0%B8%81%E0%B9%88%E0%B8%AD%E0%B8%99', $this->Url->rawUrlEncodeSegments('hello/สวัสดี/ลาก่อน'));
        $this->assertSame('hello/%E0%B8%AA%E0%B8%A7%E0%B8%B1%E0%B8%AA%E0%B8%94%E0%B8%B5?search=search+value&option[0]=no&option[1]=no&option[2]=yes', $this->Url->rawUrlEncodeSegments('hello/สวัสดี?search=search+value&option[0]=no&option[1]=no&option[2]=yes'));
        $this->assertSame('hello/%E0%B8%AA%E0%B8%A7%E0%B8%B1%E0%B8%AA%E0%B8%94%E0%B8%B5', $this->Url->rawUrlEncodeSegments('hello/สวัสดี?'));
        $this->assertSame('hello/%E0%B8%AA%E0%B8%A7%E0%B8%B1%E0%B8%AA%E0%B8%94%E0%B8%B5', $this->Url->rawUrlEncodeSegments('hello/สวัสดี?#'));
        $this->assertSame('php/%E0%B8%9E%E0%B8%B5%E0%B9%80%E0%B8%AD%E0%B9%87%E0%B8%8A%E0%B8%9E%E0%B8%B5', $this->Url->rawUrlEncodeSegments('php/พีเอ็ชพี'));
        $this->assertSame('hello%20world', $this->Url->rawUrlEncodeSegments('hello world'));// space will be `%20` NOT `+`.
        $this->assertSame('question%20%E0%B8%84%E0%B8%B3%E0%B8%95%E0%B8%AD%E0%B8%9A', $this->Url->rawUrlEncodeSegments('question คำตอบ'));
        $this->assertSame('question คำตอบ', rawurldecode('question%20%E0%B8%84%E0%B8%B3%E0%B8%95%E0%B8%AD%E0%B8%9A'));
        $this->assertSame('http://localhost.localhost/lang/%E0%B8%A0%E0%B8%B2%E0%B8%A9%E0%B8%B2%E0%B9%84%E0%B8%97%E0%B8%A2/question/%E0%B8%84%E0%B8%B3%E0%B8%95%E0%B8%AD%E0%B8%9A', $this->Url->rawUrlEncodeSegments('http://localhost.localhost/lang/ภาษาไทย/question/คำตอบ'));
        $this->assertSame('https://localhost.localhost/lang/%E0%B8%A0%E0%B8%B2%E0%B8%A9%E0%B8%B2%E0%B9%84%E0%B8%97%E0%B8%A2/question/%E0%B8%84%E0%B8%B3%E0%B8%95%E0%B8%AD%E0%B8%9A', $this->Url->rawUrlEncodeSegments('https://localhost.localhost/lang/ภาษาไทย/question/คำตอบ'));
        $this->assertSame('ftp://localhost.localhost/lang/%E0%B8%A0%E0%B8%B2%E0%B8%A9%E0%B8%B2%E0%B9%84%E0%B8%97%E0%B8%A2/question/%E0%B8%84%E0%B8%B3%E0%B8%95%E0%B8%AD%E0%B8%9A', $this->Url->rawUrlEncodeSegments('ftp://localhost.localhost/lang/ภาษาไทย/question/คำตอบ'));
        $this->assertSame('//localhost.localhost/lang/%E0%B8%A0%E0%B8%B2%E0%B8%A9%E0%B8%B2%E0%B9%84%E0%B8%97%E0%B8%A2/question/%E0%B8%84%E0%B8%B3%E0%B8%95%E0%B8%AD%E0%B8%9A', $this->Url->rawUrlEncodeSegments('//localhost.localhost/lang/ภาษาไทย/question/คำตอบ'));
        $this->assertSame('http://my@email.tld:password@localhost.localhost:80/lang/%E0%B8%A0%E0%B8%B2%E0%B8%A9%E0%B8%B2%E0%B9%84%E0%B8%97%E0%B8%A2/question/%E0%B8%84%E0%B8%B3%E0%B8%95%E0%B8%AD%E0%B8%9A?question=คำตอบ#anchorไทย', $this->Url->rawUrlEncodeSegments('http://my@email.tld:password@localhost.localhost:80/lang/ภาษาไทย/question/คำตอบ?question=คำตอบ#anchorไทย'));
    }// testRawUrlEncodeSegments


    public function testRawUrlEncodeUsernamePassword()
    {
        $this->assertSame('http://my%40email.tld:%E0%B8%9C%E0%B9%88%E0%B8%B2%E0%B8%99word@localhost.localhost:80/lang/ภาษาไทย/question/คำตอบ?question=คำตอบ#anchorไทย', $this->Url->rawUrlEncodeUsernamePassword('http://my@email.tld:ผ่านword@localhost.localhost:80/lang/ภาษาไทย/question/คำตอบ?question=คำตอบ#anchorไทย'));
    }// testRawUrlEncodeUsernamePassword


    public function testRemoveQuerystring()
    {
        $this->assertEquals('/my/controller/method', $this->Url->removeQuerystring('/my/controller/method?name1=value1&name2=value2&encoded1=hello%3Dworld%26goodbye%3Dworld'));
        $this->assertEquals('/my/controller/method', $this->Url->removeQuerystring('/my/controller/method?name1=value1&name2=value2&encoded1=hello%3Dworld%26goodbye%3Dworld?invalud=querystring'));
    }// testRemoveQuerystring


    public function testRemoveUnsafeUrlCharacters()
    {
        // test multiple spaces, new lines, tabs------
        $string = <<<EOT
space space       spaces
new line


new lines
tab tab                 tabs
EOT;
        // multiple spaces, new lines, tabs become a single space.
        // space become dash.
        $assert =  <<<EOT
space-space-spaces
new-line-new-lines
tab-tab-tabs
EOT;
        $this->assertSame($assert, $this->Url->removeUnsafeUrlCharacters($string));

        // test alpha-numeric only ----------------------
        $string = <<<EOT
space space       spaces พทว่าง    พทว่าง
new line


new lines
tab tab                 tabs
EOT;
        // new line is non alpha-numeric then it will be removed.
        $assert =  <<<EOT
space-space-spaces--new-line-new-linestab-tab-tabs
EOT;
        $this->assertSame($assert, $this->Url->removeUnsafeUrlCharacters($string, true));

        // test non alpha-numeric. ---------------------
        $string = <<<EOT
space space       spaces พทว่าง    พทว่าง
new line


new lines
tab tab                 tabs
w3safe $@&+
w3extra !*"\'(),
w3reserved =;/#?:
w3escape %
w3national {}[]\\^~
w3punctation <>
other unsafe |
EOT;
        // new line is non alpha-numeric then it will be removed.
        $assert =  <<<EOT
space-space-spaces-พทว่าง-พทว่าง
new-line-new-lines
tab-tab-tabs
w3safe-
w3extra-
w3reserved-
w3escape-
w3national-
w3punctation-
other-unsafe-
EOT;
        $this->assertSame($assert, $this->Url->removeUnsafeUrlCharacters($string));

        // test special characters. -----------------------
        $string = <<<EOT
abc/กขค/123/๑๒๓/after this are special chars available on keyboards./!@#$%^&*()_+-=\|[]{};:'"?,.<>/end
EOT;
        $assert = <<<EOT
abcกขค123๑๒๓after-this-are-special-chars-available-on-keyboards._-.end
EOT;
        $this->assertSame($assert, $this->Url->removeUnsafeUrlCharacters($string));
    }// testRemoveUnsafeUrlCharacters


}
