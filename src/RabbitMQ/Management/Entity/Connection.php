<?php

namespace RabbitMQ\Management\Entity;

class Connection extends AbstractEntity
{
    public $host;
    public $peer_host;
    public $recv_oct;
    public $recv_oct_details;
    public $recv_cnt;
    public $send_oct;
    public $send_oct_details;
    public $send_cnt;
    public $send_pend;
    public $state;
    public $last_blocked_by;
    public $last_blocked_age;
    public $channels;
    public $type;
    public $node;
    public $name;
    public $address;
    public $port;
    public $peer_address;
    public $peer_port;
    public $ssl;
    public $peer_cert_subject;
    public $peer_cert_issuer;
    public $peer_cert_validity;
    public $auth_mechanism;
    public $ssl_protocol;
    public $ssl_key_exchange;
    public $ssl_cipher;
    public $ssl_hash;
    public $protocol;
    public $user;
    public $vhost;
    public $timeout;
    public $frame_max;
    public $client_properties;
    public $channel_max;

    protected function getJsonParameters()
    {
        return array();
    }
}
