# RabbitMQ Event Broadcaster for Laravel

The main focus of Laravel's event broadcasting module is to enable communication with the client-side of the application.
However, it can also be useful for asynchronous communication and message passing between services, especially in a
microservices architecture.

This package provides an easy way to broadcast Laravel events to a RabbitMQ server, enabling you to send messages
asynchronously and facilitating communication between services in a microservices architecture.

## Installation

To install the package, run the following command:

```
composer require behzadsh/rabbitmq-broadcaster
```

After installing the package, append the broadcaster config as shown in the snippet below to your `broadcasting.php`
config file:
```php
<?php

return [

    // ...

    'connections' => [

        // other connections config
        
        'rabbitmq' => [
            'driver' => 'rabbitmq',
            'connection' => [
                'host' => env('RABBITMQ_HOST', 'localhost'),
                'port' => env('RABBITMQ_PORT', 5672),
                'user' => env('RABBITMQ_USER', 'guest'),
                'password' => env('RABBITMQ_PASSWORD', 'guest'),
            ],
            'default_exchange_type' => 'fanout', // It can be `fanout` or `topic`
            'exchange_configs' => [ // optional
                'exchange1' => [
                    'type' => 'fanout',
                    'passive' => false,
                    'durable' => true,
                    'auto_delete' => false,
                    'internal' => false,
                ],
                'exchange2' => [
                    'type' => 'topic',
                    'passive' => false,
                    'durable' => false,
                    'auto_delete' => true,
                    'internal' => false,
                ],
            ], 
        ],

    ],

];
```

Then, append the package service provider at the end of the `providers` list in your `app.php` config file:

```php
<?php

return [

    // ...
    
    'providers' => [
        // Other service providers...
        Behzadsh\RabbitMQBroadcaster\RabbitMQBroadcasterServiceProvider::class,
    ]
    
    // ...
    
];
```

## Usage

To use the RabbitMQ event broadcaster, your desired event class must implement the
`Illuminate\Contracts\Broadcasting\ShouldBroadcast` interface, which requires you to implement the `broadcastOn` method.
The `broadcastOn` method should return a channel or an array of channels that the event should broadcast on.

Since this package is primarily intended for communicating between internal backend services, channels are not public,
and access to the channels from client-side applications is not allowed. Therefore, it's recommended that you avoid using
the `PrivateChannel`, `PresenceChannel`, and `EncryptedPrivateChannel` and use a simple `Channel` instead.
```php
<?php

namespace App\Events;
 
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
 
class UserRegistered implements ShouldBroadcast
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        public int $userId,
        public string $name,
        public string $email,
    ) {}
 
    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('user.events'),
        ];
    }
}
```

It is recommended to implement the `broadcastWith` method if you construct your event with an Eloquent Model. The
`broadcastWith` method should return an array that can be serialized into JSON.

```php
<?php

namespace App\Events;
 
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
 
class ServerCreated implements ShouldBroadcast
{
    use SerializesModels;
 
    /**
     * Create a new event instance.
     */
    public function __construct(
        public User $user,
    ) {}
 
    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('user.events'),
        ];
    }
    
    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'userId' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
        ];
    }
}
```
