/**
 * Main Gulp file.
 */


'use strict';


const {series, parallel, src, dest, watch} = require('gulp');
const fs = require('fs');
// my gulp tasks.
const pack = require('./pack');


exports.pack = series(
    pack.pack
);