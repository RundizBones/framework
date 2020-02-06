<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace System\Interfaces;


/**
 * Module installer interface.
 * 
 * @since 0.1
 */
interface ModuleInstaller
{


    /**
     * The class constructor.
     * 
     * @param \System\Container $Container The DI container class.
     */
    public function __construct(\System\Container $Container);


    /**
     * Install a module.
     * 
     * This method will be call on install a module.<br>
     * If your module contain some thing to work on install such as create table in database, you can use that command here.<br>
     * If your module has nothing to work on install, you can create this method and leave it empty.
     * 
     * Any installation script here if they failed, it should throw the errors to make a notice that installation is failure.
     * 
     * @throws \Exception Throw the exception error in something failed.
     */
    public function install();


    /**
     * Uninstall a module.
     * 
     * This method will be called on uninstall and it will be delete the module's files.
     * 
     * This method will be call on uninstall module.<br>
     * If your module contain some thing to work on uninstall such as remove table in database, you can use that command here.<br>
     * If your module has nothing to work on uninstall, you can create this method and leave it empty.
     * 
     * Any uninstallation script here if they failed, it should throw the errors to make a notice that uninstallation is failure.
     * 
     * @throws \Exception Throw the exception error in something failed.
     */
    public function uninstall();


    /**
     * Update a module.
     * 
     * This method will be call on update a module.<br>
     * If your module contain something to work on update such as migration, update the database, you can use that command here.<br>
     * If your module has nothing to work on update, you can create this method and leave it empty.
     * 
     * Any update script here if they failed, it should throw the errors to make a notice that update is failure.
     * 
     * @throws \Exception Throw the exception error in something failed.
     */
    public function update();


}