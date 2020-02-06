<?php
/** 
 * Routes configuration.
 * 
 * Use route collector from FastRoute directly.<br>
 * Example:
 * <pre>
 * $Rc->addRoute($this->filterMethod('any'), '/', '\\System\\Core\\Controllers\\Default:index');
 * $Rc->addGroup('/admin', function (\FastRoute\RouteCollector $Rc) {
 *     $Rc->addRoute('GET', '', '\\Modules\\Admin\\Controllers\\Admin\\Index:index');
 *     $Rc->addRoute('GET', '/login', '\\Modules\\Admin\\Controllers\\Admin\\Login:index');
 *     $Rc->addGroup('/users', , function (\FastRoute\RouteCollector $Rc) {
 *         $Rc->addRoute('GET', '', '\\Modules\\Users\\Controllers\\Admin\\Users:index');
 *     });
 * });
 * </pre>
 * The HTTP method must be upper case or use `$this->filterMethod('any')` to get methods in array `['GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH']`.
 * 
 * Fore more reference, please look at FastRoute document. https://github.com/nikic/FastRoute
 * 
 * You can use methods from `\System\Router` class directly via `$this` object since this file will be include into the class.<br>
 * The class in handler will be automatically add `Controller` suffix, and the method in handler will be automatically add `Action` suffix.<br>
 * `Users:index` will be `UsersController` class and `indexAction` method.
 * 
 * This config is working in modules.
 * 
 * @license http://opensource.org/licenses/MIT MIT
 */


/* @var $Rc \FastRoute\RouteCollector */
/* @var $this \System\Router */


// The default route and default controller. You can remove or replace it.
$Rc->addRoute($this->filterMethod('any'), '/rundizbones', '\\System\\Core\\Controllers\\Default:index');
