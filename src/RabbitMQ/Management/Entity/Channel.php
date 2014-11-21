<?php

namespace RabbitMQ\Management\Entity;

class Channel extends AbstractEntity
{
    public $connection_details;
    public $consumer_details = array();
    public $idle_since;
    public $transactional;
    public $confirm;
    public $consumer_count;
    public $messages_unacknowledged;
    public $messages_unconfirmed;
    public $messages_uncommitted;
    public $message_stats;
    public $acks_uncommitted;
    public $publishes;
    public $prefetch_count;
    public $client_flow_blocked;
    public $node;
    public $name;
    public $number;
    public $user;
    public $vhost;
    public $deliveries = array();
    public $global_prefetch_count;
    public $state;

    protected function getJsonParameters()
    {
        return array();
    }
}
