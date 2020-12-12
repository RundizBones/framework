# Getting started

## Requirement
This framework require at least PHP 7.1 to work.

## Installation

### Quick install.
* Extract files to your location.
* Copy **composer.default.json** to **composer.json**.
* Run `composer install` command.
* Browse to your installed URL and follow with **/rundizbones**. Example: http://localhost.localhost/rundizbones
* If your installed correctly, it will showing the welcome page.

### Detailed installation.
Extract framework files to your location. The folder structure for the framework will be:
```
config/
Modules/
public/
storage/
System/
Tests/
composer.default.json
composer.json
LICENSE
phinx.php
phpunit.xml
rdb
```
These are folders and files that is required to make it work. The other folders and files such as **.api-doc**, **.wiki**, etc can be deleted.

* **config** folder contains configuration files for the framework.
* **Modules** folder contains the working module. Your code work as module.
* **public** folder is the folder that will be serve via HTTP. 
    If your root web server is the other name such as **public_html**, or **www** you have to modify index.php file. (Please read more description below.)
* **storage** folder contains temporary folders and files such as cache, logs. 
    This folder can be cleared by the **rdb** command, do not put anything important in this folder.
* **System** folder contains the framework files.
* **Tests** folder contains unit tests.<br>
    ---*This folder is no need on production website.*---
* **composer.default.json** file contains required default packages that make this framework working properly.<br>
    ---*This file maybe no need on production.*---
* **composer.json** file contains required default packages from composer.default.json and another packages from the modules. 
    This will be auto generate via **rdb** command, do not manually write anything here.<br>
    ---*This file maybe no need on production.*---
* **LICENSE** file contains license of this framework.
* **phinx.php** file is the configuration file required by [Phinx] for DB migration.<br>
    ---*This file maybe no need on production.*---
* **phpunit.xml** file is configuration file for unit tests.<br>
    ---*This file is no need on production website.*---
* **rdb** file is the framework command to run via console or command prompt on Windows.<br>
    ---*This file maybe no need on production. WARNING! It contains commands that affect functions such as enabling or disabling modules. Use with care.*---

Upload required folders and files as description above to your server. Everything inside **public** folder will be upload to your root web server path. 
Other folders and files will be upload to the path that is outside root web server.<br>
The uploaded folder structure will be:

```
config/
Modules/
public/
    .htaccess
    index.php
    favicon.ico
    ...
storage/
...
```

Or you may upload everything into the same root web server path but you have to modify **index.php** as describe below.

#### public folder
For any server that has the root web server in different name such as **public_html**, **www** please upload anything in **public** folder to your root web server and edit your **index.php** file as description below.<br>
Or if you want to use the framework as sub directory please read the description below.

For any root web server name such as **public**, **public_html**, **www**, etc will now call **rootweb**.

If your **index.php** file is in **/rootweb** then do not modify anything.<br>
If your **index.php** file is in **/rootweb** and all the framework folders and files are in the same **rootweb** then modify `ROOT_PATH` in the **index.php** file to `define('ROOT_PATH', __DIR__);`<br>
If your **index.php** file is in **/rootweb/subdirectory1** then modify `ROOT_PATH` in the **index.php** file to `define('ROOT_PATH', dirname(dirname(__DIR__)));`<br>
If your **index.php** file is in **/rootweb/subdirectory1/subdirectory2/subdirectory3** then modify `ROOT_PATH` in the **index.php** file to `define('ROOT_PATH', dirname(dirname(dirname(dirname(__DIR__)))));`

Also modify `PUBLIC_PATH` in the **rdb** file to correct path to the index.php file.

#### composer.json
Copy **composer.default.json** to **composer.json** and then run the command `composer install`.

#### Verify your installation
Browse the web page to http://yourdomain/installdir**/rundizbones** such as http://localhost.localhost/rundizbones.<br>
If you see welcome message then installation is finished successfully.

## Modify configuration
On your **public/index.php** file, set your `APP_ENV` constant to `development` or `production` depend on your usage.<br>
Copy the only configuration file you want to make change from **config/default** folder to your **config/`APP_ENV`**.<br>
For example: if you set `APP_ENV` to `development` then copy config file to **config/development** folder.

Read the description on each configuration file. Modify configuration to your need and save changed. Maybe upload to your server if you modify it on local but run on server.

---

To start write your module, please continue reading on [Module folder structure][mdfs].


[Phinx]:https://phinx.org
[mdfs]: module-folder-structure.md
