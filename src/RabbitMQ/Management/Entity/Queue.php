<?php

namespace RabbitMQ\Management\Entity;

class Queue extends AbstractEntity
{

    public $active_consumers;
    public $arguments = array();
    public $auto_delete = false;
    public $backing_queue_status;
    public $consumer_details = array();
    public $consumer_utilisation;
    public $consumers;
    public $deliveries = array();
    public $disk_reads;
    public $disk_writes;
    public $down_slave_nodes;
    public $durable = false;
    public $effective_policy_definition;
    public $exclusive;
    public $exclusive_consumer_tag;
    public $garbage_collection;
    public $head_message_timestamp;
    public $idle_since;
    public $incoming = array();
    public $memory;
    public $message_bytes;
    public $message_bytes_paged_out;
    public $message_bytes_persistent;
    public $message_bytes_ram;
    public $message_bytes_ready;
    public $message_bytes_unacknowledged;
    public $message_stats;
    public $messages;
    public $messages_details;
    public $messages_paged_out;
    public $messages_persistent;
    public $messages_ram;
    public $messages_ready;
    public $messages_ready_details;
    public $messages_ready_ram;
    public $messages_unacknowledged;
    public $messages_unacknowledged_details;
    public $messages_unacknowledged_ram;
    public $name;
    public $node;
    public $operator_policy;
    public $policy;
    public $recoverable_slaves = array();
    public $reductions;
    public $reductions_details;
    public $single_active_consumer_tag;
    public $slave_nodes;
    public $state;

    public $synchronised_slave_nodes = array();
    public $type;
    public $vhost;



    public function getBindings()
    {
        $this->APIClient->listBindingsByQueue($this);
    }

    protected function getJsonParameters()
    {
        return array('auto_delete', 'durable', 'arguments', 'vhost', 'name');
    }
}
