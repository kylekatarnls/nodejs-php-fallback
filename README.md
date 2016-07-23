# NodejsPhpFallback
[![Latest Stable Version](https://poser.pugx.org/nodejs-php-fallback/nodejs-php-fallback/v/stable.png)](https://packagist.org/packages/nodejs-php-fallback/nodejs-php-fallback)
[![Build Status](https://travis-ci.org/kylekatarnls/nodejs-php-fallback.svg?branch=master)](https://travis-ci.org/kylekatarnls/nodejs-php-fallback)
[![StyleCI](https://styleci.io/repos/62958645/shield?style=flat)](https://styleci.io/repos/62958645)
[![Test Coverage](https://codeclimate.com/github/kylekatarnls/nodejs-php-fallback/badges/coverage.svg)](https://codecov.io/github/kylekatarnls/nodejs-php-fallback?branch=master)
[![Code Climate](https://codeclimate.com/github/kylekatarnls/nodejs-php-fallback/badges/gpa.svg)](https://codeclimate.com/github/kylekatarnls/nodejs-php-fallback)

Allow you to call node.js module or scripts throught PHP and call a fallback function if node.js is not available.

## Usage

Edit **composer.json** to add **nodejs-php-fallback** to *"require"*, your *"npm"* dependancies to *"extra"* and ```"NodejsPhpFallback\\NodejsPhpFallback::install"```to both *"post-install-cmd"* and *"post-update-cmd"* in *"scripts"*

For example, to use node.js **stylus** and fallback to the php **kylekatarnls/stylus** port, use:
```json
...
"require": {
    "nodejs-php-fallback/nodejs-php-fallback": "*",
    "kylekatarnls/stylus": "*"
},
"extra": {
    "npm": {
        "stylus": "^0.54"
    }
},
"scripts": {
    "post-install-cmd": [
        "NodejsPhpFallback\\NodejsPhpFallback::install"
    ],
    "post-update-cmd": [
        "NodejsPhpFallback\\NodejsPhpFallback::install"
    ]
},
...
```

With this configuration, both node **stylus** and php **kylekatarnls/stylus** packages will be installed and updated when you update or install with composer if node is installed, else, only the php package will be.

So you can easily create a function that will try first to call the node package, then else the php one:

```php
use NodejsPhpFallback\NodejsPhpFallback;
use Stylus\Stylus;

function getCssFromStylusFile($stylusFile)
{
    $node = new NodejsPhpFallback();
    $fallback = function () use ($stylusFile) {
        $stylus = new Stylus();

        return $stylus->fromFile($stylusFile)->toString();
    }

    return $node->execModuleScript('stylus', 'bin/stylus', '--print ' . escapeshellarg($stylusFile), $fallback);
}

$css = getCssFromStylusFile('path/to/my-stylus-file.styl');
```
Here ```$css``` will contain CSS code rendered from your stylus file, no matter node is installed or not. So you can install node on your production environment to benefit of the last official version of a npm package but any one can test or develop your project with no need to install node.

Note: the PHP fallback can be a simple php function, not necessarily a call to a class or a composer package.

### Settings

The *extra.npm* can be an object with npm required packages as key and versions for each of them as value (see https://docs.npmjs.com/misc/semver for version definition). You can also set it as an array of package names, it's the same as specify all packages dependancies with ```"*"``` version. Else if you need only one package and don't care about the version, just pass it as a string:

Array configuration:
```json
"extra": {
    "npm": [
        "hamljs",
        "kraken-js"
    ]
},
```

String configuration:
```json
"extra": {
    "npm": "express"
},
```
