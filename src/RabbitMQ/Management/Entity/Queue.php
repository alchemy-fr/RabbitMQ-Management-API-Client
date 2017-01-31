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
    public $down_slave_nodes;
    public $messages_ram;
    public $messages_ready_ram;
    public $messages_unacknowledged_ram;
    public $messages_persistent;
    public $message_bytes;
    public $message_bytes_ready;
    public $message_bytes_unacknowledged;
    public $message_bytes_ram;
    public $message_bytes_persistent;
    public $reductions;
    public $reductions_details;
    public $synchronised_slave_nodes = array();
    public $recoverable_slaves = array();
    public $garbage_collection;
    public $head_message_timestamp;
    public $disk_reads;
    public $disk_writes;
    public $exclusive;

    public function getBindings()
    {
        $this->APIClient->listBindingsByQueue($this);
    }

    protected function getJsonParameters()
    {
        return array('auto_delete', 'durable', 'arguments', 'vhost', 'name');
    }
}
