RabbitMQ Management API Client Documentation
============================================

Introduction
------------

RabbitMQ Managemenet API Client is an object oriented PHP client for the `RabbitMQ
Management API <http://hg.rabbitmq.com/rabbitmq-management/raw-file/3646dee55e02/priv/www-api/help.html>`_
provided by the `RabbitMQ Management Plugin <http://www.rabbitmq.com/management.html>`_

This library depends on `Guzzle <https://guzzlephp.org>`_
and `Doctrine Common <https://github.com/doctrine/common>`_.

Installation
------------

We rely on `composer <http://getcomposer.org/>`_ to use this library. If you do
no still use composer for your project, you can start with this ``composer.json``
at the root of your project:

.. code-block:: json

    {
        "require": {
            "alchemy/rabbitmq-management-client": "master"
        }
    }

Install composer :

.. code-block:: bash

    # Install composer
    curl -s http://getcomposer.org/installer | php
    # Upgrade your install
    php composer.phar install

You now just have to autoload the library to use it :

.. code-block:: php

    <?php
    require 'vendor/autoload.php';

This is a very short intro to composer.
If you ever experience an issue or want to know more about composer,
you will find help on their  website
`http://getcomposer.org/ <http://getcomposer.org/>`_.

Basic Usage
-----------

Here is a simple way to instantiate the APIClient :

.. code-block:: php

    <?php
    use RabbitMQ\APIClient;

    $client = APIClient::factory(array('url'=>'localhost'));

The APIClient factory requires the url option to build. Other available options
are :

 - scheme : The scheme to access the API endpoint (default to 'http')
 - port : The port number of the API endpoint (default to '55672')
 - username : The username to connect to the API endpoint (default to 'guest')
 - password : The password to connect to the API endpoint (default to 'guest')

Recipes
-------

These recipes are samples of code you could re-use.

Ensure a queue is set up with correct options
+++++++++++++++++++++++++++++++++++++++++++++

In the following example we want to ensure that a queue 'queue.leuleu' is
set-up on vhost '/' with durable flag set to true :

.. code-block:: php

    <?php
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

Ensure an exchange is set up with correct options
+++++++++++++++++++++++++++++++++++++++++++++++++

In the following example we want to ensure that an exchange of type 'direct',
named 'airport' is set-up on vhost '/' with durable flag set to true :

.. code-block:: php

    <?php
    use RabbitMQ\Exception\EntityNotFoundException;
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

Ensure a binding is set up
+++++++++++++++++++++++++++

In the following example we want to ensure that an exchange names 'airport' is
bound to a queue named 'queue.leuleu' with a given routing key 'love.routing' :

.. code-block:: php

    <?php
    use RabbitMQ\Entity\Binding;

    $vhost = '/';
    $exchange_name = 'airport';
    $queue_name = 'queue.leuleu';
    $routing_key = 'love.routing';

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

Handling Exceptions
-------------------

RabbitMQ Management API Client throws 4 different types of exception :

- ``RabbitMQ\Exception\EntityNotFoundException`` is thrown when an entity is not
  found.
- ``RabbitMQ\Exception\InvalidArgumentException`` is thrown when an invalid
  argument (name, vhost, ...) is provided
- ``RabbitMQ\Exception\PreconditionFailedException`` is thrown when you try to
  add an existing queue/exchange with different parameters (similar to HTTP 406).
- ``RabbitMQ\Exception\RuntimeException`` which extends SPL RuntimeException

All these Exception implements ``RabbitMQ\Exception\ExceptionInterface`` so you can catch
any of these exceptions by catching this exception interface.

Report a bug
------------

If you experience an issue, please report it in our
`issue tracker <https://github.com/alchemy-fr/RabbitMQ-Management-API-Client/issues>`_. Before
reporting an issue, please be sure that it is not already reported by browsing
open issues.

Ask for a feature
-----------------

We would be glad you ask for a feature ! Feel free to add a feature request in
the `issues manager <https://github.com/alchemy-fr/RabbitMQ-Management-API-Client/issues>`_ on GitHub !

Contribute
----------

You find a bug and resolved it ? You added a feature and want to share ? You
found a typo in this doc and fixed it ? Feel free to send a
`Pull Request <http://help.github.com/send-pull-requests/>`_ on GitHub, we will
be glad to merge your code.

Run tests
---------

RabbitMQ Management Client relies on
`PHPUnit <http://www.phpunit.de/manual/current/en/>`_ for unit tests.
To run tests on your system, ensure you have PHPUnit installed, and, at the
root of the project, execute it :

.. code-block:: bash

    phpunit

About
-----

RabbitMQ Management Client has been written by Romain Neutron @ `Alchemy <http://alchemy.fr/>`_
for `Gloubster <https://github.com/gloubster>`_.

License
-------

RabbitMQ Management API client is licensed under the
`MIT License <http://opensource.org/licenses/MIT>`_
