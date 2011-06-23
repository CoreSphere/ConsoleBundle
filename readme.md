ConsoleBundle
=============

This bundle allows you accessing the symfony2 console via your browser.


Installation
------------

 1. Download the Bundle into vendors/bundles/CoreSphere/ConsoleBundle
 2. Add this line to your autoloader

    // app/autoload.php
    $loader->registerNamespaces(array(
        'CoreSphere'          => array(__DIR__ . '/../vendor/bundles'),
    ));
 3. Add the following route to your routing configuration

    #app/config/routing_dev.yml
    console:
        resource: "@CoreSphereConsoleBundle/Resources/config/routing.yml"

 4. Register the bundle in you AppKernel in the development section

    // app/ApplicationKernel.php
    public function registerBundles()
    {
        $bundles = array(
            // other bundles here...
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new CoreSphere\ConsoleBundle\CoreSphereConsoleBundle();
        }

        return $bundles;
    }

 5. run the assets:install command to install the css file

    ./app/console assets:install web

Dependencies
------------

 * jQuery
 * AsseticBundle