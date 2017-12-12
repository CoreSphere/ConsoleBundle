<?php declare(strict_types=1);

namespace CoreSphere\ConsoleBundle\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector as DataCollectorBase;

final class DataCollector extends DataCollectorBase
{
    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'coresphere_console';
    }

    public function reset()
    {
    }
}
