<?php

namespace RabbitMQ\Management\Entity;

class Queue extends AbstractEntity
{
    public $arguments = array();
    public $auto_delete = false;
    public $durable = false;
    public $name;
    public $vhost;
    public $status;

    public $node;
    public $policy;
    public $active_consumers;
    public $messages_unacknowledged_details;
    public $messages_ready_details;
    public $messages_details;
    public $backing_queue_status;
    public $slave_nodes;
    public $consumers;
    public $consumer_details = array();
    public $consumer_utilisation;
    public $state;
    public $messages;
    public $messages_ready;
    public $message_stats;
    public $messages_unacknowledged;
    public $exclusive_consumer_tag;
    public $memory;
    public $idle_since;
    public $incoming = array();
    public $deliveries = array();

    public function getBindings()
    {
        $this->APIClient->listBindingsByQueue($this);
    }

    protected function getJsonParameters()
    {
        return array('auto_delete', 'durable', 'arguments', 'vhost', 'name');
    }
}
