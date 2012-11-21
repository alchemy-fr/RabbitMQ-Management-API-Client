<?php

namespace RabbitMQ\Entity;

use RabbitMQ\APIClient;

interface EntityInterface
{
    public function toJson();
    public function setAPIClient(APIClient $client);
}
