<?php

namespace RabbitMQ;

use RabbitMQ\HttpClient;
use Doctrine\Common\Collections\ArrayCollection;
use Guzzle\Http\Exception\RequestException;
use RabbitMQ\Entity\Binding;
use RabbitMQ\Entity\Channel;
use RabbitMQ\Entity\Exchange;
use RabbitMQ\Entity\Queue;
use RabbitMQ\Entity\EntityInterface;
use RabbitMQ\Exception\PreconditionFailedException;
use RabbitMQ\Exception\RuntimeException;
use RabbitMQ\Exception\InvalidArgumentException;
use RabbitMQ\Exception\EntityNotFoundException;

class APIClient
{
    /**
     * @var RabbitMQCient
     */
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
        return $this->retrieveCollection('/api/connections', 'RabbitMQ\Entity\Connection');
    }

    public function getConnection($name)
    {
        $uri = sprintf('/api/connections/%s', urlencode($name));

        return $this->retrieveEntity($uri, 'RabbitMQ\Entity\Connection');
    }

    public function deleteConnection($name)
    {
        try {
            $this->client->delete(sprintf('/api/connections/%s', urlencode($name)))->send();
        } catch (RequestException $e) {
            throw new RuntimeException('Failed to delete connection');
        }

        return $this;
    }

    public function listChannels()
    {
        return $this->retrieveCollection('/api/channels', 'RabbitMQ\Entity\Channel');
    }

    public function getChannel($name, Channel $channel = null)
    {
        $uri = sprintf('/api/channels/%s', urlencode($name));

        return $this->retrieveEntity($uri, 'RabbitMQ\Entity\Channel', $channel);
    }

    public function listExchanges($vhost = null)
    {
        if (null !== $vhost) {
            $uri = sprintf('/api/exchanges/%s', urlencode($vhost));
        } else {
            $uri = '/api/exchanges';
        }

        return $this->retrieveCollection($uri, 'RabbitMQ\Entity\Exchange');
    }

    public function getExchange($vhost, $name, Exchange $exchange = null)
    {
        if (!$vhost) {
            throw new InvalidArgumentException('Queue requires a vhost');
        }

        if (!$name) {
            throw new InvalidArgumentException('Queue requires a name');
        }

        $uri = sprintf('/api/exchanges/%s/%s', urlencode($vhost), urlencode($name));

        return $this->retrieveEntity($uri, 'RabbitMQ\Entity\Exchange', $exchange);
    }

    public function deleteExchange($vhost, $name)
    {
        if (!$vhost) {
            throw new InvalidArgumentException('Queue requires a vhost');
        }

        if (!$name) {
            throw new InvalidArgumentException('Queue requires a name');
        }

        $uri = sprintf('/api/exchanges/%s/%s', urlencode($vhost), urlencode($name));

        try {
            $this->client->delete($uri)->send();
        } catch (RequestException $e) {
            throw new RuntimeException('Unable to delete exchange');
        }

        return $this;
    }

    public function addExchange(Exchange $exchange)
    {
        if (!$exchange->vhost) {
            throw new InvalidArgumentException('Exchange requires a vhost');
        }

        if (!$exchange->name) {
            throw new InvalidArgumentException('Exchange requires a name');
        }

        $uri = sprintf('/api/exchanges/%s/%s', urlencode($exchange->vhost), urlencode($exchange->name));

        try {
            $response = $this->client->put($uri, array('Content-Type' => 'application/json'), $exchange->toJson())->send();
        } catch (RequestException $e) {
            if ($data = json_decode($e->getResponse()->getBody(true), true)) {
                if (isset($data['reason']) && strpos($data['reason'], '406 PRECONDITION_FAILED') === 0) {
                    throw new PreconditionFailedException('Exchange already exists with different properties');
                }
            }
            throw new RuntimeException('Unable to put the exchange');
        }

        return $this->getExchange($exchange->vhost, $exchange->name, $exchange);
    }

    public function listQueues($vhost = null)
    {
        if (null !== $vhost) {
            $uri = sprintf('/api/queues/%s', urlencode($vhost));
        } else {
            $uri = '/api/queues';
        }

        return $this->retrieveCollection($uri, 'RabbitMQ\Entity\Queue');
    }

    public function getQueue($vhost, $name, Queue $queue = null)
    {
        $uri = sprintf('/api/queues/%s/%s', urlencode($vhost), urlencode($name));

        return $this->retrieveEntity($uri, 'RabbitMQ\Entity\Queue', $queue);
    }

    public function refreshQueue(Queue $queue)
    {
        $uri = sprintf('/api/queues/%s/%s', urlencode($queue->vhost), urlencode($queue->name));

        return $this->retrieveEntity($uri, 'RabbitMQ\Entity\Queue', $queue);
    }

    public function addQueue(Queue $queue)
    {
        if (!$queue->vhost) {
            throw new InvalidArgumentException('Queue requires a vhost');
        }

        if (!$queue->name) {
            throw new InvalidArgumentException('Queue requires a name');
        }

        $uri = sprintf('/api/queues/%s/%s', urlencode($queue->vhost), urlencode($queue->name));

        try {
            $this->client->put($uri, array('Content-type' => 'application/json'), $queue->toJson())->send();
        } catch (RequestException $e) {
            if ($data = json_decode($e->getResponse()->getBody(true), true)) {
                if (isset($data['reason']) && strpos($data['reason'], '406 PRECONDITION_FAILED') === 0) {
                    throw new PreconditionFailedException('Queue already exists with different properties');
                }
            }
            throw new RuntimeException('Unable to put the exchange');
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
                sprintf('/api/queues/%s/%s', urlencode($vhost), urlencode($name))
            )->send();
        } catch (RequestException $e) {
            throw new RuntimeException('Unable to delete queue');
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
                sprintf('/api/queues/%s/%s/contents', urlencode($vhost), urlencode($name))
            )->send();
        } catch (RequestException $e) {
            throw new RuntimeException('Unable to purge queue');
        }

        return $this;
    }

    public function listBindingsByQueue(Queue $queue)
    {
        $uri = sprintf('/api/queues/%s/%s/bindings', urlencode($queue->vhost), urlencode($queue->name));

        return $this->retrieveCollection($uri, 'RabbitMQ\Entity\Binding');
    }

    public function listBindingsByExchangeAndQueue($vhost, $exchange, $queue)
    {
        $uri = sprintf('/api/bindings/%s/e/%s/q/%s', urlencode($vhost), urlencode($exchange), urlencode($queue));

        return $this->retrieveCollection($uri, 'RabbitMQ\Entity\Binding');
    }

    public function addBinding($vhost, $exchange, $queue, Binding $binding)
    {
        $uri = sprintf('/api/bindings/%s/e/%s/q/%s', urlencode($vhost), urlencode($exchange), urlencode($queue));

        try {
            $this->client->post($uri, array('Content-type' => 'application/json'), $binding->toJson())->send();
        } catch (RequestException $e) {
            throw new RuntimeException('Unable to add binding');
        }

        return $this;
    }

    public function deleteBinding($vhost, $exchange, $queue, Binding $binding)
    {
        $uri = sprintf('/api/bindings/%s/e/%s/q/%s/%s', urlencode($vhost), urlencode($exchange), urlencode($queue), urlencode($binding->properties_key));

        try {
            $this->client->delete($uri)->send();
        } catch (RequestException $e) {
            throw new RuntimeException('Unable to delete binding');
        }

        return $this;
    }

    public function listBindings($vhost = null)
    {
        if (null !== $vhost) {
            $uri = sprintf('/api/bindings/%s', urlencode($vhost));
        } else {
            $uri = '/api/bindings';
        }

        return $this->retrieveCollection($uri, 'RabbitMQ\Entity\Binding');
    }

    public function alivenessTest($vhost)
    {
        try {
            $res = $this->client->get(sprintf('/api/aliveness-test/%s', urlencode($vhost)))->send()->getBody(true);
            $data = json_decode($res, true);

            if (!isset($data['status']) || $data['status'] !== 'ok') {
                return false;
            }

            $this->deleteQueue($vhost, 'aliveness-test');
        } catch (RequestException $e) {
            return false;
        }

        return true;
    }

    private function retrieveEntity($uri, $targetEntity, EntityInterface $entity = null)
    {
        try {
            $res = $this->client->get($uri)->send()->getBody(true);
        } catch (\Guzzle\Http\Exception\RequestException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                throw new EntityNotFoundException('Entity not found');
            }

            throw new RuntimeException('Error while getting the entity', null, $e);
        }

        if (null === $entity) {
            $entity = new $targetEntity();
        }

        return $this->hydrator->hydrate($entity, json_decode($res, true));
    }

    private function retrieveCollection($uri, $targetEntity)
    {
        try {
            $res = $this->client->get($uri)->send()->getBody(true);
        } catch (RequestException $e) {
            throw new RuntimeException(sprintf('Unable to fetch data for %s', $targetEntity));
        }

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