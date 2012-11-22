<?php

namespace RabbitMQ\Management\Entity;

class Binding extends AbstractEntity
{
    public $source;
    public $vhost;
    public $destination;
    public $destination_type;
    public $routing_key;
    public $arguments = array();
    public $properties_key;

    protected function getJsonParameters()
    {
        return array('routing_key', 'arguments', 'vhost', 'source', 'destination');
    }
}
