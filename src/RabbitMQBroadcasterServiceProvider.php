<?php

namespace Behzadsh\RabbitMQBroadcaster;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Container\Container;
use Illuminate\Broadcasting\BroadcastManager;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Contracts\Container\BindingResolutionException;

class RabbitMQBroadcasterServiceProvider extends ServiceProvider
{
    /**
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        $this->app->make(BroadcastManager::class)->extend(
            'rabbitmq',
            static function (Container $app, array $config): Broadcaster {
                $connection = $config['connection'];
                $amqpConnection = new AMQPStreamConnection(
                    $connection['host'], $connection['port'], $connection['user'], $connection['password']
                );

                return new RabbitMQBroadcaster(
                    $amqpConnection,
                    $config['exchange_configs'],
                    $config['default_exchange_type']
                );
            }
        );
    }
}
