<?php

namespace RabbitMQ;

use RabbitMQ\Entity\EntityInterface;
use RabbitMQ\Entity\Binding;
use RabbitMQ\Entity\Exchange;
use RabbitMQ\Entity\Queue;
use RabbitMQ\Exception\EntityNotFoundException;
use RabbitMQ\Exception\RuntimeException;

class Guarantee
{
    const PROBE_ABSENT = -1;
    const PROBE_MISCONFIGURED = 0;
    const PROBE_OK = 1;

    private $client;

    public function __construct(APIClient $client)
    {
        $this->client = $client;
    }

    public function probeExchange(Exchange $exchange)
    {
        try {
            if ($exchange !== $this->client->getExchange($exchange->vhost, $exchange->name)) {
                return self::PROBE_MISCONFIGURED;
            } else {
                return self::PROBE_OK;
            }
        } catch (EntityNotFoundException $e) {
            return self::PROBE_ABSENT;
        }
    }

    public function ensureExchange(Exchange $exchange)
    {
        switch ($this->probeExchange($exchange)) {
            case self::PROBE_ABSENT;
                $this->client->addExchange($exchange);
                break;
            case self::PROBE_MISCONFIGURED;
                $this->client->deleteExchange($exchange->vhost, $exchange->name);
                $this->client->addExchange($exchange);
                break;
            case self::PROBE_OK;
                $this->client->refreshExchange($exchange);
                break;
            default:
                throw new RuntimeException('Unable to probe exchange');
                break;
        }
    }

    public function probeQueue(Queue $queue)
    {
        try {
            if ($queue !== $this->client->getQueue($queue->vhost, $queue->name)) {
                return self::PROBE_MISCONFIGURED;
            } else {
                return self::PROBE_OK;
            }
        } catch (EntityNotFoundException $e) {
            return self::PROBE_ABSENT;
        }
    }

    public function ensureQueue(Queue $queue)
    {
        switch ($this->probeQueue($queue)) {
            case self::PROBE_ABSENT;
                $this->client->addQueue($queue);
                break;
            case self::PROBE_MISCONFIGURED;
                $this->client->deleteQueue($queue->vhost, $queue->name);
                $this->client->addQueue($queue);
                break;
            case self::PROBE_OK;
                $this->client->refreshQueue($queue);
                break;
            default:
                throw new RuntimeException('Unable to probe queue');
                break;
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
