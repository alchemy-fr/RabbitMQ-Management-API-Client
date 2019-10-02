<?php

/** @noinspection MissingReturnTypeInspection */
/** @noinspection MethodShouldBeFinalInspection */
/** @noinspection DuplicatedCode */
/** @noinspection PhpUnhandledExceptionInspection */

namespace RabbitMQ\Tests\Management;

use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use RabbitMQ\Management\APIClient;
use RabbitMQ\Management\AsyncAPIClient;
use RabbitMQ\Management\Entity\Binding;
use RabbitMQ\Management\Entity\Exchange;
use RabbitMQ\Management\Entity\Queue;
use RabbitMQ\Management\Exception\EntityNotFoundException;
use RabbitMQ\Management\HttpClient;
use React\EventLoop\Factory;
use seregazhuk\React\PromiseTesting\TestCase;

class AsyncAPIClientTest extends TestCase
{
    const EXCHANGE_TEST_NAME = 'phrasea.baloo';
    const QUEUE_TEST_NAME = 'phrasea.queue.baloo';
    const VIRTUAL_HOST = '/';
    const NONEXISTENT_VIRTUAL_HOST = '/non-existent';

    /**
     * @var AsyncAPIClient
     */
    protected $object;
    protected $syncClient;
    protected $syncClientClient;
    protected $loop;

    /**
     * @var AMQPStreamConnection
     */
    protected $conn = null;

    /**
     * @var AMQPChannel
     */
    protected $channel = null;

    protected function setUp(): void
    {
        $this->loop = Factory::create();
        $this->object = AsyncAPIClient::factory($this->loop, array('host' => '127.0.0.1'));

        $this->syncClientClient = HttpClient::factory(array('host' => 'localhost'));
        $this->syncClient = new APIClient($this->syncClientClient);

        /* might be left over from previous failed test */
        $this->cleanupTestObjects();
    }


    public function tearDown(): void
    {
        $this->cleanupTestObjects();

        try {
            if ($this->channel != null) {
                $this->channel->close();
            }
            if ($this->conn != null) {
                $this->conn->close();
            }
        } catch (Exception $e) {
            /* don't care */
        }
    }

    private function cleanupTestObjects(): void
    {
        /* do not combine into one try/catch block because we want both to be tried even if first fails */
        try {
            $this->syncClient->deleteQueue('/', self::QUEUE_TEST_NAME);
        } catch (EntityNotFoundException $e) {
            /* might not exist, don't care if fails */
        }
        try {
            $this->syncClient->deleteExchange('/', self::EXCHANGE_TEST_NAME);
        } catch (EntityNotFoundException $e) {
            /* might not exist, don't care if fails */
        }
    }

    private function needConnection(): void
    {
        $this->conn = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest', '/');
        $this->channel = $this->conn->channel();

        /* attempting to see own connection just made, management api may delay showing it, so wait before testing */
        sleep(5);

    }

    public function testListConnections()
    {
        $this->needConnection();

        $connections = $this->waitForPromiseToFulfill($this->object->listConnections());

        $this->assertNonEmptyArrayCollection($connections);
        foreach ($connections as $connection) {
            $this->assertInstanceOf('RabbitMQ\Management\Entity\Connection', $connection);
        }

    }


    public function testGetConnection()
    {
        $this->needConnection();

        $connections = $this->syncClient->listConnections()->toArray();
        $expectedConnection = array_pop($connections);

        $connection = $this->waitForPromiseToFulfill($this->object->getConnection($expectedConnection->name));

        $this->assertInstanceOf('RabbitMQ\Management\Entity\Connection', $connection);
        $this->assertEquals($expectedConnection->name, $connection->name);
    }

    public function testDeleteConnection()
    {
        $this->needConnection();

        $connections = $this->syncClient->listConnections()->toArray();
        $expectedConnection = array_pop($connections);

        $this->waitForPromiseToFulfill($this->object->deleteConnection($expectedConnection->name));

        /* make sure we don't try to tear down */
        $this->channel = null;
        $this->conn = null;

        /* wait for info to update on server */
        sleep(10);

        /* its either going to return the connection in closed state */
        try {
            $expectedConnection = $this->syncClient->getConnection($expectedConnection->name);
            if ($expectedConnection->state == "closed") {
                $success = true;
            }
        } catch (EntityNotFoundException $e) {
            $this->assertContains("Failed to find connection", $e->getMessage());
            $success = true;
        }
        $this->assertEquals(true, $success);

    }

    public function testListChannels()
    {
        $this->needConnection();

        $channels = $this->waitForPromiseToFulfill($this->object->listChannels());

        $this->assertNonEmptyArrayCollection($channels);
        foreach ($channels as $channel) {
            $this->assertInstanceOf('RabbitMQ\Management\Entity\Channel', $channel);
        }
    }

    public function testGetChannel()
    {
        $this->needConnection();

        $channels = $this->syncClient->listChannels()->toArray();
        $this->assertNotEmpty($channels);
        $expectedChannel = array_pop($channels);

        $channel = $this->waitForPromiseToFulfill($this->object->getChannel($expectedChannel->name));
        $this->assertInstanceOf('RabbitMQ\Management\Entity\Channel', $channel);
        $this->assertEquals($expectedChannel->name, $channel->name);

    }

    public function testListExchanges()
    {
        $exchanges = $this->waitForPromiseToFulfill($this->object->listExchanges());
        $this->assertNonEmptyArrayCollection($exchanges);
        foreach ($exchanges as $exchange) {
            $this->assertInstanceOf('RabbitMQ\Management\Entity\Exchange', $exchange);
        }

    }

    public function testListExchangesWithVhost()
    {
        $exchanges = $this->waitForPromiseToFulfill($this->object->listExchanges(self::VIRTUAL_HOST));
        $this->assertNonEmptyArrayCollection($exchanges);
        foreach ($exchanges as $exchange) {
            $this->assertInstanceOf('RabbitMQ\Management\Entity\Exchange', $exchange);
            $this->assertEquals(self::VIRTUAL_HOST, $exchange->vhost);
        }
    }

    public function testListExchangeFailed()
    {
        $this->assertPromiseRejects($this->object->listExchanges(self::NONEXISTENT_VIRTUAL_HOST));
    }

    public function testGetExchange()
    {
        $exchanges = $this->syncClient->listExchanges()->toArray();
        $expectedExchange = array_pop($exchanges);

        $exchange = $this->waitForPromiseToFulfill($this->object->getExchange($expectedExchange->vhost, $expectedExchange->name));
        $this->assertInstanceOf('RabbitMQ\Management\Entity\Exchange', $exchange);
        $this->assertEquals($expectedExchange->name, $exchange->name);
    }

    public function testGetExchangeFailed()
    {
        $this->assertPromiseRejects($this->object->getExchange(self::NONEXISTENT_VIRTUAL_HOST, self::EXCHANGE_TEST_NAME));
    }

    public function testDeleteExchange()
    {
        $exchange = new Exchange();
        $exchange->name = self::EXCHANGE_TEST_NAME;
        $exchange->vhost = '/';

        $this->syncClient->addExchange($exchange);
        $this->syncClient->deleteExchange('/', self::EXCHANGE_TEST_NAME);


        $this->assertPromiseRejects($this->object->getExchange(self::VIRTUAL_HOST, self::EXCHANGE_TEST_NAME));
    }

    public function testDeleteExchangeFailed()
    {
        $this->assertPromiseRejects($this->object->deleteExchange(self::NONEXISTENT_VIRTUAL_HOST, self::EXCHANGE_TEST_NAME));
    }

    public function testAddExchange()
    {
        $exchange = new Exchange();

        $exchange->vhost = '/';
        $exchange->name = self::EXCHANGE_TEST_NAME;
        $exchange->durable = true;

        $resultExchange = $this->waitForPromiseToFulfill($this->object->addExchange($exchange));
        $this->assertEquals($resultExchange->name, $exchange->name);
    }

    public function testAddExchangeFailed()
    {
        $exchange = new Exchange();

        $exchange->vhost = self::NONEXISTENT_VIRTUAL_HOST;
        $exchange->type = 'fanout';
        $exchange->name = self::EXCHANGE_TEST_NAME;
        $exchange->durable = true;

        $this->assertPromiseRejects($this->object->addExchange($exchange));
    }

    public function testAddExchangeThatAlreadyExists()
    {
        $exchange = new Exchange();
        $exchange->vhost = '/';
        $exchange->name = self::EXCHANGE_TEST_NAME;

        $this->syncClient->addExchange($exchange);

        $exchange->type = 'durable';

        $this->assertPromiseRejects($this->object->addExchange($exchange));
    }

    public function testListQueues()
    {
        $this->createQueue();

        $queues = $this->waitForPromiseToFulfill($this->object->listQueues());
        $this->assertNonEmptyArrayCollection($queues);
        foreach ($queues as $queue) {
            $this->assertInstanceOf('RabbitMQ\Management\Entity\Queue', $queue);
        }
    }


    public function testListQueuesWithVhost()
    {
        $this->createQueue();

        $queues = $this->waitForPromiseToFulfill($this->object->listQueues(self::VIRTUAL_HOST));
        $this->assertNonEmptyArrayCollection($queues);
        foreach ($queues as $queue) {
            $this->assertInstanceOf('RabbitMQ\Management\Entity\Queue', $queue);
        }
    }

    public function testListQueueFailed()
    {
        $this->assertPromiseRejects($this->object->listQueues(self::NONEXISTENT_VIRTUAL_HOST));
    }

    public function testGetQueue()
    {
        $this->createQueue();

        $resultQueue = $this->waitForPromiseToFulfill($this->object->getQueue(self::VIRTUAL_HOST, self::QUEUE_TEST_NAME));
        $this->assertInstanceOf('RabbitMQ\Management\Entity\Queue', $resultQueue);
        $this->assertEquals(self::VIRTUAL_HOST, $resultQueue->vhost);
        $this->assertEquals(self::QUEUE_TEST_NAME, $resultQueue->name);
    }

    public function testGetQueueFailed()
    {
        $this->assertPromiseRejects($this->object->getQueue(self::NONEXISTENT_VIRTUAL_HOST, self::QUEUE_TEST_NAME));
    }

    public function testAddQueue()
    {
        $queue = new Queue();
        $queue->vhost = '/';
        $queue->name = self::QUEUE_TEST_NAME;

        $returnedQueue = $this->waitForPromiseToFulfill($this->object->addQueue($queue));
        $this->assertInstanceOf('RabbitMQ\\Management\\Entity\\Queue', $returnedQueue);
        $this->assertEquals($returnedQueue->name, $queue->name);

        $this->syncClient->deleteQueue('/', self::QUEUE_TEST_NAME);
    }

    public function testAddQueueFailed()
    {
        $queue = new Queue();
        $queue->vhost = self::NONEXISTENT_VIRTUAL_HOST;
        $queue->name = self::QUEUE_TEST_NAME;

        $this->assertPromiseRejects($this->object->addQueue($queue));
    }

    public function testDeleteQueue()
    {
        $queue = $this->createQueue();

        $this->waitForPromiseToFulfill($this->object->deleteQueue($queue->vhost, $queue->name));
        $this->assertPromiseRejects($this->object->getQueue($queue->vhost, $queue->name));
    }

    public function testDeleteQueueFailed()
    {
        $this->assertPromiseRejects($this->object->deleteQueue(self::NONEXISTENT_VIRTUAL_HOST, self::QUEUE_TEST_NAME));
    }

    public function testAddQueueThatAlreadyExists()
    {
        $queue = $this->createQueue();
        $queue->durable = true;

        $this->assertPromiseRejects($this->object->addQueue($queue));
    }


    public function testPurgeQueueFailed()
    {
        $this->assertPromiseRejects($this->object->purgeQueue(self::NONEXISTENT_VIRTUAL_HOST, self::QUEUE_TEST_NAME));
    }


    public function testListBindingsByQueue()
    {
        $this->createQueue();

        foreach ($this->syncClient->listQueues() as $queue) {
            $bindings = $this->waitForPromiseToFulfill($this->object->listBindingsByQueue($queue));
            foreach ($bindings as $binding) {
                $this->assertEquals('/', $binding->vhost);
                $this->assertEquals($queue->name, $binding->destination);
            }
        }
    }


    public function testListBindingsByQueueFailed()
    {
        $queue = new Queue();
        $queue->name = 'nonexistent queue';
        $queue->vhost = self::NONEXISTENT_VIRTUAL_HOST;

        $bindings = $this->waitForPromiseToFulfill($this->object->listBindingsByQueue($queue));
        $count = count($bindings);
        $this->assertEquals(0, $count);
    }


    public function testListBindingsByExchangeAndQueue()
    {
        $this->createBinding();

        foreach ($this->syncClient->listQueues() as $queue) {
            foreach ($this->syncClient->listExchanges() as $exchange) {
                if ($exchange->name == '') {
                    continue;
                }

                $bindings = $this->waitForPromiseToFulfill($this->object->listBindingsByExchangeAndQueue(self::VIRTUAL_HOST, $exchange->name, $queue->name));
                foreach ($bindings as $binding) {
                    $this->assertEquals('/', $binding->vhost);
                    $this->assertEquals($queue->name, $binding->destination);
                    $this->assertEquals($exchange->name, $binding->source);
                }
            }
        }
    }


    public function testListBindingsByExchangeAndQueueFailed()
    {
        $bindings = $this->waitForPromiseToFulfill($this->object->listBindingsByExchangeAndQueue(self::NONEXISTENT_VIRTUAL_HOST, self::EXCHANGE_TEST_NAME, self::QUEUE_TEST_NAME));
        $this->assertCount(0, $bindings);
    }


    public function testAddBinding()
    {
        $queue = $this->createQueue();

        $exchange = new Exchange();
        $exchange->name = self::EXCHANGE_TEST_NAME;
        $exchange->vhost = '/';

        $this->syncClient->addExchange($exchange);

        $binding = new Binding();
        $binding->vhost = '/';
        $binding->source = $exchange->name;
        $binding->destination = $queue->name;
        $binding->destination_type = 'direct';
        $binding->routing_key = 'rounting.key';
        $binding->arguments = array('bim' => 'boom');

        $this->waitForPromiseToFulfill($this->object->addBinding($binding));
        $bindings = $this->waitForPromiseToFulfill($this->object->listBindingsByExchangeAndQueue('/', self::EXCHANGE_TEST_NAME, self::QUEUE_TEST_NAME));
        foreach ($bindings as $binding) {
            $this->assertTrue($binding->routing_key === 'rounting.key' && $binding->arguments === array('bim' => 'boom'));
        }
        $this->waitForPromiseToFulfill($this->object->deleteBinding($binding));

    }


    public function testAddBindingFailed()
    {
        $binding = new Binding();
        $binding->vhost = '/';
        $binding->source = self::EXCHANGE_TEST_NAME;
        $binding->destination = self::QUEUE_TEST_NAME;

        $this->assertPromiseRejects($this->object->addBinding($binding));
    }


    public function testDeleteBinding()
    {
        $this->createBinding();
        $found = null;
        $bindings = $this->waitForPromiseToFulfill($this->object->listBindingsByExchangeAndQueue('/', self::EXCHANGE_TEST_NAME, self::QUEUE_TEST_NAME));
        foreach ($bindings as $binding) {
            if ($binding->routing_key == 'rounting.key') {
                $found = $binding;
                break;
            }
        }
        $this->assertNotNull($found);

        $this->waitForPromiseToFulfill($this->object->deleteBinding($found));
        $bindings = $this->waitForPromiseToFulfill($this->object->listBindingsByExchangeAndQueue('/', self::EXCHANGE_TEST_NAME, self::QUEUE_TEST_NAME));
        $found = null;

        foreach ($bindings as $binding) {
            if ($binding->routing_key == 'rounting.key') {
                $found = $binding;
                break;
            }
        }
        $this->assertNull($found);
    }


    public function testDeleteNonexistentBinding()
    {
        $binding = new Binding();
        $binding->vhost = self::VIRTUAL_HOST;
        $binding->source = self::EXCHANGE_TEST_NAME;
        $binding->destination = self::QUEUE_TEST_NAME;
        $binding->properties_key = 'bingo';

        $this->assertPromiseRejects($this->object->deleteBinding($binding));
    }


    public function testListBindings()
    {
        $this->createBinding();

        $bindings = $this->waitForPromiseToFulfill($this->object->listBindings());
        $this->assertNonEmptyArrayCollection($bindings);
        foreach ($bindings as $binding) {
            $this->assertInstanceOf('RabbitMQ\Management\Entity\Binding', $binding);
        }

    }


    public function testListBindingsWithVhost()
    {
        $this->createBinding();

        $bindings = $this->waitForPromiseToFulfill($this->object->listBindings(self::VIRTUAL_HOST));
        $this->assertNonEmptyArrayCollection($bindings);
        foreach ($bindings as $binding) {
            $this->assertInstanceOf('RabbitMQ\Management\Entity\Binding', $binding);
        }
    }


    public function testAlivenessTest()
    {
        $this->assertPromiseFulfills($this->object->alivenessTest(self::VIRTUAL_HOST));
    }


    public function testPurgeQueue()
    {
        $queue = $this->createQueue();

        $exchange = new Exchange();
        $exchange->name = self::EXCHANGE_TEST_NAME;
        $exchange->vhost = '/';

        $this->syncClient->addExchange($exchange);

        $binding = new Binding();
        $binding->vhost = '/';
        $binding->source = self::EXCHANGE_TEST_NAME;
        $binding->destination = self::QUEUE_TEST_NAME;
        $binding->routing_key = self::QUEUE_TEST_NAME;

        $this->syncClient->addBinding($binding);

        $message = json_encode(array(
            'properties' => array(),
            'routing_key' => self::QUEUE_TEST_NAME,
            'payload' => 'body',
            'payload_encoding' => 'string',
        ));
        $message = str_replace('[]', '{}', $message);

        $n = 12;
        while ($n > 0) {
            $this->syncClientClient->post('/api/exchanges/' . urlencode('/') . '/' . self::EXCHANGE_TEST_NAME . '/publish',
                ['headers' => ['content-type' => 'application/json'], 'body' => $message]);
            $n--;
        }

        sleep(10);
        $this->syncClient->refreshQueue($queue);
        $this->assertEquals(12, $queue->messages_ready);

        $this->waitForPromiseToFulfill($this->object->purgeQueue('/', self::QUEUE_TEST_NAME));
        sleep(15);
        $this->syncClient->refreshQueue($queue);
        $this->assertEquals(0, $queue->messages_ready);
    }


    private function createQueue()
    {
        $queue = new Queue();
        $queue->vhost = self::VIRTUAL_HOST;
        $queue->name = self::QUEUE_TEST_NAME;

        $this->syncClient->addQueue($queue);

        return $queue;
    }


    private function createBinding()
    {
        $this->createQueue();

        $exchange = new Exchange();
        $exchange->name = self::EXCHANGE_TEST_NAME;
        $exchange->vhost = '/';

        $this->syncClient->addExchange($exchange);

        $binding = new Binding();
        $binding->vhost = '/';
        $binding->source = self::EXCHANGE_TEST_NAME;
        $binding->destination = self::QUEUE_TEST_NAME;
        $binding->routing_key = 'rounting.key';

        $this->syncClient->addBinding($binding);

        return $binding;
    }


    public function assertNonEmptyArrayCollection($collection)
    {
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $collection);
        $this->assertGreaterThan(0, count($collection), 'Collection is not empty');
    }
}
