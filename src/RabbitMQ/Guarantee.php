<?php

namespace RabbitMQ;

use RabbitMQ\Entity\EntityInterface;
use RabbitMQ\Entity\Binding;
use RabbitMQ\Entity\Exchange;
use RabbitMQ\Entity\Queue;
use RabbitMQ\Exception\EntityNotFoundException;

class Guarantee
{
    private $client;

    public function __construct(APIClient $client)
    {
        $this->client = $client;
    }

    public function ensureExchange(Exchange $exchange)
    {
        try {
            $expectedExchange = $this->client->getExchange($exchange->vhost, $exchange->name);

            if ($expectedExchange !== $exchange) {
                $this->client->deleteExchange($exchange->vhost, $exchange->name);
                $this->client->addExchange($exchange);
            } else {
                $this->client->refreshExchange($exchange);
            }
        } catch (EntityNotFoundException $e) {
            $this->client->addExchange($exchange);
        }
    }

    public function ensureQueue(Queue $queue)
    {
        try {
            $expectedQueue = $this->client->getQueue($queue->vhost, $queue->name);

            if ($expectedQueue !== $queue) {
                $this->client->deleteQueue($queue->vhost, $queue->name);
                $this->client->addQueue($queue);
            } else {
                $this->client->refreshQueue($queue);
            }
        } catch (EntityNotFoundException $e) {
            $this->client->addQueue($queue);
        }
    }

    public function ensureBinding($vhost, $exchange, $queue, $routing_key = '', array $arguments = array())
    {
        $bindings = $this->client->listBindingsByExchangeAndQueue($vhost, $exchange, $queue);
        $found = false;

        foreach ($bindings as $binding) {
            if ($routing_key === $binding->routing_key) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            $binding = $this->setProperties(new Binding(), array(
                'vhost'       => $vhost,
                'routing_key' => $routing_key,
                'arguments'   => $arguments,
                ));

            $this->client->addBinding($vhost, $exchange, $queue, $binding);
        }
    }

    private function setProperties(EntityInterface $entity, array $properties)
    {
        foreach ($properties as $key => $value) {
            if ($value !== $entity->{$key}) {
                $entity->{$key} = $value;
            }
        }

        return $entity;
    }
}
