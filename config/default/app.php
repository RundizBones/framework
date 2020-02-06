<?php
/** 
 * Application configuration.
 * 
 * @license http://opensource.org/licenses/MIT MIT
 */


return [
    // Add content-length to header or not (`true` to add, `false` for not). If you use profiler, I suggest to turn this off.
    'addContentLengthHeader' => true,

    // FastRoute can cache the routes. If you want to use cached routes please set this to `true` otherwise set it to `false`.
    'routesCache' => false,
    // If you enabled cached routes, how many days to cache?
    'routesCacheExpire' => 30,

    // Profiler. set to true to enable profiler, however you have to install packages from composer with development mode.
    // It is not recommend to enable this feature in production mode.
    'profiler' => false,
    // Profiler sections.
    // All available sections are 'Logs', 'Time Load', 'Memory Usage', 'Database', 'Files', 'Session', 'Get', 'Post'.
    // For more information please see Rundiz/Profiler on Github.
    // The "Files" included might no need to show because there are too much files and can cause slow performance.
    'profilerSections' => [
        'Logs', 'Time Load', 'Memory Usage', 'Database', /*'Files', */'Session', 'Get', 'Post'
    ],

    // Log file configuration.
    'log' => [
        // Set to `true` to enable log file. Set to `false` to not use log file.
        'enable' => false,
        // Do not log when log level is UNDER selected. 0=debug (or log it *ALL*), 1=info, 2=notice, 3=warning, 4=error, 5=critical, 6=alert, 7=emergency
        // This config is depend on enable, if it is disabled then nothing will be write to log file.
        'donot_log_level' => 1,
    ],
];