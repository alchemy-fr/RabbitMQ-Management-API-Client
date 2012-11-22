<?php

include __DIR__ . '/../../vendor/autoload.php';


$client = \RabbitMQ\HttpClient::factory(array('url'=>'localhost'));
//
//
//$description = ServiceDescription::factory('serviceDescription.json');
//$client->setDescription($description);

$client = new \RabbitMQ\APIClient($client);


    use RabbitMQ\Exception\EntityNotFoundException;
    use RabbitMQ\Entity\Queue;

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





    use RabbitMQ\Entity\Exchange;

    try {
        $exchange = $client->getExchange('/', 'airport');

        if (true !== $exchange->durable) {
            $exchange->durable = true;

            $client->deleteExchange('/', 'airport');
            $client->addExchange($exchange);
        }

    } catch (EntityNotFoundException $e) {
        $exchange = new Exchange();
        $exchange->vhost = '/';
        $exchange->name = 'airport';
        $exchange->durable = true;
        $exchange->type = 'direct';

        $client->addExchange($exchange);
    }

    use RabbitMQ\Entity\Binding;

    $vhost = '/';
    $exchange_name = 'airport';
    $queue_name = 'queue.leuleu';
    $routing_key = 'pretty.routing';

    $bindings = $client->listBindingsByExchangeAndQueue($vhost, $exchange_name, $queue_name);
    $found = false;

    foreach ($bindings as $binding) {
        if ($routing_key === $binding->routing_key) {
            $found = true;
            break;
        }
    }

    if (!$found) {
        $binding = new Binding();
        $binding->routing_key = $routing_key;

        $client->addBinding($vhost, $exchange_name, $queue_name, $binding);
    }
