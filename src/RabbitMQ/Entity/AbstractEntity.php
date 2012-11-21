<?php

namespace RabbitMQ\Entity;

use RabbitMQ\APIClient;

abstract class AbstractEntity implements EntityInterface
{
    /**
     * @var APIClient
     */
    protected $APIClient;

    public function setAPIClient(APIClient $client)
    {
        $this->APIClient = $client;
    }

    public function toJson()
    {
        $data = array();

        foreach ($this as $key => $value) {
            if ($key === 'APIClient') {
                continue;
            }
            if (null === $value) {
                continue;
            }

            $data[$key] = $value;
        }

        return json_encode($data);
    }
}
