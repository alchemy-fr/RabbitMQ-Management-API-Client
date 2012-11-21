<?php

namespace RabbitMQ\Entity;

class Exchange extends AbstractEntity
{
    public $name;
    public $vhost;
    public $type;
    public $durable;
    public $auto_delete;
    public $internal;
    public $arguments;
}
