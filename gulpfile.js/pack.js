/**
 * Pack folders and files into zip that is ready to use in distribute release.
 */


'use strict';


const {series, parallel, src, dest} = require('gulp');
let argv = require('yargs').argv;
const fs = require('fs');
const print = require('gulp-print').default;
const zip = require('gulp-zip');


/**
 * Pack files for production or development zip.
 * 
 * To pack for development, run `gulp pack --development` or `npm run pack -- --development`.<br>
 * To pack for production, run `gulp pack` or `npm run pack`.
 * 
 * @param {type} cb
 * @returns {unresolved}
 */
function packDist(cb) {
    let phpContentContainsVersion = fs.readFileSync('./System/App.php', 'utf-8');
    let regexPattern = /@version(\s?)(?<version>[\d\.]+)/miu;
    let matched = phpContentContainsVersion.match(regexPattern);
    let moduleVersion = 'unknown';
    if (matched && matched.groups && matched.groups.version) {
        moduleVersion = matched.groups.version;
    }

    let isProduction = true;
    if (argv.development) {
        isProduction = false;
    }

    let targetDirs = [];
    if (isProduction === true) {
        targetDirs = [
            './**',
            '!.*/**',
            '!Modules/**',// skip all files and subfolders in Modules.
            'Modules/*.*',// include only files in Modules.
            '!config/development/**',
            '!config/production/**',
            '!gulpfile.js/**',
            '!node_modules/**',
            '!public/**',// skip all files and subfolders in public.
            'public/*.*',// include only files in public.
            '!storage/**',// skip all files and subfolders in storage.
            'storage/*.*',// include only files in storage.
            '!System/vendor/**/**/*',// skip everything in System/vendor.
            'System/vendor/*.*',// include only files in System/vendor.
            '!System/vendor/*.php',// skip everything that is .php in System/vendor.
            '!Tests/**',
            '!composer.json',
            '!composer.lock',
            '!mkdocs.yml',
            '!package*.json',
            '!phpdoc.xml',
            '!phpunit.xml',
        ];
    } else {
        targetDirs = [
            './**',
            '!.git/**',
            '!.phpdoc/**',
            '!.dist/**',
            '!Modules/**',// skip all files and subfolders in Modules.
            'Modules/*.*',// include only files in Modules.
            '!config/development/**',
            '!config/production/**',
            '!node_modules/**',
            '!public/**',// skip all files and subfolders in public.
            'public/*.*',// include only files in public.
            '!storage/**',// skip all files and subfolders in storage.
            'storage/*.*',// include only files in storage.
            '!System/vendor/**/**/*',// skip everything in System/vendor.
            'System/vendor/*.*',// include only files in System/vendor.
            '!System/vendor/*.php',// skip everything that is .php in System/vendor.
            '!composer.json',
            '!composer.lock',
            '!package-*.json',
        ];
    }
    let zipFileName;
    if (isProduction === true) {
        zipFileName = 'rundizbones v' + moduleVersion + '.zip';
    } else {
        zipFileName = 'rundizbones dev.zip';
    }

    return src(targetDirs, {base : ".", dot: true})
        .pipe(print())
        .pipe(zip(zipFileName))
        .pipe(dest('.dist/'));
}// packDist


/**
 * Get module version from Install.php and write it to package.json.
 * 
 * @param {type} cb
 * @returns {unresolved}
 */
function writePackageVersion(cb) {
    let installerPhpContent = fs.readFileSync('./System/App.php', 'utf-8');
    let regexPattern = /@version(\s?)(?<version>[\d\.]+)/miu;
    let matched = installerPhpContent.match(regexPattern);
    let moduleVersion = 'unknown';
    if (matched && matched.groups && matched.groups.version) {
        moduleVersion = matched.groups.version;
    }

    let packageJson = JSON.parse(fs.readFileSync('./package.json'));
    packageJson.version = moduleVersion;

    fs.writeFileSync('./package.json', JSON.stringify(packageJson, null, 4));

    return cb();
}// writePackageVersion


exports.pack = series(
    writePackageVersion,
    packDist
);