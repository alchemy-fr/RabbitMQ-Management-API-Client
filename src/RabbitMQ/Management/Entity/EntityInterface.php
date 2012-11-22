<?php

namespace RabbitMQ\Management\Entity;

use RabbitMQ\Management\APIClient;

interface EntityInterface
{
    public function toJson();
    public function setAPIClient(APIClient $client);
}
