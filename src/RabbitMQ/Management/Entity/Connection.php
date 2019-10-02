<?php

namespace RabbitMQ\Management\Entity;

class Connection extends AbstractEntity
{
    public $address;
    public $auth_mechanism;
    public $channel_max;
    public $channels;
    public $client_properties;
    public $connected_at;
    public $frame_max;
    public $host;
    public $last_blocked_age;
    public $last_blocked_by;
    public $name;
    public $node;
    public $peer_address;
    public $peer_cert_issuer;
    public $peer_cert_subject;
    public $peer_cert_validity;
    public $peer_host;
    public $peer_port;
    public $port;
    public $protocol;
    public $recv_cnt;
    public $recv_oct;
    public $recv_oct_details;
    public $send_cnt;
    public $send_oct;
    public $send_oct_details;
    public $send_pend;
    public $ssl;
    public $ssl_cipher;
    public $ssl_hash;
    public $ssl_key_exchange;
    public $ssl_protocol;
    public $state;
    public $timeout;
    public $type;
    public $user;
    public $vhost;
    public $garbage_collection;
    public $reductions;
    public $reductions_details;
    public $user_who_performed_action;

    protected function getJsonParameters()
    {
        return array();
    }
}
