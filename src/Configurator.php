<?php

namespace NAttreid\Crm;

use NAttreid\Crm\Model\Orm,
    Nextras\Orm\Model\Model,
    Nette\Caching\IStorage,
    Nette\Caching\Cache,
    NAttreid\Crm\Model\Configuration,
    NAttreid\AppManager\AppManager;

/**
 * Nastaveni aplikace
 *
 * @property boolean $sendNewUserPassword zaslat novemu uzivateli heslo mailem
 * @property boolean $sendChangePassword zaslat uzivateli zmenene heslo mailem
 * @property boolean $dockbarAdvanced povolit rozsirene moznosti v dockbaru (mazani databaze, atd )
 * @property string $logo logo
 * @property string $defaultLang nastaveni defaultniho jazyka
 * @property array $allowedLang povolene jazyky
 * @property boolean $mailPanel Mail panel misto zasilani mailu
 * 
 * @property boolean $cookiePolicy potvrzeni pouzivani cookie
 * @property string $cookiePolicyLink link pro informace o pouzivani cookie
 * @property string $keywords klicova slova
 * @property string $description popis
 * 
 * @author Attreid <attreid@gmail.com>
 */
class Configurator {

    public $lang = [
        'cs' => 'cs',
        'en' => 'en'
    ];
    private $default = [
        'sendNewUserPassword' => TRUE,
        'sendChangePassword' => TRUE,
        'dockbarAdvanced' => FALSE,
        'mailPanel' => FALSE,
        'defaultLang' => 'cs',
        'allowedLang' => ['cs', 'en']
    ];
    private $tag = 'cache/configuration';

    /** @var Orm */
    private $orm;

    /** @var Cache */
    private $cache;

    public function __construct(Model $orm, IStorage $storage, AppManager $app) {
        $this->orm = $orm;
        $this->cache = new Cache($storage, 'nattreid-crm-configurator');
        $app->onInvalidateCache[] = [$this, 'cleanCache'];
        $orm->configuration->onFlush[] = function($persisted, $removed) {
            if (!empty($persisted) || !empty($removed)) {
                $this->cleanCache();
            }
        };
    }

    /**
     * Smaze cache
     */
    public function cleanCache() {
        $this->cache->clean([
            Cache::TAGS => [$this->tag]
        ]);
    }

    public function __get($name) {
        $key = 'cache_configuration_' . $name;

        $result = $this->cache->load($key);
        if ($result === NULL) {
            $result = $this->cache->save($key, function() use ($name) {
                /* @var $configuration Model\Configuration */
                $configuration = $this->orm->configuration->get($name);
                if ($configuration) {
                    return $configuration->value;
                } else {
                    if (isset($this->default[$name])) {
                        return $this->$name = $this->default[$name];
                    }
                }
                return FALSE;
            }, [
                Cache::TAGS => [$this->tag]
            ]);
        }
        return $result;
    }

    public function __set($name, $value) {
        $configuration = $this->orm->configuration->getById($name);
        if ($configuration === NULL) {
            $configuration = new Configuration;
        }
        $configuration->name = $name;
        $configuration->value = $value;
        $this->orm->persistAndFlush($configuration);
    }

    /** @return array */
    public function fetchConfigurations() {
        return $this->orm->configuration->findAll()->fetchPairs('name', 'value');
    }

}
