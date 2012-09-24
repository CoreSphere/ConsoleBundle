ConsoleBundle
=============

This bundle allows you accessing the symfony2 console via your browser.

Features
--------

 * Colored output
 * Autocompletion for command names
 * Local command history (localStorage)
 * cache:clear works


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

 5. Add the stylesheets block to your template file

        <!-- /app/Resources/views/base.html.twig -->

        {% block stylesheets %}{% endblock %}

 6. Add the javascripts block to your template file

        <!-- /app/Resources/views/base.html.twig -->

        {% block javascripts %}{% endblock %}

 8. run the assets:install command to install the css and js files

        ./app/console assets:install web

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
 * AsseticBundle

Compatibility
-------------

Tested with:

 * Chrome
 * Firefox 4
 * Opera 11
 * Safari 5

Todo
----

 * Write Javascript tests
 * Add console as "pop up" to web developer toolbar
 * Figure out how to allow interactive mode (possible? extreme hacky?)