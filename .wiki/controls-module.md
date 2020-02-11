# Controls the module

The module can be install, uninstall, update, enable, disable. If the module has some database installation, it can be run via install or update command.
Disable the module will completely make module stop functional but the files still exists. Uninstall the module may delete or just disable it but the uninstallation script will work.

## Install a module

If you have a new module and you want to install it, please extract the module into **Modules** folder and run the command from RundizBones root application `php rdb system:module install --mname="YourModule"`.
For example, you just extract module **Contact** to **Modules** folder then run the command `php rdb system:module install --mname="Contact"`.<br>
Make sure that the `--mname="..."` option is correct case (case sensitive).

You may have to run `composer update` if the module you installed have some packages in **moduleComposer.json**.

## Update a module

Some module may require the update command, in that case you can run the command `php rdb system:module update --mname="YourModule"`.

You may have to run `composer update` if the module you installed have some packages in **moduleComposer.json**.

## Uninstall a module

To uninstall a module, use the command `php rdb system:module uninstall --mname="YourModule"`. It will be delete a certain module folder, if you want to keep it just add `--nodelete` option.

The uninstallation may or may not delete module folder depend on `--nodelete` option but the module folder on **public** folder will be deleted if **public** folder is not same as the framework's root folder.

## Enable a module

Enable a module is for after you had disabled it. To enable a module, delete **.disabled** file or run the command `php rdb system:module enable --mname="YourModule"`.

## Disable a module

Disable a module completely make it stop functional but the files still exists.

---

You can read more help message by run the command `php rdb system:module --help`.