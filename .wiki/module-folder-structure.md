# Module folder structure

The folder structure to create in a module that you are working with.

```
ModuleName/
    assets/
        css/
            style.css
        js/
            my-javascript.js
    config/
        default/
            routes.php
        development/
            routes.php
        production/
            routes.php
    Console/
        MyConsole.php
    Controllers/
        MyModuleController.php
    phinxdb/
        migrations/
        seeds/
    Tests/
        MyUnitTest.php
    Views/
        mymodulecontroller_index_v.php
    Installer.php
    moduleComposer.json
```

### Disable or enable
If there is a file named **.disabled** in the module folder (same location as **Installer.php**) then the module will be disable and not working.<br>
To make module enable or work again, just delete **.disabled** file.

### Namespace
Any classes including controllers in the module should have namespace begins with `Modules\ModuleName` where **ModuleName** is the real folder name of your module.<br>
Example:
```
<?php
// Modules/ModuleName/Controllers/MyPageController.php file.

namespace Modules\ModuleName\Controllers;

class MyPageController extends \System\Core\Controllers\BaseController
{
    // your code.
}
```

---

### assets folder
The **assets** is optional. It is only need if you have some public assets file to use.<br>
The **assets** folder will be copied to **public/Modules/ModuleName/assets**.<br>
To use public assets, you have to run `php rdb system:module install --mname="ModuleName"` command where **ModuleName** is the real folder name of your module.

### config folder
The module should contain at least **routes.php** and its controller.<br>
Example:
```
<?php
// Modules/ModuleName/config/default/routes.php file.

/* @var $Rc \FastRoute\RouteCollector */
/* @var $this \System\Router */


$Rc->addRoute('GET', '/modulename', '\\Modules\\ModuleName\\Controllers\\MyPage:index');
// the MyPage controller will be MyPageController class and index will be indexAction method.
```

The config folder is the same as main app's config. It is based on environment setting in the **public/index.php** file.

### Console folder
The **Console** folder is for the code that will be run via CLI. It is optional and only need if you have some CLI app here.<br>
Example:
```
<?php
// Modules/ModuleName/Console/MyConsole.php file.
// read more about how to write console at https://symfony.com/doc/3.3/components/console.html

namespace Modules\ModuleName\Console;

class MyConsole extends \System\Core\Console\BaseConsole
{
    protected function configure()
    {
        // config code here.
        // https://symfony.com/doc/3.3/console.html
    }

    protected function execute()
    {
        // execute code here.
        // https://symfony.com/doc/3.3/console.html
    }
}
```

The above code is for setup the new command. If you want to include any existing commands, please use the following code.
```
<?php
// Modules/ModuleName/Console/MyIncludeExternalConsole.php file.

namespace Modules\ModuleName\Console;

class MyIncludeExternalConsole
{
    public function IncludeExternalCommands(\Symfony\Component\Console\Application $CliApp)
    {
        // $CliApp->add(); or $CliApp->addCommands();
    }
}
```

### Controllers folder

The **Controller** folder is for the code that will be run via HTTP request. It is optional and only need if you have some HTTP app here.<br>
Controllers will be resolve from routes in config folder. The controller must extends `\System\Core\Controllers\BaseController` class.<br>
Example:
```
<?php
// Modules/ModuleName/Controllers/MyPageController.php file.

namespace Modules\ModuleName\Controllers;

class MyPageController extends \System\Core\Controllers\BaseController
{
    public function indexAction()
    {
        $output = [];
        $output['name'] = 'World';

        return $this->Views->render('MyPageController/index_v', $output);
    }// indexAction
}
```

### phinxdb folder
This folder is contain the DB migration using [Phinx](http://docs.phinx.org). There are 2 more sub folders in here 1. is **migrations**, 2. is **seeds**.<br>
The **phinxdb** folder is optional and only need if you have DB migration to work.

### Tests folder
The **Tests** folder is for unit test, and is optional. It is only need if you have some unit testing.<br>
Example:
```
<?php
// Modules/ModuleName/Tests/MyUnitTest.php file.

namespace Modules\ModuleName\Tests;

class MyUnitTest extends \Tests\Rdb\BaseTestCase
{
    public function testBasic()
    {
        $this->assertTrue(true);
    }
}

```

### Views folder
The **Views** folder is optional. It is only need if your controller use views.<br>
Example:
```
<?php
// Modules/ModuleName/Views/MyPageController/index_v.php
?>

<!DOCYPE html>
<html>
    <head>
        <title>Views demo</title>
    </head>
    <body>
        Hello <?php echo $name; ?>.
    </body>
</html>
```

### Installer.php
The **Installer.php** is optional. It is only need if you use install, update, uninstall feature.<br>
Example:
```
<?php
// Modules/ModuleName/Installer.php file.

namespace Modules\ModuleName;

class Installer implements \System\Interfaces\ModuleInstaller
{
    /**
     * @var \System\Container
     */
    protected $Container;

    public function __construct(\System\Container $Container)
    {
        $this->Container = $Container;
    }
    
    public function install()
    {
        // install code here.
    }

    public function uninstall()
    {
        // uninstall code here.
    }

    public function update()
    {
        // update code here.
    }
}
```

### moduleComposer.json
The **moduleComposer.json** is optional. It is only need if you want composer dependency list to use.<br>
To use module's composer, run the command `php rdb system:module --help` to see all available commands about module.