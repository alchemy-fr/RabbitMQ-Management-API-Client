<?php

namespace RabbitMQ\Tests\Management;

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Channel\AMQPChannel;
use RabbitMQ\Management\APIClient;
use RabbitMQ\Management\AsyncAPIClient;
use RabbitMQ\Management\HttpClient;
use RabbitMQ\Management\Entity\Exchange;
use RabbitMQ\Management\Entity\Queue;
use RabbitMQ\Management\Entity\Binding;

class AsyncAPIClientTest extends \PHPUnit_Framework_TestCase
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
     * @var AMQPConnection
     */
    protected $conn;

    /**
     * @var AMQPChannel
     */
    protected $channel;

    protected function setUp()
    {
        $this->loop = \React\EventLoop\Factory::create();
        $this->object = AsyncAPIClient::factory($this->loop, array('host'             => '127.0.0.1'));

        $this->syncClientClient = HttpClient::factory(array('host'         => 'localhost'));
        $this->syncClient = new APIClient($this->syncClientClient);

        $this->conn = new \PhpAmqpLib\Connection\AMQPConnection('localhost', 5672, 'guest', 'guest', '/');
        $this->channel = $this->conn->channel();
    }

    public function tearDown()
    {
        try {
            $this->syncClient->deleteQueue('/', self::QUEUE_TEST_NAME);
        } catch (\Exception $e) {

        }
        try {
            $this->syncClient->deleteExchange('/', self::EXCHANGE_TEST_NAME);
        } catch (\Exception $e) {

        }

        $this->channel->close();
        $this->conn->close();
    }

    public function testListConnections()
    {
        $loop = $this->loop;
        $PHPUnit = $this;

        $this->object->listConnections()
            ->then(function($connections) use ($PHPUnit, $loop) {
                $PHPUnit->assertNonEmptyArrayCollection($connections);
                foreach ($connections as $connection) {
                    $PHPUnit->assertInstanceOf('RabbitMQ\Management\Entity\Connection', $connection);
                }
                $loop->stop();
            }, function() use ($PHPUnit) {
                $PHPUnit->fail('Should no fail');
            });

        $loop->run();
    }

    public function testGetConnection()
    {
        $loop = $this->loop;
        $PHPUnit = $this;

        $connections = $this->syncClient->listConnections()->toArray();
        $expectedConnection = array_pop($connections);

        $this->object->getConnection($expectedConnection->name)
            ->then(function($connection) use ($expectedConnection, $PHPUnit, $loop) {
                $PHPUnit->assertInstanceOf('RabbitMQ\Management\Entity\Connection', $connection);
                $PHPUnit->assertEquals($expectedConnection, $connection);
                $loop->stop();
            }, function() use ($PHPUnit) {
                $PHPUnit->fail('Should no fail');
            });

        $loop->run();
    }

    public function testDeleteConnection()
    {
        $this->markTestSkipped('Not working ?!');
    }

    public function testListChannels()
    {
        $loop = $this->loop;
        $PHPUnit = $this;

        $this->object->listChannels()
            ->then(function($channels) use ($PHPUnit, $loop) {
                $PHPUnit->assertNonEmptyArrayCollection($channels);
                foreach ($channels as $channel) {
                    $PHPUnit->assertInstanceOf('RabbitMQ\Management\Entity\Channel', $channel);
                }
                $loop->stop();
            }, function() use ($PHPUnit) {
                $PHPUnit->fail('Should no fail');
            });

        $loop->run();
    }

    public function testGetChannel()
    {
        $loop = $this->loop;
        $PHPUnit = $this;

        $channels = $this->syncClient->listChannels()->toArray();
        $expectedChannel = array_pop($channels);

        $this->object->getConnection($expectedChannel->name)
            ->then(function($channel) use ($expectedChannel, $PHPUnit, $loop) {
                $PHPUnit->assertInstanceOf('RabbitMQ\Management\Entity\Channel', $channel);
                $PHPUnit->assertEquals($expectedChannel, $channel);
                $loop->stop();
            }, function() use ($PHPUnit) {
                $PHPUnit->fail('Should no fail');
            });

        $loop->run();
    }

    public function testListExchanges()
    {
        $loop = $this->loop;
        $PHPUnit = $this;

        $this->object->listExchanges()
            ->then(function($exchanges) use ($PHPUnit, $loop) {
                $PHPUnit->assertNonEmptyArrayCollection($exchanges);
                foreach ($exchanges as $exchange) {
                    $PHPUnit->assertInstanceOf('RabbitMQ\Management\Entity\Exchange', $exchange);
                }
                $loop->stop();
            }, function() use ($PHPUnit) {
                $PHPUnit->fail('Should no fail');
            });

        $loop->run();
    }

    public function testListExchangesWithVhost()
    {
        $loop = $this->loop;
        $PHPUnit = $this;

        $this->object->listExchanges(self::VIRTUAL_HOST)
            ->then(function($exchanges) use ($PHPUnit, $loop) {
                $PHPUnit->assertNonEmptyArrayCollection($exchanges);
                foreach ($exchanges as $exchange) {
                    $PHPUnit->assertInstanceOf('RabbitMQ\Management\Entity\Exchange', $exchange);
                    $PHPUnit->assertEquals($PHPUnit::VIRTUAL_HOST, $exchange->vhost);
                }
                $loop->stop();
            }, function() use ($PHPUnit) {
                $PHPUnit->fail('Should no fail');
            });

        $loop->run();
    }

    public function testListExchangeFailed()
    {
        $loop = $this->loop;
        $PHPUnit = $this;

        $success = false;
        $this->object->listExchanges(self::NONEXISTENT_VIRTUAL_HOST)
            ->then(function($exchanges) use ($PHPUnit) {
                $PHPUnit->fail('Should not success');
            }, function() use (&$success, $loop) {
                $success = true;
                $loop->stop();
            });

        $loop->run();
        $this->assertTrue($success);
    }

    public function testGetExchange()
    {
        $loop = $this->loop;
        $PHPUnit = $this;

        $exchanges = $this->syncClient->listExchanges()->toArray();
        $expectedExchange = array_pop($exchanges);

        $success = false;
        $this->object->getExchange($expectedExchange->vhost, $expectedExchange->name)
            ->then(function($exchange) use ($expectedExchange, &$success, $loop, $PHPUnit) {
                $PHPUnit->assertInstanceOf('RabbitMQ\Management\Entity\Exchange', $exchange);
                $PHPUnit->assertEquals($expectedExchange, $exchange);
                $success = true;
                $loop->stop();
            }, function() use ($PHPUnit) {
                $PHPUnit->fail('Should not success');
            });

        $loop->run();
        $this->assertTrue($success);
    }

    public function testGetExchangeFailed()
    {
        $loop = $this->loop;
        $PHPUnit = $this;

        $success = false;
        $this->object->getExchange(self::NONEXISTENT_VIRTUAL_HOST, self::EXCHANGE_TEST_NAME)
            ->then(function($exchanges) use ($PHPUnit) {
                $PHPUnit->fail('Should not success');
            }, function() use (&$success, $loop) {
                $success = true;
                $loop->stop();
            });

        $loop->run();
        $this->assertTrue($success);
    }

    public function testDeleteExchange()
    {
        $exchange = new Exchange();
        $exchange->name = self::EXCHANGE_TEST_NAME;
        $exchange->vhost = '/';

        $this->syncClient->addExchange($exchange);
        $this->syncClient->deleteExchange('/', self::EXCHANGE_TEST_NAME);

        $loop = $this->loop;
        $PHPUnit = $this;

        $success = false;
        $this->object->getExchange(self::NONEXISTENT_VIRTUAL_HOST, self::EXCHANGE_TEST_NAME)
            ->then(function($exchanges) use ($PHPUnit) {
                $PHPUnit->fail('Should not success');
            }, function() use (&$success, $loop) {
                $success = true;
                $loop->stop();
            });

        $loop->run();
        $this->assertTrue($success);
    }

    public function testDeleteExchangeFailed()
    {
        $loop = $this->loop;
        $PHPUnit = $this;

        $success = false;
        $this->object->deleteExchange(self::NONEXISTENT_VIRTUAL_HOST, self::EXCHANGE_TEST_NAME)
            ->then(function($exchanges) use ($PHPUnit) {
                $PHPUnit->fail('Should not success');
            }, function() use (&$success, $loop) {
                $success = true;
                $loop->stop();
            });

        $loop->run();
        $this->assertTrue($success);

        $this->object->deleteExchange(self::NONEXISTENT_VIRTUAL_HOST, self::EXCHANGE_TEST_NAME);
    }

    public function testAddExchange()
    {
        $exchange = new Exchange();

        $exchange->vhost = '/';
        $exchange->type = 'fanout';
        $exchange->name = self::EXCHANGE_TEST_NAME;
        $exchange->durable = true;

        $loop = $this->loop;
        $PHPUnit = $this;

        $success = false;
        $this->object->addExchange($exchange)
            ->then(function($resultExchange) use ($exchange, &$success, $loop, $PHPUnit) {
                $PHPUnit->assertInstanceOf('RabbitMQ\Management\Entity\Exchange', $exchange);
                $PHPUnit->assertEquals($resultExchange->toJson(), $exchange->toJson());
                $success = true;
                $loop->stop();
            }, function() use ($PHPUnit) {
                $PHPUnit->fail('Should not success');
            });

        $loop->run();
        $this->assertTrue($success);
    }

    public function testAddExchangeFailed()
    {
        $exchange = new Exchange();

        $exchange->vhost = self::NONEXISTENT_VIRTUAL_HOST;
        $exchange->type = 'fanout';
        $exchange->name = self::EXCHANGE_TEST_NAME;
        $exchange->durable = true;

        $loop = $this->loop;
        $PHPUnit = $this;

        $success = false;
        $this->object->addExchange($exchange)
            ->then(function() use ($PHPUnit) {
                $PHPUnit->fail('Should not success');
            }, function() use (&$success, $loop) {
                $success = true;
                $loop->stop();
            });

        $loop->run();
        $this->assertTrue($success);
    }

    public function testAddExchangeThatAlreadyExists()
    {
        $exchange = new Exchange();
        $exchange->vhost = '/';
        $exchange->name = self::EXCHANGE_TEST_NAME;

        $this->syncClient->addExchange($exchange);

        $exchange->durable = true;

        $loop = $this->loop;
        $PHPUnit = $this;

        $success = false;
        $this->object->addExchange($exchange)
            ->then(function() use ($PHPUnit) {
                $PHPUnit->fail('Should not success');
            }, function() use (&$success, $loop) {
                $success = true;
                $loop->stop();
            });

        $loop->run();
        $this->assertTrue($success);
    }

    public function testListQueues()
    {
        $queue = $this->createQueue();

        $loop = $this->loop;
        $PHPUnit = $this;

        $this->object->listQueues()
            ->then(function($queues) use ($PHPUnit, $loop) {
                $PHPUnit->assertNonEmptyArrayCollection($queues);
                foreach ($queues as $queue) {
                    $PHPUnit->assertInstanceOf('RabbitMQ\Management\Entity\Queue', $queue);
                }
                $loop->stop();
            }, function() use ($PHPUnit) {
                $PHPUnit->fail('Should no fail');
            });

        $loop->run();
    }

    private function createQueue()
    {
        $queue = new Queue();
        $queue->vhost = self::VIRTUAL_HOST;
        $queue->name = self::QUEUE_TEST_NAME;

        $this->syncClient->addQueue($queue);

        return $queue;
    }

    public function testListQueuesWithVhost()
    {
        $queue = $this->createQueue();

        $loop = $this->loop;
        $PHPUnit = $this;

        $this->object->listQueues(self::VIRTUAL_HOST)
            ->then(function($queues) use ($PHPUnit, $loop) {
                $PHPUnit->assertNonEmptyArrayCollection($queues);
                foreach ($queues as $queue) {
                    $PHPUnit->assertInstanceOf('RabbitMQ\Management\Entity\Queue', $queue);
                }
                $loop->stop();
            }, function() use ($PHPUnit) {
                $PHPUnit->fail('Should no fail');
            });

        $loop->run();
    }

    public function testListQueueFailed()
    {
        $loop = $this->loop;
        $PHPUnit = $this;

        $this->object->listQueues(self::NONEXISTENT_VIRTUAL_HOST)
            ->then(function($queues) use ($PHPUnit, $loop) {
                $PHPUnit->fail('Should not success');
            }, function() use (&$success, $loop) {
                $success = true;
                $loop->stop();
            });

        $loop->run();
        $this->assertTrue($success);
    }

    public function testGetQueue()
    {
        $this->createQueue();

        $loop = $this->loop;
        $PHPUnit = $this;
        $success = false;

        $this->object->getQueue(self::VIRTUAL_HOST, self::QUEUE_TEST_NAME)
            ->then(function($resultQueue) use (&$success, $PHPUnit, $loop) {
                $PHPUnit->assertInstanceOf('RabbitMQ\Management\Entity\Queue', $resultQueue);
                $PHPUnit->assertEquals($PHPUnit::VIRTUAL_HOST, $resultQueue->vhost);
                $PHPUnit->assertEquals($PHPUnit::QUEUE_TEST_NAME, $resultQueue->name);
                $success = true;
                $loop->stop();
            }, function() use ($PHPUnit) {
                $PHPUnit->fail('Should not fail');
            });

        $loop->run();
        $this->assertTrue($success);
    }

    public function testGetQueueFailed()
    {
        $loop = $this->loop;
        $PHPUnit = $this;

        $this->object->getQueue(self::NONEXISTENT_VIRTUAL_HOST, self::QUEUE_TEST_NAME)
            ->then(function($queues) use ($PHPUnit, $loop) {
                $PHPUnit->fail('Should not success');
            }, function() use (&$success, $loop) {
                $success = true;
                $loop->stop();
            });

        $loop->run();
        $this->assertTrue($success);
    }

    public function testAddQueue()
    {
        $loop = $this->loop;
        $client = $this->object;
        $PHPUnit = $this;

        $queue = new Queue();
        $queue->vhost = '/';
        $queue->name = self::QUEUE_TEST_NAME;

        $this->object->addQueue($queue)
            ->then(function($returnedQueue) use ($queue, $loop, $client, $PHPUnit) {
                $PHPUnit->assertInstanceOf('RabbitMQ\\Management\\Entity\\Queue', $returnedQueue);
                $PHPUnit->assertEquals($returnedQueue->toJson(), $queue->toJson());

                $client->deleteQueue('/', $PHPUnit::QUEUE_TEST_NAME)
                ->then(function() use ($loop) {
                        $loop->stop();
                    });
            }, function() use ($PHPUnit) {
                $PHPUnit->fail('Should not success');
            });

        $loop->run();
    }

    public function testAddQueueFailed()
    {
        $queue = new Queue();
        $queue->vhost = self::NONEXISTENT_VIRTUAL_HOST;
        $queue->name = self::QUEUE_TEST_NAME;

        $loop = $this->loop;
        $PHPUnit = $this;

        $this->object->addQueue($queue)
            ->then(function() use ($PHPUnit, $loop) {
                $PHPUnit->fail('Should not success');
            }, function() use (&$success, $loop) {
                $success = true;
                $loop->stop();
            });

        $loop->run();
        $this->assertTrue($success);
    }

    public function testDeleteQueue()
    {
        $queue = $this->createQueue();

        $loop = $this->loop;
        $client = $this->object;
        $PHPUnit = $this;
        $success = false;

        $this->object->deleteQueue($queue->vhost, $queue->name)
            ->then(function() use ($queue, $client, &$success) {
                $client->getQueue($queue->vhost, $queue->name)
                ->then(function() {
                        $PHPUnit->fail('Should not success');
                    }, function() use (&$success) {
                        $success = true;
                    });
            }, function() use ($PHPUnit) {
                $PHPUnit->fail('Should not fail');
            });

        $loop->run();
        $this->assertTrue($success);
    }

    public function testDeleteQueueFailed()
    {
        $loop = $this->loop;
        $PHPUnit = $this;

        $this->object->deleteQueue(self::NONEXISTENT_VIRTUAL_HOST, self::QUEUE_TEST_NAME)
            ->then(function() use ($PHPUnit, $loop) {
                $PHPUnit->fail('Should not success');
            }, function() use (&$success, $loop) {
                $success = true;
                $loop->stop();
            });

        $loop->run();
        $this->assertTrue($success);
    }

    public function testAddQueueThatAlreadyExists()
    {
        $queue = $this->createQueue();

        $queue->durable = true;

        $loop = $this->loop;
        $PHPUnit = $this;

        $this->object->addQueue($queue)
            ->then(function() use ($PHPUnit, $loop) {
                $PHPUnit->fail('Should not success');
            }, function() use (&$success, $loop) {
                $success = true;
                $loop->stop();
            });

        $loop->run();
        $this->assertTrue($success);
    }

    public function testPurgeQueueFailed()
    {
        $loop = $this->loop;
        $PHPUnit = $this;
        $success = false;

        $this->object->purgeQueue(self::NONEXISTENT_VIRTUAL_HOST, self::QUEUE_TEST_NAME)
            ->then(function() use ($PHPUnit, $loop) {
                $PHPUnit->fail('Should not success');
            }, function() use (&$success, $loop) {
                $success = true;
                $loop->stop();
            });

        $loop->run();
        $this->assertTrue($success);
    }

    public function testListBindingsByQueue()
    {
        $loop = $this->loop;
        $PHPUnit = $this;

        foreach ($this->syncClient->listQueues() as $queue) {
            $this->object->listBindingsByQueue($queue)
            ->then(function($bindings) use ($loop, $queue, $PHPUnit) {
                foreach ($bindings as $binding) {
                    /* @var $binding Binding */
                    $PHPUnit->assertEquals('/', $binding->vhost);
                    $PHPUnit->assertEquals($queue->name, $binding->destination);
                }
                $loop->stop();
            }, function() use ($PHPUnit) {
                $PHPUnit->fail('Should not fail');
            });
        }

        $loop->run();
    }

    public function testListBindingsByQueueFailed()
    {
        $loop = $this->loop;
        $PHPUnit = $this;
        $success = false;

        $queue = new Queue();
        $queue->name = 'nonexistent queue';
        $queue->vhost = self::NONEXISTENT_VIRTUAL_HOST;

        $this->object->listBindingsByQueue($queue)
            ->then(function() use ($PHPUnit, $loop) {
                $PHPUnit->fail('Should not success');
            }, function() use (&$success, $loop) {
                $success = true;
                $loop->stop();
            });

        $loop->run();
    }

    public function testListBindingsByExchangeAndQueue()
    {
        $loop = $this->loop;
        $PHPUnit = $this;
        $success = false;

        foreach ($this->syncClient->listQueues() as $queue) {
            foreach ($this->syncClient->listExchanges() as $exchange) {
                if ($exchange->name == '') {
                    continue;
                }

                $this->object->listBindingsByExchangeAndQueue(self::VIRTUAL_HOST, $exchange->name, $queue->name)
                    ->then(function($bindings) use (&$success, $loop, $queue, $exchange, $PHPUnit) {
                        foreach ($bindings as $binding) {
                            $PHPUnit->assertEquals('/', $binding->vhost);
                            $PHPUnit->assertEquals($queue->name, $binding->destination);
                            $PHPUnit->assertEquals($exchange->name, $binding->source);
                        }
                        $success = true;
                        $loop->stop();
                    }, function() use ($PHPUnit) {
                        $PHPUnit->fail('Should not fail');
                    });

                foreach ($this->object->listBindingsByExchangeAndQueue('/', $exchange->name, $queue->name) as $binding) {
                    /* @var $binding Binding */
                    $this->assertEquals('/', $binding->vhost);
                    $this->assertEquals($queue->name, $binding->destination);
                    $this->assertEquals($exchange->name, $binding->source);
                }
            }
        }

        $loop->run();
        $this->assertTrue($success);
    }

    public function testListBindingsByExchangeAndQueueFailed()
    {
        $loop = $this->loop;
        $PHPUnit = $this;
        $success = false;

        $this->object->listBindingsByExchangeAndQueue(self::NONEXISTENT_VIRTUAL_HOST, self::EXCHANGE_TEST_NAME, self::QUEUE_TEST_NAME)
            ->then(function($bindings) {
                $PHPUnit->fail('Should not success');
            }, function() use ($PHPUnit, $loop, &$success) {
                $success = true;
                $loop->stop();
            });

        $loop->run();

        $this->assertTrue($success);
    }

    public function testAddBinding()
    {
        $loop = $this->loop;
        $PHPUnit = $this;
        $success = false;
        $client = $this->object;

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

        $this->object->addBinding($binding)
            ->then(function($res) use ($client, &$success, $loop, $PHPUnit) {
                $client->listBindingsByExchangeAndQueue('/', $PHPUnit::EXCHANGE_TEST_NAME, $PHPUnit::QUEUE_TEST_NAME)
                ->then(function($bindings) use ($client, &$success, $loop) {
                    foreach ($bindings as $binding) {
                        if ($binding->routing_key === 'rounting.key' && $binding->arguments === array('bim' => 'boom')) {
                            $success = true;

                            $found = $binding;
                            break;
                        }
                    }
                    $client->deleteBinding($binding)
                    ->then(function() use ($loop) {
                        $loop->stop();
                    });
                }, function() use ($PHPUnit) {
                    $PHPUnit->fail('Should not fail');
                });
            }, function($error) use ($PHPUnit) {
                $PHPUnit->fail('Should not fail');
            });

            $loop->run();
            $this->assertTrue($success);
    }

    public function testAddBindingFailed()
    {
        $binding = new Binding();
        $binding->vhost = '/';
        $binding->source = self::EXCHANGE_TEST_NAME;
        $binding->destination = self::QUEUE_TEST_NAME;


        $loop = $this->loop;
        $PHPUnit = $this;
        $success = false;

        $this->object->addBinding($binding)
            ->then(function() use ($PHPUnit) {
                $PHPUnit->fail('Should no success');
            }, function() use ($loop, &$success) {
                $success = true;
                $loop->stop();
            });

        $loop->run();
        $this->assertTrue($success);
    }

    public function testDeleteBinding()
    {
        $this->createBinding();

        $loop = $this->loop;
        $PHPUnit = $this;
        $success = false;
        $client = $this->object;

        $this->object->listBindingsByExchangeAndQueue('/', self::EXCHANGE_TEST_NAME, self::QUEUE_TEST_NAME)
            ->then(function($bindings) use ($client, &$success, $loop) {
                $found = false;
                foreach ($bindings as $binding) {
                    if ($binding->routing_key == 'rounting.key') {
                        $found = $binding;
                        break;
                    }
                }
                $client->deleteBinding($found)
                ->then(function() use ($client, &$success, $loop, $PHPUnit) {
                    $client->listBindingsByExchangeAndQueue('/', $PHPUnit::EXCHANGE_TEST_NAME, $PHPUnit::QUEUE_TEST_NAME)
                    ->then(function($bindings) use ($client, &$success, $loop, $PHPUnit) {
                            $found = false;

                            foreach ($bindings as $binding) {
                                if ($binding->routing_key == 'rounting.key') {
                                    $found = true;
                                }
                            }

                            if ($found) {
                                $PHPUnit->fail('unable to find delete the binding');
                            }
                            $success = true;
                            $loop->stop();
                        }, function() {
                            $PHPUnit->fail('Should no failed');
                        });
                }, function() {
                    $PHPUnit->fail('Should no failed');
                });
            }, function() use ($PHPUnit) {
                $PHPUnit->fail('Should no failed');
            });

        $loop->run();
        $this->assertTrue($success);
    }

    private function createBinding()
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
        $binding->routing_key = 'rounting.key';

        $this->syncClient->addBinding($binding);

        return $binding;
    }

    public function testDeleteNonexistentBinding()
    {
        $loop = $this->loop;
        $PHPUnit = $this;
        $success = false;

        $binding = new Binding();
        $binding->vhost = self::VIRTUAL_HOST;
        $binding->source = self::EXCHANGE_TEST_NAME;
        $binding->destination = self::QUEUE_TEST_NAME;
        $binding->properties_key = 'bingo';

        $this->object->deleteBinding($binding)
            ->then(function() use ($PHPUnit) {
                $PHPUnit->fail('Should no success');
            }, function() use ($loop, &$success) {
                $success = true;
                $loop->stop();
            });

        $loop->run();
        $this->assertTrue($success);
    }

    public function testListBindings()
    {
        $this->createBinding();

        $loop = $this->loop;
        $PHPUnit = $this;
        $success = false;

        $this->object->listBindings()
            ->then(function($bindings) use (&$success, $PHPUnit, $loop) {
                $PHPUnit->assertNonEmptyArrayCollection($bindings);
                foreach ($bindings as $binding) {
                    $PHPUnit->assertInstanceOf('RabbitMQ\Management\Entity\Binding', $binding);
                }
                $success = true;
                $loop->stop();
            }, function() use ($PHPUnit) {
                $PHPUnit->fail('Should no fail');
            });

        $loop->run();
        $this->assertTrue($success);
    }

    public function testListBindingsWithVhost()
    {
        $this->createBinding();

        $loop = $this->loop;
        $PHPUnit = $this;
        $success = false;

        $this->object->listBindings(self::VIRTUAL_HOST)
            ->then(function($bindings) use (&$success, $PHPUnit, $loop) {
                $PHPUnit->assertNonEmptyArrayCollection($bindings);
                foreach ($bindings as $binding) {
                    $PHPUnit->assertInstanceOf('RabbitMQ\Management\Entity\Binding', $binding);
                }
                $success = true;
                $loop->stop();
            }, function() use ($PHPUnit) {
                $PHPUnit->fail('Should no fail');
            });

        $loop->run();
        $this->assertTrue($success);
    }

    public function testAlivenessTest()
    {
        $loop = $this->loop;
        $PHPUnit = $this;
        $success = false;

        $this->object->alivenessTest(self::VIRTUAL_HOST)
            ->then(function($result) use ($PHPUnit, &$success){
                $PHPUnit->assertTrue($result);
                $success = true;
            }, function() use ($PHPUnit) {
                $PHPUnit->fail('Should no fail');
            });

        $loop->run();
        $this->assertTrue($success);
    }


    public function testPurgeQueue()
    {
        $loop = $this->loop;
        $PHPUnit = $this;
        $success = false;
        $client = $this->object;

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
            'routing_key'      => self::QUEUE_TEST_NAME,
            'payload'          => 'body',
            'payload_encoding' => 'string',
            ));

        $n = 12;
        while ($n > 0) {
            $this->syncClientClient->post('/api/exchanges/' . urlencode('/') . '/' . self::EXCHANGE_TEST_NAME . '/publish', array('content-type' => 'application/json'), $message)->send();
            $n--;
        }

        usleep(2000000);
        $this->syncClient->refreshQueue($queue);
        $this->assertEquals(12, $queue->messages_ready);

        $this->object->purgeQueue('/', self::QUEUE_TEST_NAME)
            ->then(function() use ($client, &$success, $PHPUnit, $queue){

                usleep(4000000);
                $client->refreshQueue($queue);
                $success = true;
                $PHPUnit->assertEquals(0, $queue->messages_ready);
                $loop->stop();
            }, function() use ($PHPUnit) {
                $PHPUnit->fail('Should no fail');
            });

        $loop->run();
        $this->assertTrue($success);
    }

    public function assertNonEmptyArrayCollection($collection)
    {
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $collection);
        $this->assertGreaterThan(0, count($collection), 'Collection is not empty');
    }
}
