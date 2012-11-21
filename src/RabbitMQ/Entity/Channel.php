<?php

namespace RabbitMQ\Entity;

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
    public $acks_uncommitted;
    public $prefetch_count;
    public $client_flow_blocked;
    public $node;
    public $name;
    public $number;
    public $user;
    public $vhost;
}
