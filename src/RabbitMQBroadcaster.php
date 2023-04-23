<?php

namespace Behzadsh\RabbitMQBroadcaster;

use JsonException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class RabbitMQBroadcaster extends Broadcaster
{
    protected AMQPChannel $rmqChannel;

    public function __construct(
        protected AMQPStreamConnection $connection,
        protected array $exchangeConfigs,
        protected string $defaultExchangeType = 'fanout'
    ) {
        $this->rmqChannel = $connection->channel();
    }

    /**
     * Broadcast the given event.
     *
     * @param string[] $channels
     * @param string   $event
     * @param array    $payload
     *
     * @return void
     *
     * @throws BroadcastException
     */
    public function broadcast(array $channels, $event, array $payload = []): void
    {
        foreach ($channels as $exchange) {
            $this->rmqChannel->exchange_declare(
                $exchange,
                $this->getExchangeType($exchange),
                $this->isPassive($exchange),
                $this->isDurable($exchange),
                $this->isAutoDelete($exchange),
                $this->isInternal($exchange),
            );

            try {
                $message = new AMQPMessage(json_encode($payload, JSON_THROW_ON_ERROR));
            } catch (JsonException $e) {
                throw new BroadcastException($e->getMessage());
            }

            $this->rmqChannel->basic_publish($message, $exchange, $event);
        }
    }

    /**
     * Authenticate the incoming request for a given channel. Since this broadcaster is not used for
     * client side application it always throws Access Denied exception.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return void
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function auth($request): void
    {
        throw new AccessDeniedHttpException();
    }

    /**
     * Return the valid authentication response. Since this broadcaster is not used for client side application it
     * always returns a false value.
     *
     * @param \Illuminate\Http\Request $request
     * @param mixed                    $result
     *
     * @return false
     */
    public function validAuthenticationResponse($request, $result): false
    {
        return false;
    }

    private function getExchangeType(string $exchange): string
    {
        return $this->exchangeTypes[$exchange]['type'] ?? $this->defaultExchangeType;
    }

    private function isPassive(string $exchange): bool
    {
        return isset($this->exchangeConfigs[$exchange]['durable']) &&
               filter_var($this->exchangeConfigs[$exchange]['durable'], FILTER_VALIDATE_BOOL);
    }

    private function isDurable(string $exchange): bool
    {
        return isset($this->exchangeConfigs[$exchange]['durable']) &&
               filter_var($this->exchangeConfigs[$exchange]['durable'], FILTER_VALIDATE_BOOL);
    }

    private function isAutoDelete(string $exchange): bool
    {
        return isset($this->exchangeConfigs[$exchange]['auto_delete']) ?
            filter_var($this->exchangeConfigs[$exchange]['auto_delete'], FILTER_VALIDATE_BOOL) :
            true;
    }

    private function isInternal(string $exchange): bool
    {
        return isset($this->exchangeConfigs[$exchange]['internal']) &&
               filter_var($this->exchangeConfigs[$exchange]['internal'], FILTER_VALIDATE_BOOL);
    }
}
