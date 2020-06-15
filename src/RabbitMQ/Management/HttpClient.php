<?php

namespace RabbitMQ\Management;

use GuzzleHttp\Client;
use RabbitMQ\Management\Exception\RuntimeException;

class HttpClient extends Client
{
    private $hydrator;

    public function getHydrator()
    {
        if (!$this->hydrator) {
            $this->hydrator = new Hydrator();
        }

        return $this->hydrator;
    }

    /**
     * Factory method to create the HttpClient
     *
     * The following array keys and values are available options:
     * - url     : Base URL of web service
     * - port    : Port of web service
     * - scheme  : URI scheme: http or https
     * - username: API username
     * - password: API password
     *
     * @param array|Collection $options Configuration data
     *
     * @return self
     */
    public static function factory($options = array())
    {
        $default = array(
            'base_uri' => '{scheme}://{username}:{password}@{host}:{port}/',
            'scheme' => 'http',
            'username' => 'guest',
            'password' => 'guest',
            'port' => '15672',
        );

        /* accommodate legacy clients of this library using base_url where Guzzle now wants base_uri */
        if(array_key_exists('base_url',$options) and !array_key_exists('base_uri',$options)) {
            $options['base_uri']=$options['base_url'];
            unset($options['base_url']);
        }

        $required = array('username', 'password', 'host', 'base_uri');
        $config = array_merge($default,$options);
        if ($missing = array_diff($required, array_keys($config))) {
            throw new RuntimeException('Config is missing the following keys: ' . implode(', ', $missing));
        }

        foreach ($config as $key => $value) {
            if ($key === 'base_uri') {
                continue;
            }
            $config['base_uri'] = str_replace('{' . $key . '}', $value, $config['base_uri']);
        }


        $client = new self($config);


        return $client;
    }
}
