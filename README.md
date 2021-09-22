ConsoleBundle
=============

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

0. Make sure you have php 7.4 or newer installed. Make sure your composer file does not override the required php version of your project with in older one via the `config.plattform.php` setting.

1. Install the latest version via composer:

	```sh
	composer require coresphere/console-bundle
	```

2. Register the bundle in you AppKernel in the development section

	 ```php
	// app/AppKernel.php
	public function registerBundles()
	{
		$bundles = [
	  		// other bundles here...
		];

		if (in_array($this->getEnvironment(), ['dev', 'test'])) {
			// ...
			$bundles[] = new CoreSphere\ConsoleBundle\CoreSphereConsoleBundle();
	 	}

		return $bundles;
	}
	```

3. Add the bundle's route to your app/config/routing_dev.yml

	```yaml
	# app/config/routing_dev.yml

	# ...
	_main:
		resource: routing.yml
	
	coresphere_console:
		resource: .
		type: extra
	```


4. run the assets:install command to install the css and js files

	```sh
	./app/console assets:install web
	```

Tips
----

 * Type ```.clear``` to clear the console window

Preview
-------

<img src="http://static.laszlokorte.de/github/coresphere_console.png" width="900" alt="Screenshot" />

Dependencies
------------

 * jQuery
 * Twig