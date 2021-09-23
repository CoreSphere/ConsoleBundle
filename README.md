ConsoleBundle
=============

TODO: Update
[![Build Status](https://img.shields.io/travis/CoreSphere/ConsoleBundle.svg?style=flat-square)](https://travis-ci.org/CoreSphere/ConsoleBundle)
[![Quality Score](https://img.shields.io/scrutinizer/g/CoreSphere/ConsoleBundle.svg?style=flat-square)](https://scrutinizer-ci.com/g/CoreSphere/ConsoleBundle)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/CoreSphere/ConsoleBundle.svg?style=flat-square)](https://scrutinizer-ci.com/g/CoreSphere/ConsoleBundle)


This bundle allows you accessing the Symfony console via your browser.

Features
--------

 * Colored output
 * Autocompletion for command names
 * Local command history (localStorage)
 * ```cache:clear``` works

Installation
------------

1. Install the latest version via composer:

    ```sh
    composer require coresphere/console-bundle --dev
    ```

2. If you don't use [Symfony Flex](https://symfony.com/doc/current/setup/flex.html), you must enable the bundle manually in the application:

     ```php
   // config/bundles.php
   // in older Symfony apps, enable the bundle in app/AppKernel.php
   return [
   // ...
    CoreSphere\ConsoleBundle\CoreSphereConsoleBundle::class => ['dev' => true],
   ];
    ```

3. Add the bundle's route to your config/routing_dev.yml

    ```yaml
    # config/routing_dev.yml
	
    coresphere_console:
        resource: .
        type: extra
    ```

Tips
----

 * Type ```.clear``` to clear the console window

Preview
-------

<img src="https://static.laszlokorte.de/github/coresphere_console.png" width="900" alt="Screenshot" />
