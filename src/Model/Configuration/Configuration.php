<?php

namespace Nattreid\Crm\Model;

/**
 * Configuration
 * 
 * @property int $id {primary-proxy}
 * @property string $name {primary}
 * @property string $serializedValue
 * @property mixed|NULL $value {virtual}
 * 
 * @author Attreid <attreid@gmail.com>
 */
class Configuration extends \Nextras\Orm\Entity\Entity {

    protected function getterValue() {
        return unserialize($this->serializedValue);
    }

    protected function setterValue($value) {
        $this->serializedValue = serialize($value);
        return $value;
    }

}
