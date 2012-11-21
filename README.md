# RabbitMQ Manager API Client

[![Build Status](https://secure.travis-ci.org/alchemy-fr/RabbitMQ-Management-API-Client.png?branch=master)](https://travis-ci.org/alchemy-fr/RabbitMQ-Management-API-Client)

This library is intended to help management of RabbitMQ server in an application.

Exemple of use :

```php
use RabbitMQ\APIClient;

$client = APIClient::factory(array('url'=>'localhost'));

foreach ($client->listQueues() as $queue) {
    echo $queue->name;
}
```

# API Browser

Browse the API [here](https://rabbitmq-management-api-client.readthedocs.org/en/latest/_static/API/).

# Documentation

Read the documentation at [Read The Docs](https://rabbitmq-management-api-client.readthedocs.org) !

# License

This library is released under the MIT license (use it baby !)



