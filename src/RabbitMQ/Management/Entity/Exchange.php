<?php

namespace RabbitMQ\Management\Entity;

class Exchange extends AbstractEntity
{
    public $name;
    public $vhost;
    public $type;
    public $incoming = array();
    public $outgoing = array();
    public $message_stats_in;
    public $message_stats_out;
    public $durable = false;
    public $auto_delete = false;
    public $internal = false;
    public $arguments = array();
    public $message_stats = array();
    public $policy;
    public $user_who_performed_action;

    protected function getJsonParameters()
    {
        return array('type', 'auto_delete', 'durable', 'internal', 'arguments', 'vhost', 'name', 'policy');
    }
}
