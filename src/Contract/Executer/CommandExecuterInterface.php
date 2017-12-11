<?php

/*
 * This file is part of the CoreSphereConsoleBundle.
 *
 * (c) Laszlo Korte <me@laszlokorte.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CoreSphere\ConsoleBundle\Contract\Executer;

/**
 * Takes a string to execute as console command.
 */
interface CommandExecuterInterface
{
    /**
     * @param string $commandString
     *
     * @return array
     */
    public function execute($commandString);
}
