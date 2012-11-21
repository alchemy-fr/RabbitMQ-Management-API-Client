# RabbitMQ Manager API Client

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

Browse the API [here]().

# Documentation

Read the documentation at [Read The Docs]() !

# License

This library is released under the MIT license (use it baby !)



