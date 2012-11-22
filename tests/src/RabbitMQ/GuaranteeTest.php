<?php

namespace RabbitMQ;

use RabbitMQ\Entity\Binding;
use RabbitMQ\Entity\Exchange;
use RabbitMQ\Entity\Queue;

class GuaranteeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Guarantee
     */
    protected $object;

    /**
     *
     * @var APIClient
     */
    protected $client;

    protected function setUp()
    {
        $this->client = APIClient::factory(array('url' => 'localhost'));
        $this->object = new Guarantee($this->client);
    }

    /**
     * @covers RabbitMQ\Guarantee::ensureExchange
     */
    public function testEnsureExchange()
    {
        $exchange = new Exchange();
        $exchange->vhost = '/';
        $exchange->name = 'test.exchange';

        $this->object->ensureExchange($exchange);

        $this->assertInstanceOf('RabbitMQ\Entity\Exchange', $exchange);
        $this->assertFalse($exchange->durable);
        $this->assertFalse($exchange->auto_delete);
        $this->assertFalse($exchange->internal);

        $this->client->deleteExchange($exchange->vhost, $exchange->name);
    }

    /**
     * @covers RabbitMQ\Guarantee::ensureExchange
     */
    public function testEnsureExchangeWithOptions()
    {
        $exchange = new Exchange();
        $exchange->vhost = '/';
        $exchange->name = 'test.exchange';
        $exchange->durable = true;
        $exchange->auto_delete = true;
        $exchange->internal = true;
        $exchange->type = 'direct';

        $this->object->ensureExchange($exchange);

        $this->assertInstanceOf('RabbitMQ\Entity\Exchange', $exchange);
        $this->assertTrue($exchange->durable);
        $this->assertTrue($exchange->auto_delete);
        $this->assertTrue($exchange->internal);

        $this->client->deleteExchange($exchange->vhost, $exchange->name);
    }

    /**
     * @covers RabbitMQ\Guarantee::ensureExchange
     */
    public function testEnsureExchangeAlreadyExisting()
    {
        $expectedExchange = new Exchange();
        $expectedExchange->type = 'fanout';
        $expectedExchange->vhost = '/';
        $expectedExchange->name = 'test.exchange';

        $this->client->addExchange($expectedExchange);

        $exchange = new Exchange();
        $exchange->vhost = '/';
        $exchange->name = 'test.exchange';
        $exchange->type = 'fanout';

        $this->object->ensureExchange($exchange);
        $this->assertEquals($expectedExchange, $exchange);

        $this->client->deleteExchange($exchange->vhost, $exchange->name);
    }

    /**
     * @covers RabbitMQ\Guarantee::ensureExchange
     */
    public function testEnsureExchangeAlreadyExistingWithDifferentOptions()
    {
        $expectedExchange = new Exchange();
        $expectedExchange->type = 'fanout';
        $expectedExchange->vhost = '/';
        $expectedExchange->name = 'test.exchange';

        $this->client->addExchange($expectedExchange);

        $exchange = new Exchange();
        $exchange->vhost = '/';
        $exchange->name = 'test.exchange';
        $exchange->durable = true;
        $exchange->auto_delete = true;
        $exchange->internal = true;
        $exchange->type = 'fanout';

        $this->object->ensureExchange($exchange);
        $this->assertNotEquals($expectedExchange, $exchange);

        $this->assertTrue($exchange->durable);
        $this->assertTrue($exchange->auto_delete);
        $this->assertTrue($exchange->internal);

        $this->client->deleteExchange($expectedExchange->vhost, $expectedExchange->name);
    }

    /**
     * @covers RabbitMQ\Guarantee::ensureQueue
     */
    public function testEnsureQueue()
    {
        $queue = new Queue();
        $queue->vhost = '/';
        $queue->name = 'test.queue';

        $this->object->ensureQueue($queue);

        $this->assertInstanceOf('RabbitMQ\Entity\Queue', $queue);
        $this->assertFalse($queue->durable);
        $this->assertFalse($queue->auto_delete);

        $this->client->deleteQueue($queue->vhost, $queue->name);
    }

    /**
     * @covers RabbitMQ\Guarantee::ensureQueue
     */
    public function testEnsureQueueWithOptions()
    {
        $queue = new Queue();
        $queue->vhost = '/';
        $queue->name = 'test.queue';
        $queue->auto_delete = true;
        $queue->durable = true;

        $this->object->ensureQueue($queue);

        $this->assertInstanceOf('RabbitMQ\Entity\Queue', $queue);
        $this->assertTrue($queue->durable);
        $this->assertTrue($queue->auto_delete);

        $this->client->deleteQueue($queue->vhost, $queue->name);
    }

    /**
     * @covers RabbitMQ\Guarantee::ensureQueue
     */
    public function testEnsureQueueAlreadyExisting()
    {
        $expectedQueue = new Queue();
        $expectedQueue->name = 'test.queue';
        $expectedQueue->vhost = '/';

        $this->client->addQueue($expectedQueue);

        $queue = new Queue();
        $queue->vhost = '/';
        $queue->name = 'test.queue';

        $this->object->ensureQueue($queue);
        $this->assertEquals($expectedQueue, $queue);

        $this->client->deleteQueue($queue->vhost, $queue->name);
    }

    /**
     * @covers RabbitMQ\Guarantee::ensureQueue
     */
    public function testEnsureQueueAlreadyExistingWithDifferentOptions()
    {
        $expectedQueue = new Queue();
        $expectedQueue->name = 'test.queue';
        $expectedQueue->vhost = '/';

        $this->client->addQueue($expectedQueue);

        $queue = new Queue();
        $queue->vhost = '/';
        $queue->name = 'test.queue';
        $queue->auto_delete = true;
        $queue->durable = true;

        $this->object->ensureQueue($queue);
        $this->assertNotEquals($expectedQueue, $queue);

        $this->assertTrue($queue->durable);
        $this->assertTrue($queue->auto_delete);

        $this->client->deleteQueue($queue->vhost, $queue->name);
    }

    /**
     * @covers RabbitMQ\Guarantee::ensureBinding
     */
    public function testEnsureBinding()
    {
        $exchange = new Exchange();
        $exchange->type = 'fanout';
        $exchange->vhost = '/';
        $exchange->name = 'test.exchange';

        $queue = new Queue();
        $queue->name = 'test.queue';
        $queue->vhost = '/';

        $this->client->addExchange($exchange);
        $this->client->addQueue($queue);

        $this->object->ensureBinding('/', 'test.exchange', 'test.queue', 'special.routing.key');

        $found = false;

        foreach ($this->client->listBindingsByExchangeAndQueue('/', 'test.exchange', 'test.queue') as $binding) {
            if ('special.routing.key' === $binding->routing_key) {
                $found = true;
                break;
            }
        }

        $this->client->deleteExchange($exchange->vhost, $exchange->name);
        $this->client->deleteQueue($queue->vhost, $queue->name);

        if (!$found) {
            $this->fail('Unable to find back the binding');
        }
    }

    /**
     * @covers RabbitMQ\Guarantee::ensureBinding
     */
    public function testEnsureBindingAlreadyBound()
    {
        $exchange = new Exchange();
        $exchange->type = 'fanout';
        $exchange->vhost = '/';
        $exchange->name = 'test.exchange';

        $queue = new Queue();
        $queue->name = 'test.queue';
        $queue->vhost = '/';

        $this->client->addExchange($exchange);
        $this->client->addQueue($queue);

        $binding = new Binding();
        $binding->routing_key = 'special.routing.key';

        $this->client->addBinding('/', 'test.exchange', 'test.queue', $binding);

        $this->object->ensureBinding('/', 'test.exchange', 'test.queue', 'special.routing.key');

        $found = false;

        foreach ($this->client->listBindingsByExchangeAndQueue('/', 'test.exchange', 'test.queue') as $binding) {
            if ('special.routing.key' === $binding->routing_key) {
                $found = true;
                break;
            }
        }

        $this->client->deleteExchange($exchange->vhost, $exchange->name);
        $this->client->deleteQueue($queue->vhost, $queue->name);

        if (!$found) {
            $this->fail('Unable to find back the binding');
        }
    }
}
