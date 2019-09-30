<?php

/** @noinspection PhpUnused */

namespace RabbitMQ\Management;

use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use RabbitMQ\Management\Entity\Binding;
use RabbitMQ\Management\Entity\Channel;
use RabbitMQ\Management\Entity\Connection;
use RabbitMQ\Management\Entity\Exchange;
use RabbitMQ\Management\Entity\Queue;
use RabbitMQ\Management\Entity\EntityInterface;
use RabbitMQ\Management\Exception\InvalidArgumentException;
use React\HttpClient\Client;
use React\Promise\Deferred;
use React\HttpClient\Response;
use function React\Partial\bind;

class AsyncAPIClient
{
    /**
     * @var Client
     */
    private $client;
    private $hydrator;
    private $options;

    public static function factory($loop, array $options = array())
    {
        $defaultOptions = array(
            'port' => 15672,
            'user' => 'guest',
            'password' => 'guest',
            'scheme' => 'http',
            'url' => '127.0.0.1',
        );

        $options = array_merge($defaultOptions, $options);

        $client = new Client($loop);

        return new self($client, $options);
    }

    public function __construct(Client $client, array $options)
    {
        $this->client = $client;
        $this->hydrator = new Hydrator();
        $this->options = $options;
    }

    public function listConnections()
    {
        return $this->executeRequest('GET', '/api/connections')
            ->then(bind(array($this, 'handleCollectionData'), 'Connection'));
    }

    public function getConnection($name)
    {
        return $this->executeRequest('GET', sprintf('/api/connections/%s', rawurlencode($name)))
            ->then(bind(array($this, 'handleEntityData'), new Connection()));
    }

    public function deleteConnection($name)
    {
        return $this->executeRequest('DELETE', sprintf('/api/connections/%s', rawurlencode($name)));
    }

    private function executeRequest($method, $uri, $body = null)
    {
        $deferred = new Deferred();
        $request = $this->client->request($method, $this->buildUrl($uri), array('Content-Length' => strlen($body), 'Authorization' => 'Basic ' . base64_encode($this->options['user'] . ':' . $this->options['password']), 'Content-Type' => 'application/json'));

        $request->on('error', function (Exception $error) use ($uri, $deferred) {
            $deferred->reject(sprintf('Error while doing the request on %s : %s', $uri, $error->getMessage()));
        });

        $request->on('response', function (Response $response) use ($deferred) {
            if ($response->getCode() < 200 || $response->getCode() >= 400) {
                $deferred->reject(sprintf('The response is not as expected (status code %s, message is %s)', $response->getCode(), $response->getReasonPhrase()));
            }

            $response->on('error', function (Exception $error) use ($deferred) {
                $deferred->reject($error->getMessage());
            });

            $data = (object)array('data' => '');

            $response->on('data', function ($chunk) use ($data) {
                $data->data .= $chunk;
            });

            $response->on('end', function () use ($deferred, $data) {
                $deferred->resolve($data->data);
            });
        });

        $request->end($body);

        return $deferred->promise();
    }

    public function listChannels()
    {
        return $this->executeRequest('GET', '/api/channels')
            ->then(bind(array($this, 'handleCollectionData'), 'Channel'));
    }

    public function getChannel($name, Channel $channel = null)
    {
        return $this->executeRequest('GET', sprintf('/api/channels/%s', rawurlencode($name)))
            ->then(bind(array($this, 'handleEntityData'), $channel ?: new Channel()));
    }

    public function listExchanges($vhost = null)
    {
        if (null !== $vhost) {
            $uri = sprintf('/api/exchanges/%s', rawurlencode($vhost));
        } else {
            $uri = '/api/exchanges';
        }

        return $this->executeRequest('GET', $uri)
            ->then(bind(array($this, 'handleCollectionData'), 'Exchange'));
    }

    public function getExchange($vhost, $name, Exchange $exchange = null)
    {
        if (!$vhost) {
            throw new InvalidArgumentException('Queue requires a vhost');
        }

        if (!$name) {
            throw new InvalidArgumentException('Queue requires a name');
        }

        return $this->executeRequest('GET', sprintf('/api/exchanges/%s/%s', rawurlencode($vhost), rawurlencode($name)))
            ->then(bind(array($this, 'handleEntityData'), $exchange ?: new Exchange()));
    }

    public function deleteExchange($vhost, $name)
    {
        if (!$vhost) {
            throw new InvalidArgumentException('Queue requires a vhost');
        }

        if (!$name) {
            throw new InvalidArgumentException('Queue requires a name');
        }

        return $this->executeRequest('DELETE', sprintf('/api/exchanges/%s/%s', rawurlencode($vhost), rawurlencode($name)));
    }

    public function refreshExchange(Exchange $exchange)
    {
        return $this->getExchange($exchange->vhost, $exchange->name, $exchange);
    }

    public function addExchange(Exchange $exchange)
    {
        if (!$exchange->vhost) {
            throw new InvalidArgumentException('Exchange requires a vhost');
        }

        if (!$exchange->name) {
            throw new InvalidArgumentException('Exchange requires a name');
        }

        return $this->executeRequest('PUT', sprintf('/api/exchanges/%s/%s', rawurlencode($exchange->vhost), rawurlencode($exchange->name)), $exchange->toJson())
            ->then(bind(array($this, 'getExchange'), $exchange->vhost, $exchange->name, $exchange));
    }

    public function listQueues($vhost = null)
    {
        if (null !== $vhost) {
            $uri = sprintf('/api/queues/%s', rawurlencode($vhost));
        } else {
            $uri = '/api/queues';
        }

        return $this->executeRequest('GET', $uri)
            ->then(bind(array($this, 'handleCollectionData'), 'Queue'));
    }

    public function getQueue($vhost, $name, Queue $queue = null)
    {
        return $this->executeRequest('GET', sprintf('/api/queues/%s/%s', rawurlencode($vhost), rawurlencode($name)))
            ->then(bind(array($this, 'handleEntityData'), $queue ?: new Queue()));
    }

    public function refreshQueue(Queue $queue)
    {
        return $this->getQueue($queue->vhost, $queue->name, $queue);
    }

    public function addQueue(Queue $queue)
    {
        if (!$queue->vhost) {
            throw new InvalidArgumentException('Queue requires a vhost');
        }

        if (!$queue->name) {
            throw new InvalidArgumentException('Queue requires a name');
        }

        return $this->executeRequest('PUT', sprintf('/api/queues/%s/%s', rawurlencode($queue->vhost), rawurlencode($queue->name)), $queue->toJson())
            ->then(bind(array($this, 'getQueue'), $queue->vhost, $queue->name, $queue));
    }

    public function deleteQueue($vhost, $name)
    {
        if (!$vhost) {
            throw new InvalidArgumentException('Queue requires a vhost');
        }

        if (!$name) {
            throw new InvalidArgumentException('Queue requires a name');
        }

        return $this->executeRequest('DELETE', sprintf('/api/queues/%s/%s', rawurlencode($vhost), rawurlencode($name)));
    }

    public function purgeQueue($vhost, $name)
    {
        if (!$vhost) {
            throw new InvalidArgumentException('Queue requires a vhost');
        }

        if (!$name) {
            throw new InvalidArgumentException('Queue requires a name');
        }

        return $this->executeRequest('DELETE', sprintf('/api/queues/%s/%s/contents', rawurlencode($vhost), rawurlencode($name)))
            ->then(bind(array($this, 'getQueue'), $vhost, $name, null));
    }

    public function listBindingsByQueue(Queue $queue)
    {
        $uri = sprintf('/api/queues/%s/%s/bindings', rawurlencode($queue->vhost), rawurlencode($queue->name));

        return $this->executeRequest('GET', $uri)
            ->then(bind(array($this, 'handleCollectionData'), 'Binding'));
    }

    public function listBindingsByExchangeAndQueue($vhost, $exchange, $queue)
    {
        $uri = sprintf('/api/bindings/%s/e/%s/q/%s', rawurlencode($vhost), rawurlencode($exchange), rawurlencode($queue));

        return $this->executeRequest('GET', $uri)
            ->then(bind(array($this, 'handleCollectionData'), 'Binding'));
    }

    public function addBinding(Binding $binding)
    {
        $uri = sprintf('/api/bindings/%s/e/%s/q/%s', rawurlencode($binding->vhost), rawurlencode($binding->source), rawurlencode($binding->destination));

        return $this->executeRequest('POST', $uri, $binding->toJson());
    }

    public function deleteBinding(Binding $binding)
    {
        $uri = sprintf('/api/bindings/%s/e/%s/q/%s/%s', rawurlencode($binding->vhost), rawurlencode($binding->source), rawurlencode($binding->destination), rawurlencode($binding->properties_key));

        return $this->executeRequest('DELETE', $uri);
    }

    public function listBindings($vhost = null)
    {
        if (null !== $vhost) {
            $uri = sprintf('/api/bindings/%s', rawurlencode($vhost));
        } else {
            $uri = '/api/bindings';
        }

        return $this->executeRequest('GET', $uri)
            ->then(bind(array($this, 'handleCollectionData'), 'Binding'));
    }

    public function alivenessTest($vhost)
    {
        $that = $this;
        return $this->executeRequest('GET', sprintf('/api/aliveness-test/%s', rawurlencode($vhost)))
            ->then(function ($data) use ($that, $vhost) {
                $data = json_decode($data, true);

                if (!isset($data['status']) || $data['status'] !== 'ok') {
                    return false;
                }

                return $that->deleteQueue($vhost, 'aliveness-test')
                    ->then(function () {
                        return true;
                    }, function () {
                        return false;
                    });
            });
    }

    public function handleEntityData(EntityInterface $entity, $rawData)
    {
        return $this->hydrator->hydrate($entity, json_decode($rawData, true));
    }

    public function handleCollectionData($targetEntity, $rawData)
    {
        $data = json_decode($rawData, true);
        $collection = new ArrayCollection();
        $targetEntity = 'RabbitMQ\\Management\\Entity\\' . $targetEntity;

        foreach ($data as $entityData) {
            $entity = new $targetEntity();
            $this->hydrator->hydrate($entity, $entityData);
            $collection->add($entity);
        }

        return $collection;
    }

    private function buildUrl($base)
    {
        return $this->options['scheme'] . '://' . $this->options['url'] . ':' . $this->options['port'] . $base;
    }
}