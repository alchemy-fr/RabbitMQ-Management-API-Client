<?php

namespace RabbitMQ\Management\Entity;

class Exchange extends AbstractEntity
{
    public $name;
    public $vhost;
    public $type;
    public $incoming;
    public $outgoing;
    public $message_stats_in;
    public $message_stats_out;
    public $durable = false;
    public $auto_delete = false;
    public $internal = false;
    public $arguments = array();

    protected function getJsonParameters()
    {
        return array('type', 'auto_delete', 'durable', 'internal', 'arguments', 'vhost', 'name');
    }
}
