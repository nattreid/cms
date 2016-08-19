<?php

namespace Nattreid\Crm\Model;

/**
 * Configuration Repository
 * 
 * @author Attreid <attreid@gmail.com>
 */
class ConfigurationRepository extends \NAttreid\Orm\Repository {

    public static function getEntityClassNames() {
        return [Configuration::class];
    }

    /**
     * Vrati hodnotu
     * @param string $name
     * @return Configuration
     */
    public function get($name) {
        return $this->getBy(['name' => $name]);
    }

}
