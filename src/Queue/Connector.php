<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/10/26
 * Time: 10:13
 */

namespace HughCube\Laravel\AliFC\Queue;

use HughCube\Laravel\AliFC\Manager as Fc;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Connectors\ConnectorInterface;
use JetBrains\PhpStorm\Pure;

class Connector implements ConnectorInterface
{
    /**
     * fc connections.
     * @var Fc
     */
    protected Fc $fc;

    /**
     * Create a new connector instance.
     * @param  Fc  $fc
     */
    public function __construct(Fc $fc)
    {
        $this->fc = $fc;
    }

    /**
     * Establish a queue connection.
     * @param  array  $config
     * @return QueueContract
     */
    #[Pure]
    public function connect(array $config): QueueContract
    {
        return new Queue(
            $this->fc,
            ($config['client'] ?? null),
            $config['service'],
            $config['function'],
            ($config['qualifier'] ?? null),
            (isset($config['after_commit']) && $config['after_commit']),
        );
    }
}
