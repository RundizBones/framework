# Getting started

## Requirement
This framework require at least PHP 7.1 to work.

## Installation

### Quick install.
* Extract files to your location.
* Copy **composer.default.json** to **composer.json**.
* Copy **rdb.default** to **rdb**.
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
rdb.default
```
These are folders and files that is required to make it work. The other folders and files such as **.api-doc**, **.wiki**, etc can be deleted.

* **config** folder contains configuration files for the framework.
* **Modules** folder contains the working module. Your code work as module.
* **public** folder is the folder that will be serve via HTTP. 
    If your root web server is the other name such as **public_html**, or **www** you have to modify **index.php**, **rdb** files. (Please read more description below.)
* **storage** folder contains server side storage folders and files such as cache, logs. Some modules may put the server side files here to prevent access from public.
* **System** folder contains the framework files.
* **Tests** folder contains unit tests.<br>
    ---*This folder is no need on production.*---
* **composer.default.json** file contains required default packages that make this framework working properly.<br>
    ---*This file is no need on production.*---
* **composer.json** file is the copied file from **composer.default.json** but contains another packages from the modules. 
    This will be auto update via **rdb** command, do not manually write anything here.<br>
    ---*This file maybe no need on production.*---
* **LICENSE** file contains license of this framework.
* **phinx.php** file is the configuration file required by [Phinx] for DB migration.<br>
    ---*This file maybe no need on production.*---
* **phpunit.xml** file is configuration file for unit tests.<br>
    ---*This file is no need on production.*---
* **rdb** file is the copied file from **rdb.default** file but it might be modified to matched your environment.<br>
    ---*This file maybe no need on production. WARNING! It contains commands that affect functions such as enabling or disabling modules. Use with care.*---
* **rdb.default** file is the framework command to run via console or command prompt on Windows.<br>
    ---*This file is no need on production.*

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
The public folder or directory is where the web server will be serve to the public. The root of public folder will be called **root web**.<br>
You can use different root web name and location. For example: **/public_html**, **/www**, **/public**, or even in sub folder such as **/public/sub1/sub2/sub3**.<br>

The default location of framework's root web is located in **public** folder. Upload everything here to your desired location and then modify the files as explained below.

##### use /public_html as root web
```
config/
Modules/
public_html/
    .htaccess
    index.php
    ...
storage/
...
```

1. Open **rdb** file and modify `define('PUBLIC_PATH', __DIR__ . DIRECTORY_SEPARATOR . 'public_html');`

##### Use /www as root web
```
config/
Modules/
www/
    .htaccess
    index.php
    ...
storage/
...
```

1. Open **rdb** file and modify `define('PUBLIC_PATH', __DIR__ . DIRECTORY_SEPARATOR . 'www');`

##### Use /public/sub1/sub2/sub3 as root web
```
config/
Modules/
public/
    sub1/
        sub2/
            sub3/
                .htaccess
                index.php
    ...
storage/
...
```

1. Open **index.php** file in root web and modify `define('ROOT_PATH', dirname(__DIR__, 4));`
2. Open **rdb** file and modify `define('PUBLIC_PATH', __DIR__ . '/public/sub1/sub2/sub3');`

##### Use the root web at the same level as framework
```
config/
Modules/
storage/
System/
.htaccess
index.php
```

1. Open **index.php** file in root web and modify `define('ROOT_PATH', __DIR__);`
2. Open **rdb** file and modify `define('PUBLIC_PATH', __DIR__);`

#### rdb
Copy **rdb.default** to **rdb**.

#### composer.json
Copy **composer.default.json** to **composer.json** and then run the command `composer install`.

You may copy **composer.default.json** to **composer.mydefault.json** to override its default with your modification such as make change to packages version that is required.<br>
The **composer.mydefault.json** will be use when update module with the command `update` or `install` with `rdb` such as `php rdb system:module update --mname="YourModule"`.<br>
You have to run the command `composer install` or `composer update` again to update the packages.

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
