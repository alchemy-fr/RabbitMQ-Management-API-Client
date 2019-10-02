<?php

namespace RabbitMQ\Management;

use RabbitMQ\Management\Entity\EntityInterface;

class Hydrator
{
    private static $audit=false;
    private static $lastAuditStatus=false;
    private static $instance;

    public static function getInstance()
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /* notice: not thread safe! */
    public static function requestOneShotAudit($delay) {
        self::$audit=true;
        self::$lastAuditStatus=false;
        sleep($delay);
    }

    public static function getLastAuditStatus() {
        return self::$lastAuditStatus;
    }

    public function hydrate(EntityInterface $entity, $data)
    {
        if (self::$audit) {
            self::$lastAuditStatus=true;
            $shortClassName = preg_replace("/.+\\\\/", "", get_class($entity));
            $expectedProps = get_class_vars(get_class($entity));
        }

        foreach ($data as $key => $value) {
            /*
            if (!property_exists($entity, $key)) {
                throw new \InvalidArgumentException(sprintf('Entity %s does not have property %s', get_class($entity), $key));
            }
            */
            if (self::$audit) {
                if (!property_exists($entity, $key)) {
                    printf("class %s missing public \$%s;\n", $shortClassName, $key);
                    self::$lastAuditStatus=false;
                } else {
                    $expectedProps[$key] = true;
                }
            }

            $entity->{$key} = $value;
        }

        if (self::$audit) {
            foreach ($expectedProps as $key => $value) {
                if ($value === true) {
                    continue;
                }
                printf("class %s didn't use %s\n",$shortClassName,$key);
                /* warn but don't fail on audit for use against old servers */
                /* self::$lastAuditStatus=false; */
            }
            self::$audit=false;
        }

        return $entity;
    }

    public static function camelize($attribute)
    {
        return implode('', array_map(function ($part) {
            return ucfirst($part);
        }, explode('_', $attribute)));
    }
}
