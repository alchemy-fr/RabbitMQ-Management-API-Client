<?php /** @noinspection DuplicatedCode */

/** @noinspection PhpUndefinedNamespaceInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace RabbitMQ\Tests\Management;

use PHPUnit\Framework\TestCase;
use RabbitMQ\Management\APIClient;
use RabbitMQ\Management\Guarantee;
use RabbitMQ\Management\Entity\Binding;
use RabbitMQ\Management\Entity\Exchange;
use RabbitMQ\Management\Entity\Queue;

class GuaranteeTest extends TestCase
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

    protected function setUp() : void
    {
        $this->client = APIClient::factory(array('host' => 'localhost'));
        $this->object = new Guarantee($this->client);
    }


    /**
     * @covers RabbitMQ\Management\Guarantee::probeExchange
     */
    public function testProbeExchange()
    {
        $expectedExchange = new Exchange();
        $expectedExchange->name = 'test.exchange';
        $expectedExchange->vhost = '/';
        $expectedExchange->type = 'topic';

        $this->client->addExchange($expectedExchange);

        $exchange = new Exchange();
        $exchange->vhost = '/';
        $exchange->name = 'test.exchange';
        $exchange->type = 'topic';

        $status = $this->object->probeExchange($exchange);

        $this->client->deleteExchange($expectedExchange->vhost, $expectedExchange->name);

        $this->assertEquals(Guarantee::PROBE_OK, $status);
    }

    /**
     * @covers RabbitMQ\Management\Guarantee::probeExchange
     */
    public function testProbeExchangeWithDifference()
    {
        $expectedExchange = new Exchange();
        $expectedExchange->name = 'test.exchange';
        $expectedExchange->vhost = '/';
        $expectedExchange->type = 'topic';

        $this->client->addExchange($expectedExchange);

        $exchange = new Exchange();
        $exchange->vhost = '/';
        $exchange->name = 'test.exchange';
        $exchange->type = 'fanout';

        $status = $this->object->probeExchange($exchange);

        $this->client->deleteExchange($expectedExchange->vhost, $expectedExchange->name);

        $this->assertEquals(Guarantee::PROBE_MISCONFIGURED, $status);
    }

    /**
     * @covers RabbitMQ\Management\Guarantee::probeExchange
     */
    public function testProbeExchangeThatDoesNotExists()
    {
        $exchange = new Exchange();
        $exchange->vhost = '/';
        $exchange->name = 'test.exchange';
        $exchange->type = 'fanout';

        $status = $this->object->probeExchange($exchange);

        $this->assertEquals(Guarantee::PROBE_ABSENT, $status);
    }

    /**
     * @covers RabbitMQ\Management\Guarantee::ensureExchange
     */
    public function testEnsureExchange()
    {
        $exchange = new Exchange();
        $exchange->vhost = '/';
        $exchange->name = 'test.exchange';

        $this->object->ensureExchange($exchange);

        $this->assertInstanceOf('RabbitMQ\Management\Entity\Exchange', $exchange);
        $this->assertFalse($exchange->durable);
        $this->assertFalse($exchange->auto_delete);
        $this->assertFalse($exchange->internal);

        $this->client->deleteExchange($exchange->vhost, $exchange->name);
    }

    /**
     * @covers RabbitMQ\Management\Guarantee::ensureExchange
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

        $this->assertInstanceOf('RabbitMQ\Management\Entity\Exchange', $exchange);
        $this->assertTrue($exchange->durable);
        $this->assertTrue($exchange->auto_delete);
        $this->assertTrue($exchange->internal);

        $this->client->deleteExchange($exchange->vhost, $exchange->name);
    }

    /**
     * @covers RabbitMQ\Management\Guarantee::ensureExchange
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
     * @covers RabbitMQ\Management\Guarantee::ensureExchange
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
     * @covers RabbitMQ\Management\Guarantee::probeQueue
     */
    public function testProbeQueue()
    {
        $expectedQueue = new Queue();
        $expectedQueue->name = 'test.queue';
        $expectedQueue->vhost = '/';

        $this->client->addQueue($expectedQueue);

        $queue = new Queue();
        $queue->vhost = '/';
        $queue->name = 'test.queue';

        $status = $this->object->probeQueue($queue);

        $this->client->deleteQueue($queue->vhost, $queue->name);

        $this->assertEquals(Guarantee::PROBE_OK, $status);
    }

    /**
     * @covers RabbitMQ\Management\Guarantee::probeQueue
     */
    public function testProbeQueueWithDifference()
    {
        $expectedQueue = new Queue();
        $expectedQueue->name = 'test.queue';
        $expectedQueue->vhost = '/';
        $expectedQueue->durable = true;

        $this->client->addQueue($expectedQueue);

        $queue = new Queue();
        $queue->vhost = '/';
        $queue->name = 'test.queue';

        $status = $this->object->probeQueue($queue);

        $this->client->deleteQueue($queue->vhost, $queue->name);

        $this->assertEquals(Guarantee::PROBE_MISCONFIGURED, $status);
    }

    /**
     * @covers RabbitMQ\Management\Guarantee::probeQueue
     */
    public function testProbeQueueThatDoesNotExists()
    {
        $queue = new Queue();
        $queue->vhost = '/';
        $queue->name = 'test.queue';

        $status = $this->object->probeQueue($queue);

        $this->assertEquals(Guarantee::PROBE_ABSENT, $status);
    }

    /**
     * @covers RabbitMQ\Management\Guarantee::ensureQueue
     */
    public function testEnsureQueue()
    {
        $queue = new Queue();
        $queue->vhost = '/';
        $queue->name = 'test.queue';

        $this->object->ensureQueue($queue);

        $this->assertInstanceOf('RabbitMQ\Management\Entity\Queue', $queue);
        $this->assertFalse($queue->durable);
        $this->assertFalse($queue->auto_delete);

        $this->client->deleteQueue($queue->vhost, $queue->name);
    }

    /**
     * @covers RabbitMQ\Management\Guarantee::ensureQueue
     */
    public function testEnsureQueueWithOptions()
    {
        $queue = new Queue();
        $queue->vhost = '/';
        $queue->name = 'test.queue';
        $queue->auto_delete = true;
        $queue->durable = true;

        $this->object->ensureQueue($queue);

        $this->assertInstanceOf('RabbitMQ\Management\Entity\Queue', $queue);
        $this->assertTrue($queue->durable);
        $this->assertTrue($queue->auto_delete);

        $this->client->deleteQueue($queue->vhost, $queue->name);
    }

    /**
     * @covers RabbitMQ\Management\Guarantee::ensureQueue
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
     * @covers RabbitMQ\Management\Guarantee::ensureQueue
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

    public function testProbeBindingThatDoesExist()
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
        $binding->vhost = '/';
        $binding->source = 'test.exchange';
        $binding->destination = 'test.queue';
        $binding->routing_key = 'special.routing.key';

        $this->client->addBinding($binding);

        $status = $this->object->probeBinding($binding);

        $this->client->deleteExchange($exchange->vhost, $exchange->name);
        $this->client->deleteQueue($queue->vhost, $queue->name);

        $this->assertEquals(Guarantee::PROBE_OK, $status);
    }

    public function testProbeBindingThatDoesNotExist()
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
        $binding->vhost = '/';
        $binding->source = 'test.exchange';
        $binding->destination = 'test.queue';
        $binding->routing_key = 'special.routing.key';

        $status = $this->object->probeBinding($binding);

        $this->client->deleteExchange($exchange->vhost, $exchange->name);
        $this->client->deleteQueue($queue->vhost, $queue->name);

        $this->assertEquals(Guarantee::PROBE_ABSENT, $status);
    }

    public function testProbeBindingThatDoesExistWithDifferentArguments()
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
        $binding->vhost = '/';
        $binding->source = 'test.exchange';
        $binding->destination = 'test.queue';
        $binding->routing_key = 'special.routing.key';
        $binding->arguments = array('bim', 'boum');

        $this->client->addBinding($binding);

        $status = $this->object->probeBinding($binding);

        $this->client->deleteExchange($exchange->vhost, $exchange->name);
        $this->client->deleteQueue($queue->vhost, $queue->name);

        $this->assertEquals(Guarantee::PROBE_ABSENT, $status);
    }

    /**
     * @covers RabbitMQ\Management\Guarantee::ensureBinding
     */
    public function testEnsureBinding()
    {
        $this->expectNotToPerformAssertions();

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
        $binding->vhost = '/';
        $binding->source = 'test.exchange';
        $binding->destination = 'test.queue';
        $binding->routing_key = 'special.routing.key';

        $this->object->ensureBinding($binding);

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
     * @covers RabbitMQ\Management\Guarantee::ensureBinding
     */
    public function testEnsureBindingAlreadyBound()
    {
        $this->expectNotToPerformAssertions();

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
        $binding->vhost = '/';
        $binding->source = 'test.exchange';
        $binding->destination = 'test.queue';
        $binding->routing_key = 'special.routing.key';

        $this->client->addBinding($binding);

        $this->object->ensureBinding($binding);

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
