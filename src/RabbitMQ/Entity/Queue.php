<?php

namespace RabbitMQ\Entity;

class Queue extends AbstractEntity
{
    public $arguments;
    public $auto_delete;
    public $durable;
    public $name;
    public $vhost;
    public $node;
    public $messages_unacknowledged_details;
    public $messages_ready_details;
    public $messages_details;
    public $backing_queue_status;
    public $slave_nodes;
    public $consumers;
    public $consumer_details = array();
    public $messages;
    public $messages_ready;
    public $message_stats;
    public $messages_unacknowledged;
    public $exclusive_consumer_tag;
    public $memory;
    public $idle_since;

    public function getBindings()
    {
        $this->APIClient->listBindingsByQueue($this);
    }
}
