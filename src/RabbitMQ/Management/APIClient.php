<?php
/** @noinspection PhpUnhandledExceptionInspection */

namespace RabbitMQ\Management;

use Doctrine\Common\Collections\ArrayCollection;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use RabbitMQ\Management\Entity\Binding;
use RabbitMQ\Management\Entity\Channel;
use RabbitMQ\Management\Entity\Exchange;
use RabbitMQ\Management\Entity\Queue;
use RabbitMQ\Management\Entity\EntityInterface;
use RabbitMQ\Management\Exception\PreconditionFailedException;
use RabbitMQ\Management\Exception\InvalidArgumentException;
use RabbitMQ\Management\Exception\EntityNotFoundException;


class APIClient
{
    private $client;
    private $hydrator;

    public static function factory(array $options = array())
    {
        return new self(HttpClient::factory($options));
    }

    public function __construct(HttpClient $client)
    {
        $this->client = $client;
        $this->hydrator = new Hydrator();
    }

    public function listConnections()
    {
        return $this->retrieveCollection('/api/connections', 'RabbitMQ\Management\Entity\Connection');
    }


    public function getConnection($name)
    {
        $uri = sprintf('/api/connections/%s', rawurlencode($name));

        try {
            return $this->retrieveEntity($uri, 'RabbitMQ\Management\Entity\Connection');
        } catch (EntityNotFoundException $e) {
            throw new EntityNotFoundException('Failed to find connection: ' . $name, $e->getCode(), $e);
        }
    }


    public function deleteConnection($name)
    {
        try {
            $this->client->delete(sprintf('/api/connections/%s', rawurlencode($name)));
        } catch (ClientException $e) {
            if ($e->getCode() == 404) {
                throw new EntityNotFoundException('Connection not found: ' . $name);
            }
            throw $e;
        }

        return $this;
    }

    public function listChannels()
    {
        return $this->retrieveCollection('/api/channels', 'RabbitMQ\Management\Entity\Channel');
    }

    public function getChannel($name, Channel $channel = null)
    {
        $uri = sprintf('/api/channels/%s', rawurlencode($name));

        try {
            return $this->retrieveEntity($uri, 'RabbitMQ\Management\Entity\Channel', $channel);
        } catch (EntityNotFoundException $e) {
            throw new EntityNotFoundException('Failed to find channel: ' . $name);
        }
    }

    public function listExchanges($vhost = null)
    {
        if (null !== $vhost) {
            $uri = sprintf('/api/exchanges/%s', rawurlencode($vhost));
        } else {
            $uri = '/api/exchanges';
        }

        return $this->retrieveCollection($uri, 'RabbitMQ\Management\Entity\Exchange');
    }

    public function getExchange($vhost, $name, Exchange $exchange = null)
    {
        if (!$vhost) {
            throw new InvalidArgumentException('Queue requires a vhost');
        }

        if (!$name) {
            throw new InvalidArgumentException('Queue requires a name');
        }

        $uri = sprintf('/api/exchanges/%s/%s', rawurlencode($vhost), rawurlencode($name));

        try {
            return $this->retrieveEntity($uri, 'RabbitMQ\Management\Entity\Exchange', $exchange);
        } catch (EntityNotFoundException $e) {
            throw new EntityNotFoundException('Failed to find exchange: ' . $name);
        }
    }

    public function deleteExchange($vhost, $name)
    {
        if (!$vhost) {
            throw new InvalidArgumentException('Queue requires a vhost');
        }

        if (!$name) {
            throw new InvalidArgumentException('Queue requires a name');
        }

        $uri = sprintf('/api/exchanges/%s/%s', rawurlencode($vhost), rawurlencode($name));

        try {
            $this->client->delete($uri);
        } catch (ClientException $e) {
            if ($e->getCode() == 404) {
                throw new EntityNotFoundException('Unable to find exchange for deletion: ' . $vhost . '/' . $name);
            }
            throw $e;
        }


        return $this;
    }

    public function refreshExchange(Exchange $exchange)
    {
        $uri = sprintf('/api/exchanges/%s/%s', rawurlencode($exchange->vhost), rawurlencode($exchange->name));

        try {
            return $this->retrieveEntity($uri, 'RabbitMQ\Management\Entity\Exchange', $exchange);
        } catch (EntityNotFoundException $e) {
            throw new EntityNotFoundException('Failed ot find exchange: ' . $exchange->vhost . '/' . $exchange->name);
        }
    }

    public function addExchange(Exchange $exchange)
    {
        if (!$exchange->vhost) {
            throw new InvalidArgumentException('Exchange requires a vhost');
        }

        if (!$exchange->name) {
            throw new InvalidArgumentException('Exchange requires a name');
        }

        $uri = sprintf('/api/exchanges/%s/%s', rawurlencode($exchange->vhost), rawurlencode($exchange->name));
        $json = $exchange->toJson();
        try {
            $this->client->put($uri, ['headers' => ['Content-Type' => 'application/json'], 'body' => $json]);
        } catch (ClientException $e) {
            if ($data = json_decode($e->getResponse()->getBody(), true)) {
                if (isset($data['reason']) && strpos($data['reason'], 'inequivalent arg') === 0) {
                    throw new PreconditionFailedException('Exchange already exists with different properties', $e->getCode(), $e);
                }
            }
            throw $e;
        }

        return $this->getExchange($exchange->vhost, $exchange->name, $exchange);
    }

    public function listQueues($vhost = null)
    {
        if (null !== $vhost) {
            $uri = sprintf('/api/queues/%s', rawurlencode($vhost));
        } else {
            $uri = '/api/queues';
        }
        try {
            return $this->retrieveCollection($uri, 'RabbitMQ\Management\Entity\Queue');
        } catch (ClientException $e) {
            if ($e->getCode() == 404) {
                throw new EntityNotFoundException("Failed to find virtual host to list queues: " . $vhost);
            }
            throw $e;
        }
    }

    public function getQueue($vhost, $name, Queue $queue = null)
    {
        $uri = sprintf('/api/queues/%s/%s', rawurlencode($vhost), rawurlencode($name));

        try {
            return $this->retrieveEntity($uri, 'RabbitMQ\Management\Entity\Queue', $queue);
        } catch (EntityNotFoundException $e) {
            throw new EntityNotFoundException('Failed to find queue: ' . $vhost . '/' . $name);
        }
    }

    public function refreshQueue(Queue $queue)
    {
        $uri = sprintf('/api/queues/%s/%s', rawurlencode($queue->vhost), rawurlencode($queue->name));

        try {
            return $this->retrieveEntity($uri, 'RabbitMQ\Management\Entity\Queue', $queue);
        } catch (EntityNotFoundException $e) {
            throw new EntityNotFoundException('Failed to find queue:' . $queue->vhost . '/' . $queue->name);
        }
    }

    public function addQueue(Queue $queue)
    {
        if (!$queue->vhost) {
            throw new InvalidArgumentException('Queue requires a vhost');
        }

        if (!$queue->name) {
            throw new InvalidArgumentException('Queue requires a name');
        }

        $uri = sprintf('/api/queues/%s/%s', rawurlencode($queue->vhost), rawurlencode($queue->name));

        try {
            $this->client->put($uri, ['headers' => ['Content-type' => 'application/json'], 'body' => $queue->toJson()]);
        } catch (ClientException $e) {
            if ($e->getCode() == 404) {
                throw new EntityNotFoundException("Failed to find virtual host to add queue: " . $queue->vhost);
            }
            if ($data = json_decode($e->getResponse()->getBody(), true)) {
                if (isset($data['reason']) && strpos($data['reason'], 'inequivalent arg') === 0) {
                    throw new PreconditionFailedException('Queue already exists with different properties', $e->getCode(), $e);
                }
            }
            throw $e;
        }

        return $this->getQueue($queue->vhost, $queue->name, $queue);
    }

    public function deleteQueue($vhost, $name)
    {
        if (!$vhost) {
            throw new InvalidArgumentException('Queue requires a vhost');
        }

        if (!$name) {
            throw new InvalidArgumentException('Queue requires a name');
        }

        try {
            $this->client->delete(
                sprintf('/api/queues/%s/%s', rawurlencode($vhost), rawurlencode($name))
            );
        } catch (ClientException $e) {
            if ($e->getCode() == 404) {
                throw new  EntityNotFoundException('Unable to find queue to delete: ' . $vhost . '/' . $name);
            }
            throw $e;
        }

        return $this;
    }

    public function purgeQueue($vhost, $name)
    {
        if (!$vhost) {
            throw new InvalidArgumentException('Queue requires a vhost');
        }

        if (!$name) {
            throw new InvalidArgumentException('Queue requires a name');
        }

        try {
            $this->client->delete(
                sprintf('/api/queues/%s/%s/contents', rawurlencode($vhost), rawurlencode($name))
            );
        } catch (ClientException $e) {
            if ($e->getCode() == 404) {
                throw new EntityNotFoundException('Unable to find queue to purge: ' . $vhost . '/' . $name);
            }
            throw $e;
        }

        return $this;
    }

    public function listBindingsByQueue(Queue $queue)
    {
        $uri = sprintf('/api/queues/%s/%s/bindings', rawurlencode($queue->vhost), rawurlencode($queue->name));

        return $this->retrieveCollection($uri, 'RabbitMQ\Management\Entity\Binding');
    }

    public function listBindingsByExchangeAndQueue($vhost, $exchange, $queue)
    {
        $uri = sprintf('/api/bindings/%s/e/%s/q/%s', rawurlencode($vhost), rawurlencode($exchange), rawurlencode($queue));

        return $this->retrieveCollection($uri, 'RabbitMQ\Management\Entity\Binding');
    }

    public function addBinding(Binding $binding)
    {
        $uri = sprintf('/api/bindings/%s/e/%s/q/%s', rawurlencode($binding->vhost), rawurlencode($binding->source), rawurlencode($binding->destination));

        try {
            $this->client->post($uri, ['headers' => ['Content-type' => 'application/json'], 'body' => $binding->toJson()]);
        } catch (RequestException $e) {
            if ($e->getCode() == 404) {
                throw new EntityNotFoundException("Failed to find vhost for adding binding: " . $binding->vhost);
            }
            throw $e;

        }
        return $this;
    }

    public function deleteBinding($vhost, $exchange, $queue, Binding $binding)
    {
        $uri = sprintf('/api/bindings/%s/e/%s/q/%s/%s', rawurlencode($vhost), rawurlencode($exchange), rawurlencode($queue), rawurlencode($binding->properties_key));

        try {
            $this->client->delete($uri);
        } catch (ClientException $e) {
            if ($e->getCode() == 404) {
                throw new EntityNotFoundException('Unable to find binding to delete: ' . $vhost . '/' . $exchange . '/' . $queue . '/' . $binding->properties_key);
            }
            throw $e;
        }

        return $this;
    }

    public function listBindings($vhost = null)
    {
        if (null !== $vhost) {
            $uri = sprintf('/api/bindings/%s', rawurlencode($vhost));
        } else {
            $uri = '/api/bindings';
        }

        return $this->retrieveCollection($uri, 'RabbitMQ\Management\Entity\Binding');
    }

    public function alivenessTest($vhost)
    {
        try {
            $res = $this->client->get(sprintf('/api/aliveness-test/%s', rawurlencode($vhost)))->getBody();
            $data = json_decode($res, true);

            if (!isset($data['status']) || $data['status'] !== 'ok') {
                return false;
            }

            $this->deleteQueue($vhost, 'aliveness-test');
        } catch (ClientException $e) {
            return false;
        }

        return true;
    }

    private function retrieveEntity($uri, $targetEntity, EntityInterface $entity = null)
    {
        try {
            //$res = $this->client->get($uri)->getBody();
            //$res = $this->client->get($uri)->getBody();
            $res = $this->client->get($uri)->getBody();
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                throw new EntityNotFoundException('Entity not found', $e->getCode(), $e);
            }
            throw $e;
        }

        if (null === $entity) {
            $entity = new $targetEntity();
        }

        return $this->hydrator->hydrate($entity, json_decode($res, true));
    }

    private function retrieveCollection($uri, $targetEntity)
    {
        $res = $this->client->get($uri)->getBody();

        $data = json_decode($res, true);

        $collection = new ArrayCollection();

        foreach ($data as $entityData) {
            $entity = new $targetEntity();
            $this->hydrator->hydrate($entity, $entityData);
            $collection->add($entity);
        }

        return $collection;
    }
}
