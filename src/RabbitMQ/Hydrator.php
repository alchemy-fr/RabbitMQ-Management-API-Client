<?php

namespace RabbitMQ;

use RabbitMQ\Entity\EntityInterface;

class Hydrator
{
    private static $instance;

    public static function getInstance()
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function hydrate(EntityInterface $entity, $data)
    {
        foreach ($data as $key => $value) {
            if (!property_exists($entity, $key)) {
                throw new \InvalidArgumentException(sprintf('Entity %s does not have property %s', get_class($entity), $key));
            }
            $entity->{$key} = $value;
        }

        return $entity;
    }

    public static function camelize($attribute)
    {
        return implode('', array_map(function($part) {
                        return ucfirst($part);
                    }, explode('_', $attribute)));
    }
}
