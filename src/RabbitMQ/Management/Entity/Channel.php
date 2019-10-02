<?php

namespace RabbitMQ\Management\Entity;

class Channel extends AbstractEntity
{
    public $acks_uncommitted;
    public $client_flow_blocked;
    public $confirm;
    public $connection_details;
    public $consumer_count;
    public $consumer_details = array();
    public $deliveries = array();
    public $global_prefetch_count;
    public $idle_since;
    public $message_stats;
    public $messages_unacknowledged;
    public $messages_uncommitted;
    public $messages_unconfirmed;
    public $name;
    public $node;
    public $number;
    public $prefetch_count;
    public $publishes;
    public $state;
    public $transactional;
    public $user;
    public $vhost;
    public $garbage_collection;
    public $pending_raft_commands;
    public $reductions;
    public $reductions_details;
    public $user_who_performed_action;


    protected function getJsonParameters()
    {
        return array();
    }
}
