# RabbitMQ Manager API Client

[![Build Status](https://secure.travis-ci.org/alchemy-fr/RabbitMQ-Management-API-Client.png?branch=master)](https://travis-ci.org/alchemy-fr/RabbitMQ-Management-API-Client)

**Update:** This branch is an update to this library to allow it to work with modern (circa 2019) versions of
PHP (>=7.2), RabbitMQ, Guzzle, React, Doctrine, PHPUnit and related support libraries. (see composer.json for 
version details) The existing version had too many dependencies on deprecated versions of other libraries
preventing it from being utilized on new projects.  This update fixes that. 
 
Extensive changes were needed under the hood but the API should remain nearly 
identical with the exception of the exceptions thrown from the non-async client code.  

Cases involving a non-existent entity (now including Add methods, such as trying to add a binding to a 
non-existant vhost) are now uniformly thrown as RabbitMQ&#92;Management&#92;Exception&#92;EntityNotFoundException
where previously some were RabbitMQ&#92;Management&#92;Exception&#92;RuntimeException.   Other exceptions from the 
underlying Guzzle library are now passed through as GuzzleHttp&#92;Exception&#92;ClientException rather than
GuzzleHttp&#92;Exception&#92;RequestException due to changes in Guzzle. 

*(end update message)*

This library is intended to help management of RabbitMQ server in an application.
It provides two ways to query RabbitMQ : Synchronous query with Guzzle and
Asynchronous query with React.

## Asynchronous Queries

```php

use RabbitMQ\Management\AsyncAPIClient;
use React\EventLoop\Factory;

$loop = Factory::create();

$client = AsyncAPIClient::factory($loop, array(
    'url'      => '127.0.0.1',
));

$loop->addPeriodicTimer(1, function () use ($client) {
    $client->listQueues()
        ->then(function($queues) {
            echo "\n------------\n";
            foreach ($queues as $queue) {
                echo sprintf("Found queue %s with %d messages\n", $queue->name, $queue->messages);
            }
        }, function ($error) {
            echo "An error occured : $error\n";
        });
});

$loop->run();
```

Asynchronous Client do not currently support Guarantee API.

## Synchronous Queries

Ensure a queue has a flag :

```php
use RabbitMQ\Management\APIClient;
use RabbitMQ\Management\Entity\Queue;
use RabbitMQ\Management\Exception\EntityNotFoundException;

$client = APIClient::factory(array('url'=>'localhost'));

try {
    $queue = $client->getQueue('/', 'queue.leuleu');

    if (true !== $queue->durable) {
        $queue->durable = true;

        $client->deleteQueue('/', 'queue.leuleu');
        $client->addQueue($queue);
    }

} catch (EntityNotFoundException $e) {
    $queue = new Queue();
    $queue->vhost = '/';
    $queue->name = 'queue.leuleu';
    $queue->durable = true;

    $client->addQueue($queue);
}
```

You can also use the Guarantee manager :

```php
use RabbitMQ\Management\APIClient;
use RabbitMQ\Management\Entity\Queue;
use RabbitMQ\Management\Guarantee;

$client = APIClient::factory(array('url'=>'localhost'));
$manager = new Guarantee($client);

$queue = new Queue();
$queue->vhost = '/';
$queue->name = 'queue.leuleu';
$queue->durable = true;
$queue->auto_delete = false;

$queue = $manager->ensureQueue($queue);
```

# API Browser

Browse the API [here](https://rabbitmq-management-api-client.readthedocs.org/en/latest/_static/API/).

# Documentation

Read the documentation at [Read The Docs](https://rabbitmq-management-api-client.readthedocs.org) !

# License

This library is released under the MIT license (use it baby !)



