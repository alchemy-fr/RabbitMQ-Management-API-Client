<?php

namespace RabbitMQ\Management\Entity;

class Exchange extends AbstractEntity
{
    public $name;
    public $vhost;
    public $type;
    public $durable = false;
    public $auto_delete = false;
    public $internal = false;
    public $arguments = array();

    protected function getJsonParameters()
    {
        return array('type', 'auto_delete', 'durable', 'internal', 'arguments');
    }
}
