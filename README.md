# RabbitMQ Manager API Client

[![Build Status](https://secure.travis-ci.org/alchemy-fr/RabbitMQ-Management-API-Client.png?branch=master)](https://travis-ci.org/alchemy-fr/RabbitMQ-Management-API-Client)

This library is intended to help management of RabbitMQ server in an application.

# Example of use :

Ensure a queue has a flag :

```php
use RabbitMQ\APIClient;
use RabbitMQ\Entity\Queue;
use RabbitMQ\Exception\EntityNotFoundException;

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

# Todo

 - Add guarantee constraints

# API Browser

Browse the API [here](https://rabbitmq-management-api-client.readthedocs.org/en/latest/_static/API/).

# Documentation

Read the documentation at [Read The Docs](https://rabbitmq-management-api-client.readthedocs.org) !

# License

This library is released under the MIT license (use it baby !)



