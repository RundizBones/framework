<?php
/** 
 * Language configuration.
 * 
 * @license http://opensource.org/licenses/MIT MIT
 */


return [
    // The language detection method. Accepted value is 'cookie', or 'url'.
    // The 'cookie' value will be store on the cookie name 'rundizbones_language'.
    // The 'url' value will be detect from first URL sector after application location.
    // Example: http://localhost/myapp/en-US or just http://localhost/myapp depend on `languageUrlDefaultVisible` configuration.
    'languageMethod' => 'url',

    // If `languageMethod` is 'url', the default language will be visible on the URL or not.
    // Set to `true` for make it always visible, `false` for hide only default language.
    // For example, your default language is 'en-US' and the URL is http://localhost/myapp then the 'en-US' language will be use by default.
    // Otherwise it will be depend on the URL such as http://localhost/myapp/th will be Thai language.
    'languageUrlDefaultVisible' => false,

    // Supported languages list.
    // The value will be associate array: 
    // The `array key` is the locale for use in the URL or cookie and is the identity.
    //      This is the identity of each language, it must be unique.
    //      Example: http://localhost/myapp/th, the 'th' is locale in the URL.
    // `languageLocale` is the locale in PHP.
    //      It can be string (for single locale) or array (for multiple locale).
    //      See https://www.php.net/manual/en/function.setlocale.php
    // `languageName` is the language name. (string).
    // `languageDefault` is define the default value to be use. (bool).
    'languages' => [
        'en-US' => [
            'languageLocale' => ['en_US.UTF-8', 'en-US.UTF-8', 'en.UTF-8', 'en-US', 'en_US', 'en'],
            'languageName' => 'English',
            'languageDefault' => false,
        ],
        'th' => [
            'languageLocale' => ['th_TH.UTF-8', 'th-TH.UTF-8', 'th.UTF-8', 'th-TH', 'th_TH', 'th'],
            'languageName' => 'Thai',
            'languageDefault' => true,
        ],
    ],
];