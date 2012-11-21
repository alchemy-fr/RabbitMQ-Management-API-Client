<?php

namespace RabbitMQ;

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Channel\AMQPChannel;
use RabbitMQ\Entity\Exchange;
use RabbitMQ\Entity\Queue;
use RabbitMQ\Entity\Binding;
use RabbitMQ\Exception\EntityNotFoundException;
use RabbitMQ\Exception\PreconditionFailedException;

class APIClientTest extends \PHPUnit_Framework_TestCase
{
    const EXCHANGE_TEST_NAME = 'phrasea.baloo';
    const QUEUE_TEST_NAME = 'phrasea.queue.baloo';
    const VIRTUAL_HOST = '/';
    const NONEXISTENT_VIRTUAL_HOST = '/non-existent';

    /**
     * @var APIClient
     */
    protected $object;

    /**
     * @var AMQPConnection
     */
    protected $conn;
    protected $client;

    /**
     * @var AMQPChannel
     */
    protected $channel;

    protected function setUp()
    {
        $this->client = HttpClient::factory(array('url'         => 'localhost'));
        $this->object = new APIClient($this->client);

        $this->conn = new \PhpAmqpLib\Connection\AMQPConnection('localhost', 5672, 'guest', 'guest', '/');
        $this->channel = $this->conn->channel();
    }

    public function tearDown()
    {
        try {
            $this->object->deleteQueue('/', self::QUEUE_TEST_NAME);
        } catch (\Exception $e) {

        }
        try {
            $this->object->deleteExchange('/', self::EXCHANGE_TEST_NAME);
        } catch (\Exception $e) {

        }

        $this->channel->close();
        $this->conn->close();
    }

    public function testListConnections()
    {
        $connections = $this->object->listConnections();

        $this->assertNonEmptyArrayCollection($connections);

        foreach ($connections as $connection) {
            $this->assertInstanceOf('RabbitMQ\Entity\Connection', $connection);
        }
    }

    public function testGetConnection()
    {
        $connections = $this->object->listConnections()->toArray();
        $expectedConnection = array_pop($connections);

        $connection = $this->object->getConnection($expectedConnection->name);
        $this->assertInstanceOf('RabbitMQ\Entity\Connection', $connection);

        $this->assertEquals($expectedConnection, $connection);
    }

    public function testDeleteConnection()
    {
        $this->markTestSkipped('Not working ?!');

        $connections = $this->object->listConnections()->toArray();
        $quantity = count($connections);
        $expectedConnection = array_pop($connections);
        $this->object->deleteConnection($expectedConnection->name);
        $this->assertEquals($quantity - 1, count($this->object->listConnections()));
    }

    public function testListChannels()
    {
        $channels = $this->object->listChannels();

        $this->assertNonEmptyArrayCollection($channels);

        foreach ($channels as $channel) {
            $this->assertInstanceOf('RabbitMQ\Entity\Channel', $channel);
        }
    }

    public function testGetChannel()
    {
        $channels = $this->object->listChannels()->toArray();
        $expectedChannel = array_pop($channels);

        $channel = $this->object->getChannel($expectedChannel->name);
        $this->assertInstanceOf('RabbitMQ\Entity\Channel', $channel);

        $this->assertEquals($expectedChannel, $channel);
    }

    public function testListExchanges()
    {
        $exchanges = $this->object->listExchanges();

        $this->assertNonEmptyArrayCollection($exchanges);

        foreach ($exchanges as $exchange) {
            $this->assertInstanceOf('RabbitMQ\Entity\Exchange', $exchange);
        }
    }

    public function testListExchangesWithVhost()
    {
        $exchanges = $this->object->listExchanges(self::VIRTUAL_HOST);

        $this->assertNonEmptyArrayCollection($exchanges);

        foreach ($exchanges as $exchange) {
            $this->assertInstanceOf('RabbitMQ\Entity\Exchange', $exchange);
        }
    }

    /**
     * @expectedException RabbitMQ\Exception\RuntimeException
     */
    public function testListExchangeFailed()
    {
        $this->object->listExchanges(self::NONEXISTENT_VIRTUAL_HOST);
    }

    public function testGetExchange()
    {
        $exchanges = $this->object->listExchanges()->toArray();
        $expectedExchange = array_pop($exchanges);

        $exchange = $this->object->getExchange($expectedExchange->vhost, $expectedExchange->name);
        $this->assertInstanceOf('RabbitMQ\Entity\Exchange', $exchange);

        $this->assertEquals($expectedExchange, $exchange);
    }

    /**
     * @expectedException RabbitMQ\Exception\EntityNotFoundException
     */
    public function testGetExchangeFailed()
    {
        $this->object->getExchange(self::NONEXISTENT_VIRTUAL_HOST, self::EXCHANGE_TEST_NAME);
    }

    public function testDeleteExchange()
    {
        $exchange = new Exchange();
        $exchange->name = self::EXCHANGE_TEST_NAME;
        $exchange->vhost = '/';

        $this->object->addExchange($exchange);
        $this->object->deleteExchange('/', self::EXCHANGE_TEST_NAME);

        try {
            $this->object->getExchange($exchange->vhost, $exchange->name);
            $this->fail('Should raise an exception');
        } catch (EntityNotFoundException $e) {

        }
    }

    /**
     * @expectedException RabbitMQ\Exception\RuntimeException
     */
    public function testDeleteExchangeFailed()
    {
        $this->object->deleteExchange(self::NONEXISTENT_VIRTUAL_HOST, self::EXCHANGE_TEST_NAME);
    }

    public function testAddExchange()
    {
        $exchange = new Exchange();

        $exchange->vhost = '/';
        $exchange->type = 'fanout';
        $exchange->name = self::EXCHANGE_TEST_NAME;
        $exchange->durable = true;

        $this->object->addExchange($exchange);

        $foundExchange = $this->object->getExchange($exchange->vhost, $exchange->name);

        $this->assertEquals($exchange, $foundExchange);

        $this->object->deleteExchange('/', self::EXCHANGE_TEST_NAME);
    }

    /**
     * @expectedException RabbitMQ\Exception\RuntimeException
     */
    public function testAddExchangeFailed()
    {
        $exchange = new Exchange();

        $exchange->vhost = self::NONEXISTENT_VIRTUAL_HOST;
        $exchange->type = 'fanout';
        $exchange->name = self::EXCHANGE_TEST_NAME;
        $exchange->durable = true;

        $this->object->addExchange($exchange);
    }

    public function testAddExchangeThatAlreadyExists()
    {
        $exchange = new Exchange();
        $exchange->vhost = '/';
        $exchange->name = self::EXCHANGE_TEST_NAME;

        $this->object->addExchange($exchange);

        $exchange->durable = true;

        try {
            $this->object->addExchange($exchange);
            $this->fail('Should raise an exception');
        } catch (PreconditionFailedException $e) {

        }
    }

    public function testListQueues()
    {
        $queues = $this->object->listQueues();

        $this->assertNonEmptyArrayCollection($queues);

        foreach ($queues as $queue) {
            $this->assertInstanceOf('RabbitMQ\Entity\Queue', $queue);
        }
    }

    public function testListQueuesWithVhost()
    {
        $queues = $this->object->listQueues(self::VIRTUAL_HOST);

        $this->assertNonEmptyArrayCollection($queues);

        foreach ($queues as $queue) {
            $this->assertInstanceOf('RabbitMQ\Entity\Queue', $queue);
        }
    }

    /**
     * @expectedException RabbitMQ\Exception\RuntimeException
     */
    public function testListQueueFailed()
    {
        $this->object->listQueues(self::NONEXISTENT_VIRTUAL_HOST);
    }

    public function testGetQueue()
    {
        $queues = $this->object->listQueues()->toArray();
        $expectedQueue = array_pop($queues);

        $queue = $this->object->getQueue($expectedQueue->vhost, $expectedQueue->name);
        $this->assertInstanceOf('RabbitMQ\Entity\Queue', $queue);

        $this->assertEquals($expectedQueue, $queue);
    }

    /**
     * @expectedException RabbitMQ\Exception\EntityNotFoundException
     */
    public function testGetQueueFailed()
    {
        $this->object->getQueue(self::NONEXISTENT_VIRTUAL_HOST, self::QUEUE_TEST_NAME);
    }

    public function testAddQueue()
    {
        $queue = new Queue();
        $queue->vhost = '/';
        $queue->name = self::QUEUE_TEST_NAME;

        $this->object->addQueue($queue);

        $foundQueue = $this->object->getQueue($queue->vhost, $queue->name);

        $this->assertEquals($queue, $foundQueue);

        $this->object->deleteQueue('/', self::QUEUE_TEST_NAME);
    }

    /**
     * @expectedException RabbitMQ\Exception\RuntimeException
     */
    public function testAddQueueFailed()
    {
        $queue = new Queue();
        $queue->vhost = self::NONEXISTENT_VIRTUAL_HOST;
        $queue->name = self::QUEUE_TEST_NAME;

        $this->object->addQueue($queue);
    }

    public function testDeleteQueue()
    {
        $queue = new Queue();
        $queue->name = self::QUEUE_TEST_NAME;
        $queue->vhost = '/';

        $this->object->addQueue($queue);
        $this->object->deleteQueue('/', self::QUEUE_TEST_NAME);

        try {
            $this->object->getQueue($queue->vhost, $queue->name);
            $this->fail('Should raise an exception');
        } catch (EntityNotFoundException $e) {

        }
    }

    /**
     * @expectedException RabbitMQ\Exception\RuntimeException
     */
    public function testDeleteQueueFailed()
    {
        $this->object->deleteQueue(self::NONEXISTENT_VIRTUAL_HOST, self::QUEUE_TEST_NAME);
    }

    public function testAddQueueThatAlreadyExists()
    {
        $queue = new Queue();
        $queue->vhost = '/';
        $queue->name = self::QUEUE_TEST_NAME;

        $this->object->addQueue($queue);

        $queue->durable = true;

        try {
            $this->object->addQueue($queue);
            $this->fail('Should raise an exception');
        } catch (PreconditionFailedException $e) {

        }
    }

    /**
     * @expectedException RabbitMQ\Exception\RuntimeException
     */
    public function testPurgeQueueFailed()
    {
        $this->object->purgeQueue(self::NONEXISTENT_VIRTUAL_HOST, self::QUEUE_TEST_NAME);
    }

    public function testListBindingsByQueue()
    {
        foreach ($this->object->listQueues() as $queue) {
            foreach ($this->object->listBindingsByQueue($queue) as $binding) {
                /* @var $binding Binding */
                $this->assertEquals('/', $binding->vhost);
                $this->assertEquals($queue->name, $binding->destination);
            }
        }
    }

    /**
     * @expectedException RabbitMQ\Exception\RuntimeException
     */
    public function testListBindingsByQueueFailed()
    {
        $queue = new Queue();
        $queue->name = 'nonexistent queue';
        $queue->vhost = self::NONEXISTENT_VIRTUAL_HOST;

        $this->object->listBindingsByQueue($queue);
    }

    public function testListBindingsByExchangeAndQueue()
    {
        foreach ($this->object->listQueues() as $queue) {
            foreach ($this->object->listExchanges() as $exchange) {
                if ($exchange->name == '') {
                    continue;
                }
                foreach ($this->object->listBindingsByExchangeAndQueue('/', $exchange->name, $queue->name) as $binding) {
                    /* @var $binding Binding */
                    $this->assertEquals('/', $binding->vhost);
                    $this->assertEquals($queue->name, $binding->destination);
                    $this->assertEquals($exchange->name, $binding->source);
                }
            }
        }
    }

    /**
     * @expectedException RabbitMQ\Exception\RuntimeException
     */
    public function testListBindingsByExchangeAndQueueFailed()
    {
        $this->object->listBindingsByExchangeAndQueue(self::NONEXISTENT_VIRTUAL_HOST, self::EXCHANGE_TEST_NAME, self::QUEUE_TEST_NAME);
    }

    public function testAddBinding()
    {
        $queue = new Queue();
        $queue->name = self::QUEUE_TEST_NAME;
        $queue->vhost = '/';
        $queue->durable = true;

        $this->object->addQueue($queue);

        $exchange = new Exchange();
        $exchange->name = self::EXCHANGE_TEST_NAME;
        $exchange->vhost = '/';

        $this->object->addExchange($exchange);

        $binding = new Binding();
        $binding->destination_type = 'direct';
        $binding->destination = self::QUEUE_TEST_NAME;
        $binding->routing_key = 'rounting.key';
        $binding->vhost = '/';

        $this->object->addBinding('/', self::EXCHANGE_TEST_NAME, self::QUEUE_TEST_NAME, $binding);

        $found = false;

        foreach ($this->object->listBindingsByExchangeAndQueue('/', self::EXCHANGE_TEST_NAME, self::QUEUE_TEST_NAME) as $binding) {
            if ($binding->routing_key == 'rounting.key') {
                $found = $binding;
            }
        }

        if (!$found) {
            $this->fail('unable to find back the binding');
        }

        $this->object->deleteBinding('/', self::EXCHANGE_TEST_NAME, self::QUEUE_TEST_NAME, $found);
    }

    /**
     * @expectedException RabbitMQ\Exception\RuntimeException
     */
    public function testAddBindingFailed()
    {
        $binding = new Binding();
        $binding->vhost = '/';

        $this->object->addBinding('/', self::EXCHANGE_TEST_NAME, self::QUEUE_TEST_NAME, $binding);
    }

    public function testDeleteBinding()
    {
        $queue = new Queue();
        $queue->name = self::QUEUE_TEST_NAME;
        $queue->vhost = '/';
        $queue->durable = true;

        $this->object->addQueue($queue);

        $exchange = new Exchange();
        $exchange->name = self::EXCHANGE_TEST_NAME;
        $exchange->vhost = '/';

        $this->object->addExchange($exchange);

        $binding = new Binding();
        $binding->destination_type = 'direct';
        $binding->destination = self::QUEUE_TEST_NAME;
        $binding->routing_key = 'rounting.key';
        $binding->vhost = '/';

        $this->object->addBinding('/', self::EXCHANGE_TEST_NAME, self::QUEUE_TEST_NAME, $binding);

        $found = false;

        foreach ($this->object->listBindingsByExchangeAndQueue('/', self::EXCHANGE_TEST_NAME, self::QUEUE_TEST_NAME) as $binding) {
            if ($binding->routing_key == 'rounting.key') {
                $found = $binding;
            }
        }

        $this->object->deleteBinding('/', self::EXCHANGE_TEST_NAME, self::QUEUE_TEST_NAME, $found);

        $found = false;

        foreach ($this->object->listBindingsByExchangeAndQueue('/', self::EXCHANGE_TEST_NAME, self::QUEUE_TEST_NAME) as $binding) {
            if ($binding->routing_key == 'rounting.key') {
                $found = true;
            }
        }

        if ($found) {
            $this->fail('unable to find delete the binding');
        }
    }

    /**
     * @expectedException RabbitMQ\Exception\RuntimeException
     */
    public function testDeleteNonexistentBinding()
    {
        $binding = new Binding();
        $binding->properties_key = 'bingo';

        $this->object->deleteBinding('/', self::EXCHANGE_TEST_NAME, self::QUEUE_TEST_NAME, $binding);
    }

    public function testListBindings()
    {
        $bindings = $this->object->listBindings();

        $this->assertNonEmptyArrayCollection($bindings);

        foreach ($bindings as $binding) {
            $this->assertInstanceOf('RabbitMQ\Entity\Binding', $binding);
        }
    }

    public function testListBindingsWithVhost()
    {
        $bindings = $this->object->listBindings(self::VIRTUAL_HOST);

        $this->assertNonEmptyArrayCollection($bindings);

        foreach ($bindings as $binding) {
            $this->assertInstanceOf('RabbitMQ\Entity\Binding', $binding);
        }
    }

    public function testAlivenessTest()
    {
        $this->assertTrue($this->object->alivenessTest(self::VIRTUAL_HOST));
    }

    public function testAlivenessTestFailed()
    {
        $this->assertFalse($this->object->alivenessTest(self::NONEXISTENT_VIRTUAL_HOST));
    }

    public function testPurgeQueue()
    {
        $queue = new Queue();
        $queue->name = self::QUEUE_TEST_NAME;
        $queue->vhost = '/';
        $queue->durable = true;

        $this->object->addQueue($queue);

        $exchange = new Exchange();
        $exchange->name = self::EXCHANGE_TEST_NAME;
        $exchange->vhost = '/';

        $this->object->addExchange($exchange);

        $binding = new Binding();
        $binding->destination_type = 'direct';
        $binding->destination = self::QUEUE_TEST_NAME;
        $binding->routing_key = self::QUEUE_TEST_NAME;
        $binding->vhost = '/';

        $this->object->addBinding('/', self::EXCHANGE_TEST_NAME, self::QUEUE_TEST_NAME, $binding);

        $message = json_encode(array(
            'properties' => array(),
            'routing_key'      => self::QUEUE_TEST_NAME,
            'payload'          => 'body',
            'payload_encoding' => 'string',
            ));

        $n = 12;
        while ($n > 0) {
            $this->client->post('/api/exchanges/' . urlencode('/') . '/' . self::EXCHANGE_TEST_NAME . '/publish', array('content-type' => 'application/json'), $message)->send();
            $n--;
        }

        usleep(1000000);
        $this->object->refreshQueue($queue);
        $this->assertEquals(12, $queue->messages_ready);

        $this->object->purgeQueue('/', self::QUEUE_TEST_NAME);

        usleep(2000000);
        $this->object->refreshQueue($queue);
        $this->assertEquals(0, $queue->messages_ready);
    }

    private function assertNonEmptyArrayCollection($collection)
    {
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $collection);
        $this->assertGreaterThan(0, count($collection), 'Collection is not empty');
    }
}
