<?php
/** 
 * The middleware configuration.
 * 
 * Contain middleware in array.
 * The middleware array contain 2 key names: `beforeMiddleware`, `afterMiddleware`.
 * The `beforeMiddleware` will be run before application start and `afterMiddleware` will be run after application started.
 * Each key will be run from top to bottom.
 * 
 * The middleware array value is the handle function, class.
 * Example: `\Rdb\Modules\Users\Middleware\RequireAuth:init`
 * This will be call class name `\Rdb\Modules\Users\Middleware\RequireAuth` and `init` method.
 * 
 * The middleware must accept and return the response content in its method.
 * 
 * @license http://opensource.org/licenses/MIT MIT
 */



// `beforeMiddleware`, the middleware that will be run before application start. ---------------------------------
$beforeMiddleware = [];

// The framework's middleware, please keep it first if you don't have anything to run before it.
$beforeMiddleware[0] = '\Rdb\System\Middleware\Profiler:init';// needs to be close the process at afterMiddleware.
$beforeMiddleware[1] = '\Rdb\System\Middleware\ErrorHandler:init';
$beforeMiddleware[2] = '\Rdb\System\Middleware\RemoveTrailingSlash:run';
$beforeMiddleware[3] = '\Rdb\System\Middleware\I18n:init';
// add middleware below.



// `afterMiddleware`, the middleware that will be run after application started. ----------------------------------
$afterMiddleware = [];

// add middleware below.

// The framework's middleware, please keep it last if you don't have anything to run after it.
$afterMiddleware[1000] = '\Rdb\System\Middleware\Profiler:end';



// done add middleware return all values.
return [
    'beforeMiddleware' => $beforeMiddleware,
    'afterMiddleware' => $afterMiddleware,
];