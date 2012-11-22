<?php

namespace RabbitMQ\Management\Entity;

class Exchange extends AbstractEntity
{
    public $name;
    public $vhost;
    public $type;
    public $durable;
    public $auto_delete;
    public $internal;
    public $arguments;

    protected function getJsonParameters()
    {
        return array('type', 'auto_delete', 'durable', 'internal', 'arguments');
    }
}
