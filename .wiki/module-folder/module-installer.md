# Module Installer
Module Installer is the main file of each module and maybe required in some case. The file name must be **Installer.php** (case sensitive) in your module's root folder.
For example: your module folder name is **Contact** the installer file will be in **Modules/Contact/Installer.php**.

## Installer file header
This is about file level DocBlock, not class level DocBlock. The file level is the first comment block after open PHP tag (`<?php`).<br>
The file header is optional but will be use to tell the information about the module.

### Minimum requirement
If you want to use Installer file header, this is the minimum requirement fields.

```php
<?php
/**
 * Module Name: Your Module Name.
 */
```

### Header fields
The header must be case sensitive and maybe follow with colon (**:**) or just using PHPDoc tags ([1], [2]) that is available in the list below.
 All available header fields:

* **Module Name:** *(required)*  The name of your module.
* **Description:** A short description of your module.
* **@version** The current version number of your module.
* **Requires PHP:** The minimum requirement for PHP version.
* **Requires Modules:** The other module folder names that your module is required. Use comma as separator. Example: `Requires Modules: Languages, RdbAdmin`.
* **Author:** The module's author name. Multiple authors use comma as seperator.
* **@license** The license URL and license name. Example: `@license http://opensource.org/licenses/MIT MIT`.

### Example

```php
<?php
/**
 * Module Name: My module
 * Description: Additional functional to the admin page.
 * Requires PHP: 7.1.0
 * Requires Modules: RdbAdmin
 * Author: John Doe, Jane Doe
 *
 * @version 1.0.1
 * @license http://opensource.org/licenses/MIT MIT
 */
```

## Installer class
The `Installer` class is optional. It is only need if you use install, update, uninstall feature.

### Example:
```php
<?php
/**
 * File level DocBlock.
 * Modules/[ModuleName]/Installer.php
 */

namespace Rdb\Modules\ModuleName;

/**
 * Class level DocBlock.
 */
class Installer implements \Rdb\System\Interfaces\ModuleInstaller
{
    /**
     * Class constructor DocBlock.
     *
     * @var \Rdb\System\Container
     */
    protected $Container;

    public function __construct(\Rdb\System\Container $Container)
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

[1]: https://pear.php.net/package/PhpDocumentor/docs/latest/phpDocumentor/tutorial_tags.pkg.html
[2]: https://manual.phpdoc.org/HTMLSmartyConverter/HandS/phpDocumentor/tutorial_tags.pkg.html