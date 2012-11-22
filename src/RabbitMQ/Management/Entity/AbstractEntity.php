<?php

namespace RabbitMQ\Management\Entity;

use RabbitMQ\Management\APIClient;

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

        $parameters = $this->getJsonParameters();

        foreach ($this as $key => $value) {
            if (!in_array($key, $parameters)) {
                continue;
            }
            if (null === $value) {
                continue;
            }

            $data[$key] = $value;
        }

        return json_encode((object) $data);
    }

    abstract protected function getJsonParameters();
}
