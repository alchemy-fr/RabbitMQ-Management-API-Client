<?php /** @noinspection MethodShouldBeFinalInspection */

/** @noinspection PhpUnhandledExceptionInspection */

namespace RabbitMQ\Tests\Management;

use Exception;
use GuzzleHttp\Exception\ClientException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PHPUnit\Framework\TestCase;
use RabbitMQ\Management\APIClient;
use RabbitMQ\Management\Entity\Binding;
use RabbitMQ\Management\Entity\Exchange;
use RabbitMQ\Management\Entity\Queue;
use RabbitMQ\Management\Exception\EntityNotFoundException;
use RabbitMQ\Management\Exception\PreconditionFailedException;
use RabbitMQ\Management\HttpClient;


class APIClientTest extends TestCase
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
     * @var AMQPStreamConnection
     */
    protected $conn = null;
    protected $client;

    /**
     * @var AMQPChannel
     */
    protected $channel = null;

    protected function setUp(): void
    {
        $this->client = HttpClient::factory(array('host' => 'localhost'));
        $this->object = new APIClient($this->client);

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

    private function needConnection(): void
    {
        $this->conn = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest', '/');
        $this->channel = $this->conn->channel();

        /* attempting to see own connection just made, management api may delay showing it, so wait before testing */
        sleep(5);

    }

    private function cleanupTestObjects(): void
    {
        /* do not combine into one try/catch block because we want both to be tried even if first fails */
        try {
            $this->object->deleteQueue('/', self::QUEUE_TEST_NAME);
        } catch (EntityNotFoundException $e) {
            /* might not exist, don't care if fails */
        }
        try {
            $this->object->deleteExchange('/', self::EXCHANGE_TEST_NAME);
        } catch (EntityNotFoundException $e) {
            /* might not exist, don't care if fails */
        }
    }

    public function testListConnections(): void
    {
        $this->needConnection();
        $connections = $this->object->listConnections();

        $this->assertNonEmptyArrayCollection($connections);

        foreach ($connections as $connection) {
            $this->assertInstanceOf('RabbitMQ\Management\Entity\Connection', $connection);
        }
    }

    public function testGetConnection(): void
    {
        $this->needConnection();
        $connections = $this->object->listConnections()->toArray();
        $expectedConnection = array_pop($connections);

        $connection = $this->object->getConnection($expectedConnection->name);
        $this->assertInstanceOf('RabbitMQ\Management\Entity\Connection', $connection);

        $this->assertEquals($expectedConnection, $connection);
    }

    public function testDeleteConnection(): void
    {
        $this->needConnection();

        $success = false;

        $connections = $this->object->listConnections()->toArray();
        $expectedConnection = array_pop($connections);
        $this->object->deleteConnection($expectedConnection->name);
        /* make sure we don't try to tear down */
        $this->channel = null;
        $this->conn = null;

        /* wait for info to update on server */
        sleep(10);
        /* its either going to return the connection in closed state or failed to find it */
        try {
            $expectedConnection = $this->object->getConnection($expectedConnection->name);
            if ($expectedConnection->state == "closed") {
                $success = true;
            }
        } catch (EntityNotFoundException $e) {
            $this->assertContains("Failed to find connection", $e->getMessage());
            $success = true;
        }
        $this->assertEquals(true, $success);

    }

    public function testListChannels(): void
    {
        $this->needConnection();
        $channels = $this->object->listChannels();

        $this->assertNonEmptyArrayCollection($channels);

        foreach ($channels as $channel) {
            $this->assertInstanceOf('RabbitMQ\Management\Entity\Channel', $channel);
        }
    }

    public function testGetChannel(): void
    {
        $this->needConnection();
        $channels = $this->object->listChannels()->toArray();
        $expectedChannel = array_pop($channels);

        $channel = $this->object->getChannel($expectedChannel->name);
        $this->assertInstanceOf('RabbitMQ\Management\Entity\Channel', $channel);

        $this->assertEquals($expectedChannel->name, $channel->name);
    }

    public function testListExchanges(): void
    {
        $exchanges = $this->object->listExchanges();

        $this->assertNonEmptyArrayCollection($exchanges);

        foreach ($exchanges as $exchange) {
            $this->assertInstanceOf('RabbitMQ\Management\Entity\Exchange', $exchange);
        }
    }

    public function testListExchangesWithVhost(): void
    {
        $exchanges = $this->object->listExchanges(self::VIRTUAL_HOST);

        $this->assertNonEmptyArrayCollection($exchanges);

        foreach ($exchanges as $exchange) {
            $this->assertInstanceOf('RabbitMQ\Management\Entity\Exchange', $exchange);
        }
    }

    public function testListExchangeFailed(): void
    {
        $this->expectException(ClientException::class);
        $this->object->listExchanges(self::NONEXISTENT_VIRTUAL_HOST);
    }

    public function testGetExchange(): void
    {
        $exchanges = $this->object->listExchanges()->toArray();
        $expectedExchange = array_pop($exchanges);

        $exchange = $this->object->getExchange($expectedExchange->vhost, $expectedExchange->name);
        $this->assertInstanceOf('RabbitMQ\Management\Entity\Exchange', $exchange);

        $this->assertEquals($expectedExchange, $exchange);
    }


    public function testGetExchangeFailed(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->object->getExchange(self::NONEXISTENT_VIRTUAL_HOST, self::EXCHANGE_TEST_NAME);
    }

    public function testDeleteExchange(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $exchange = new Exchange();
        $exchange->name = self::EXCHANGE_TEST_NAME;
        $exchange->vhost = '/';
        $exchange->type = 'fanout';

        $this->object->addExchange($exchange);
        $this->object->deleteExchange('/', self::EXCHANGE_TEST_NAME);

        /* expected to raise an exception */
        $this->object->getExchange($exchange->vhost, $exchange->name);

    }

    public function testDeleteExchangeFailed(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->object->deleteExchange(self::NONEXISTENT_VIRTUAL_HOST, self::EXCHANGE_TEST_NAME);
    }

    public function testAddExchange(): void
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


    public function testAddExchangeFailed(): void
    {
        $this->expectException(ClientException::class);
        $exchange = new Exchange();

        $exchange->vhost = self::NONEXISTENT_VIRTUAL_HOST;
        $exchange->type = 'fanout';
        $exchange->name = self::EXCHANGE_TEST_NAME;
        $exchange->durable = true;

        $this->object->addExchange($exchange);
    }

    public function testAddExchangeThatAlreadyExists(): void
    {
        $this->expectNotToPerformAssertions();
        $exchange = new Exchange();
        $exchange->vhost = '/';
        $exchange->name = self::EXCHANGE_TEST_NAME;
        $exchange->type = 'fanout';

        $this->object->addExchange($exchange);

        $exchange->durable = true;

        try {
            $this->object->addExchange($exchange);
            $this->fail('Should raise an exception');
        } catch (PreconditionFailedException $e) {

        }
        $this->object->deleteExchange($exchange->vhost, $exchange->name);
    }

    public function testListQueues(): void
    {
        $this->createQueue();

        $queues = $this->object->listQueues();

        $this->assertNonEmptyArrayCollection($queues);

        foreach ($queues as $queue) {
            $this->assertInstanceOf('RabbitMQ\Management\Entity\Queue', $queue);
        }
    }

    private function createQueue(): Queue
    {
        $queue = new Queue();
        $queue->vhost = '/';
        $queue->name = self::QUEUE_TEST_NAME;

        $this->object->addQueue($queue);

        return $queue;
    }

    public function testListQueuesWithVhost(): void
    {
        $this->createQueue();

        $queues = $this->object->listQueues(self::VIRTUAL_HOST);

        $this->assertNonEmptyArrayCollection($queues);

        foreach ($queues as $queue) {
            $this->assertInstanceOf('RabbitMQ\Management\Entity\Queue', $queue);
        }
    }

    public function testListQueueFailed(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->object->listQueues(self::NONEXISTENT_VIRTUAL_HOST);
    }

    public function testGetQueue(): void
    {
        $this->createQueue();

        $queues = $this->object->listQueues()->toArray();
        $expectedQueue = array_pop($queues);

        $this->assertInstanceOf('RabbitMQ\Management\Entity\Queue', $expectedQueue);

        $queue = $this->object->getQueue($expectedQueue->vhost, $expectedQueue->name);
        $this->assertInstanceOf('RabbitMQ\Management\Entity\Queue', $queue);

        $this->assertEquals($expectedQueue->vhost, $queue->vhost);
        $this->assertEquals($expectedQueue->name, $queue->name);
    }

    public function testGetQueueFailed(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->object->getQueue(self::NONEXISTENT_VIRTUAL_HOST, self::QUEUE_TEST_NAME);
    }

    public function testAddQueue(): void
    {
        $queue = new Queue();
        $queue->vhost = '/';
        $queue->name = self::QUEUE_TEST_NAME;

        $this->object->addQueue($queue);

        $foundQueue = $this->object->getQueue($queue->vhost, $queue->name);

        $this->assertEquals($queue, $foundQueue);

        $this->object->deleteQueue('/', self::QUEUE_TEST_NAME);
    }

    public function testAddQueueFailed(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $queue = new Queue();
        $queue->vhost = self::NONEXISTENT_VIRTUAL_HOST;
        $queue->name = self::QUEUE_TEST_NAME;

        $this->object->addQueue($queue);
    }

    public function testDeleteQueue(): void
    {
        $this->expectException(EntityNotFoundException::class);

        $queue = $this->createQueue();

        $this->object->deleteQueue('/', self::QUEUE_TEST_NAME);

        /* expected to throw exception */
        $this->object->getQueue($queue->vhost, $queue->name);
    }

    public function testDeleteQueueFailed(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->object->deleteQueue(self::NONEXISTENT_VIRTUAL_HOST, self::QUEUE_TEST_NAME);
    }

    public function testAddQueueThatAlreadyExists(): void
    {
        $this->expectNotToPerformAssertions();

        $queue = $this->createQueue();

        $queue->durable = true;

        try {
            $this->object->addQueue($queue);
            $this->fail('Should raise an exception');
        } catch (PreconditionFailedException $e) {

        }
    }

    public function testPurgeQueueFailed(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->object->purgeQueue(self::NONEXISTENT_VIRTUAL_HOST, self::QUEUE_TEST_NAME);
    }

    public function testListBindingsByQueue(): void
    {
        $this->createQueue();
        $this->createBinding();

        foreach ($this->object->listQueues() as $queue) {
            foreach ($this->object->listBindingsByQueue($queue) as $binding) {
                /* @var $binding Binding */
                $this->assertEquals('/', $binding->vhost);
                $this->assertEquals($queue->name, $binding->destination);
            }
        }
    }

    public function testListBindingsOnUnknownHostOrQueue(): void
    {
        $queue = new Queue();
        $queue->name = 'nonexistent queue';
        $queue->vhost = self::NONEXISTENT_VIRTUAL_HOST;

        $this->assertCount(0, $this->object->listBindingsByQueue($queue));
    }

    public function testListBindingsByExchangeAndQueue(): void
    {
        $this->createQueue();
        $this->createBinding();

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

    public function testListBindingsByExchangeAndQueueOnUnknownVhostOrQueue(): void
    {
        $this->assertCount(0, $this->object->listBindingsByExchangeAndQueue(self::NONEXISTENT_VIRTUAL_HOST, self::EXCHANGE_TEST_NAME, self::QUEUE_TEST_NAME));
    }

    public function testAddBinding(): void
    {
        $this->expectNotToPerformAssertions();

        $this->createQueue();

        $exchange = new Exchange();
        $exchange->name = self::EXCHANGE_TEST_NAME;
        $exchange->vhost = '/';

        $this->object->addExchange($exchange);

        $binding = new Binding();
        $binding->vhost = '/';
        $binding->source = self::EXCHANGE_TEST_NAME;
        $binding->destination = self::QUEUE_TEST_NAME;
        $binding->destination_type = 'direct';
        $binding->routing_key = 'rounting.key';
        $binding->arguments = array('bim' => 'boom');

        $this->object->addBinding($binding);

        $found = false;

        foreach ($this->object->listBindingsByExchangeAndQueue('/', self::EXCHANGE_TEST_NAME, self::QUEUE_TEST_NAME) as $binding) {
            if ($binding->routing_key === 'rounting.key' && $binding->arguments === array('bim' => 'boom')) {
                $found = $binding;
            }
        }

        if (!$found) {
            $this->fail('unable to find back the binding');
        }

        $this->object->deleteBinding('/', self::EXCHANGE_TEST_NAME, self::QUEUE_TEST_NAME, $found);
    }

    public function testAddBindingFailed(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $binding = new Binding();
        $binding->vhost = '/';
        $binding->source = self::EXCHANGE_TEST_NAME;
        $binding->destination = self::QUEUE_TEST_NAME;

        $this->object->addBinding($binding);
    }

    public function testDeleteBinding(): void
    {
        $this->expectNotToPerformAssertions();

        $this->createBinding();

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

    private function createBinding(): void
    {
        $this->createQueue();

        $exchange = new Exchange();
        $exchange->name = self::EXCHANGE_TEST_NAME;
        $exchange->vhost = '/';

        $this->object->addExchange($exchange);

        $binding = new Binding();
        $binding->vhost = '/';
        $binding->source = self::EXCHANGE_TEST_NAME;
        $binding->destination = self::QUEUE_TEST_NAME;
        $binding->routing_key = 'rounting.key';

        $this->object->addBinding($binding);
    }


    public function testDeleteNonexistentBinding(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $binding = new Binding();
        $binding->properties_key = 'bingo';

        $this->object->deleteBinding('/', self::EXCHANGE_TEST_NAME, self::QUEUE_TEST_NAME, $binding);
    }

    public function testListBindings(): void
    {
        $this->createBinding();

        $bindings = $this->object->listBindings();

        $this->assertNonEmptyArrayCollection($bindings);

        foreach ($bindings as $binding) {
            $this->assertInstanceOf('RabbitMQ\Management\Entity\Binding', $binding);
        }
    }

    public function testListBindingsWithVhost(): void
    {
        $this->createBinding();

        $bindings = $this->object->listBindings(self::VIRTUAL_HOST);

        $this->assertNonEmptyArrayCollection($bindings);

        foreach ($bindings as $binding) {
            $this->assertInstanceOf('RabbitMQ\Management\Entity\Binding', $binding);
        }
    }

    public function testAlivenessTest(): void
    {
        $this->assertTrue($this->object->alivenessTest(self::VIRTUAL_HOST));
    }

    public function testAlivenessTestFailed(): void
    {
        $this->assertFalse($this->object->alivenessTest(self::NONEXISTENT_VIRTUAL_HOST));
    }

    public function testPurgeQueue(): void
    {
        $queue = $this->createQueue();

        $exchange = new Exchange();
        $exchange->name = self::EXCHANGE_TEST_NAME;
        $exchange->vhost = '/';

        $this->object->addExchange($exchange);

        $binding = new Binding();
        $binding->vhost = '/';
        $binding->source = self::EXCHANGE_TEST_NAME;
        $binding->destination = self::QUEUE_TEST_NAME;
        $binding->routing_key = self::QUEUE_TEST_NAME;

        $this->object->addBinding($binding);

        $message = json_encode(array(
            'properties' => array(),
            'routing_key' => self::QUEUE_TEST_NAME,
            'payload' => 'body',
            'payload_encoding' => 'string',
        ));

        $message = str_replace('[]', '{}', $message);

        $n = 12;
        while ($n > 0) {
            $this->client->post('/api/exchanges/' . urlencode('/') . '/' . self::EXCHANGE_TEST_NAME . '/publish',
                ['headers' => ['content-type' => 'application/json'], 'body' => $message]);
            $n--;
        }

        sleep(5);
        $this->object->refreshQueue($queue);
        $this->assertEquals(12, $queue->messages_ready);

        $this->object->purgeQueue('/', self::QUEUE_TEST_NAME);

        sleep(5);
        $this->object->refreshQueue($queue);
        $this->assertEquals(0, $queue->messages_ready);
    }

    public function assertNonEmptyArrayCollection($collection): void
    {
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $collection);
        $this->assertGreaterThan(0, count($collection), 'Collection is not empty');
    }
}
