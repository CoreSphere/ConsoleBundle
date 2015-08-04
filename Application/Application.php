<?php

/*
 * This file is part of the CoreSphereConsoleBundle.
 *
 * (c) Laszlo Korte <me@laszlokorte.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CoreSphere\ConsoleBundle\Application;

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application as BaseApplication;

/**
 * Class Application
 *
 * @package CoreSphere\ConsoleBundle\Application
 */
class Application extends BaseApplication
{
    const FILTER_WHITELIST = 'whitelist';
    const FILTER_BLACKLIST = 'blacklist';

    /**
     * @var array
     */
    private $config = array();

    /**
     * Constructor.
     *
     * @param KernelInterface $kernel
     * @param array $config
     */
    public function __construct(KernelInterface $kernel, $config = array())
    {
        parent::__construct($kernel);

        $this->config = $config;

        foreach ($kernel->getBundles() as $bundle) {
            $bundle->registerCommands($this);
        }

        // Reflection Property
        $propertyReflection = new \ReflectionProperty('\\Symfony\\Bundle\\FrameworkBundle\\Console\\Application', 'commandsRegistered');
        $propertyReflection->setAccessible(true);
        $propertyReflection->setValue($this, true);
        
        if (isset($config['filtering'])) {
            $filterOptions = (count($config['filtering']['whitelist']) > 0 ? self::FILTER_WHITELIST : self::FILTER_BLACKLIST);
            $this->filter($config['filtering'][$filterOptions], $filterOptions);
        }
    }

    /**
     * Get Configs
     *
     * @return array
     */
    public function getConfigs()
    {
        return $this->config;
    }

    /**
     * Filter
     *
     * @param array $commandList
     * @param string $filterOption
     */
    public function filter($commandList = array(), $filterOption = self::FILTER_WHITELIST)
    {
        // Sets
        $allCommands = $this->all();
        $availableCommands = array();

        // Check Filtering
        if (empty($commandList)) {
            return;
        }

        $filterOption = ($filterOption == self::FILTER_WHITELIST ? true : false);
        foreach ($allCommands as $key => $command) {
            if (preg_match('/' . implode('|', $commandList) . '/', $key) == $filterOption) {
                $availableCommands[$key] = $command;
            }
        }

        // Reflection Property
        $propertyReflection = new \ReflectionProperty('\\Symfony\\Component\\Console\\Application', 'commands');
        $propertyReflection->setAccessible(true);
        $propertyReflection->setValue($this, $availableCommands);
    }
}
