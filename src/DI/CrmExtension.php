<?php

namespace NAttreid\Crm\DI;

use NAttreid\Routing\RouterFactory,
    Nette\Utils\Strings,
    NAttreid\Crm\Control\BasePresenter,
    NAttreid\Crm\Control\Presenter,
    NAttreid\Crm\Mailing\Mailer,
    NAttreid\Security\Authenticator,
    NAttreid\TracyPlugin\Tracy,
    Kdyby\Translation\Translator,
    Nette\DI\Statement,
    IPub\FlashMessages\FlashNotifier;

/**
 * Rozsireni
 * 
 * @author Attreid <attreid@gmail.com>
 */
class CrmExtension extends \Nette\DI\CompilerExtension {

    public function loadConfiguration() {
        $builder = $this->getContainerBuilder();
        $config = $this->validateConfig($this->loadFromFile(__DIR__ . '/default.neon'), $this->config);

        if ($config['front'] === NULL) {
            throw new \Nette\InvalidStateException("Crm: 'front' does not set in config.neon");
        }

        $config['wwwDir'] = \Nette\DI\Helpers::expand($config['wwwDir'], $builder->parameters);
        $config['fileManagerDir'] = \Nette\DI\Helpers::expand($config['fileManagerDir'], $builder->parameters);
        $config['layout'] = \Nette\DI\Helpers::expand($config['layout'], $builder->parameters);

        $builder->addDefinition($this->prefix('dockbar'))
                ->setImplement(\NAttreid\Crm\Control\IDockbarFactory::class)
                ->setFactory(\NAttreid\Crm\Control\Dockbar::class)
                ->setArguments([$config['permissions'], $config['namespace'], $config['front']]);

        $builder->addDefinition($this->prefix('fileManagerFactory'))
                ->setImplement(\NAttreid\Filemanager\IFileManagerFactory::class)
                ->setFactory(\NAttreid\Filemanager\FileManager::class);

        $builder->addDefinition($this->prefix('router'))
                ->setClass(\NAttreid\Crm\Routing\Router::class)
                ->setArguments([$config['namespace'], $config['url'], $config['secured']]);

        $builder->addDefinition($this->prefix('configurator'))
                ->setClass(\NAttreid\Crm\Configurator::class);

        $builder->addDefinition($this->prefix('formFactory'))
                ->setClass(\NAttreid\Crm\Factories\FormFactory::class);

        $builder->addDefinition($this->prefix('dataGridFactory'))
                ->setClass(\NAttreid\Crm\Factories\DataGridFactory::class);

        $builder->addDefinition($this->prefix('authenticator'))
                ->setClass(\NAttreid\Security\Authenticator\MainAuthenticator::class)
                ->setAutowired(FALSE);

        $this->setLoader($config);
        $this->setPresenters($config);
        $this->setMenu($config);
        $this->setMailing($config);
    }

    private function setLoader($config) {
        $builder = $this->getContainerBuilder();

        $builder->addDefinition($this->prefix('loaderFactory'))
                ->setClass(\NAttreid\Crm\LoaderFactory::class)
                ->setArguments([$config['wwwDir']])
                ->addSetup('addFile', ['css/crm.boundled.min.css'])
                ->addSetup('addFile', ['js/crm.boundled.min.js'])
                ->addSetup('addFile', ['crm.cs.min', 'cs']);
    }

    private function setPresenters($config) {
        $builder = $this->getContainerBuilder();

        $builder->addDefinition($this->prefix('profile'))
                ->setClass(\NAttreid\Crm\Control\ProfilePresenter::class)
                ->setArguments([$config['minPasswordLength']]);

        $builder->addDefinition($this->prefix('fileManager'))
                ->setClass(\NAttreid\Crm\Control\FileManagerPresenter::class)
                ->setArguments([$config['fileManagerDir']]);

        $builder->addDefinition($this->prefix('info'))
                ->setClass(\NAttreid\Crm\Control\InfoPresenter::class)
                ->setArguments([$config['infoRefresh']]);

        $builder->addDefinition($this->prefix('users'))
                ->setClass(\NAttreid\Crm\Control\UsersPresenter::class)
                ->setArguments([$config['passwordChars'], $config['minPasswordLength']]);

        $builder->addDefinition($this->prefix('sign'))
                ->setClass(\NAttreid\Crm\Control\SignPresenter::class)
                ->setArguments([$config['loginExpiration'], $config['sessionExpiration'], $config['minPasswordLength']]);
    }

    private function setMenu($config) {
        $builder = $this->getContainerBuilder();

        $extension = Strings::firstLower($config['namespace']);
        $builder->addDefinition($this->prefix('menu'))
                ->setImplement(\NAttreid\Menu\IMenuFactory::class)
                ->setFactory(\NAttreid\Menu\Menu::class)
                ->addTag('crm.menu')
                ->addSetup('setMenu', [
                    [$extension . 'Ext' => $config['menu']]
        ]);
    }

    private function setMailing($config) {
        $builder = $this->getContainerBuilder();

        $rc = new \Nette\Reflection\ClassType(Mailer::class);
        $dir = dirname($rc->getFileName());
        $builder->addDefinition($this->prefix('mailer'))
                ->setClass(Mailer::class)
                ->setArguments([$config['sender'], $dir]);
    }

    public function beforeCompile() {
        $builder = $this->getContainerBuilder();
        $config = $this->validateConfig($this->loadFromFile(__DIR__ . '/default.neon'), $this->config);

        $namespace = Strings::firstLower($config['namespace']);

        $this->setRouting();
        $this->setTranslation();
        $this->setTracy();
        $this->setFlash();
        $this->setModule($config, $namespace);
        $this->setCrmModule($config, $namespace);

        $builder->getDefinition('application.presenterFactory')
                ->addSetup('setMapping', [
                    [$config['namespace'] => 'NAttreid\Crm\Control\*Presenter']
        ]);

        $authenticator = $builder->getByType(Authenticator::class);
        $builder->getDefinition($authenticator)
                ->addSetup('add', [$namespace, $builder->getDefinition($this->prefix('authenticator'))]);
    }

    private function setRouting() {
        $builder = $this->getContainerBuilder();
        $router = $builder->getByType(RouterFactory::class);
        try {
            $builder->getDefinition($router)
                    ->addSetup('addRouter', ['@' . $this->prefix('router'), RouterFactory::PRIORITY_APP])
                    ->addSetup('setLang', ['@' . $this->prefix('configurator') . '::defaultLang', '@' . $this->prefix('configurator') . '::allowedLang']);
        } catch (\Nette\DI\MissingServiceException $ex) {
            throw new \Nette\DI\MissingServiceException("Missing extension 'nattreid/routing'");
        }
    }

    private function setModule($config, $namespace) {
        foreach ($this->findByType(BasePresenter::class) as $def) {
            $def->addSetup('setModule', [$config['namespace'], $namespace]);
            if ($config['layout'] !== NULL) {
                $def->addSetup('setLayout', [$config['layout']]);
            }
        }
    }

    private function setCrmModule($config, $namespace) {
        foreach ($this->findByType(Presenter::class) as $def) {
            $class = $def->getClass();

            $m = Strings::matchAll($class, '#(\w+)Module#');
            $module = Strings::firstLower(end($m)[1]);
            $def->addSetup('setCrmModule', [$module]);
        }
    }

    private function setTranslation() {
        $builder = $this->getContainerBuilder();
        try {
            $translator = $builder->getByType(Translator::class);
            $def = $builder->getDefinition($translator);
            $setup = [
                new Statement('addResource', ['neon', __DIR__ . '/../lang/ublaboo_datagrid.cs_CZ.neon', 'cs_CZ', 'ublaboo_datagrid']),
                new Statement('addResource', ['neon', __DIR__ . '/../lang/ublaboo_datagrid.en_US.neon', 'en_US', 'ublaboo_datagrid']),
                new Statement('addResource', ['neon', __DIR__ . '/../lang/form.cs_CZ.neon', 'cs_CZ', 'form']),
                new Statement('addResource', ['neon', __DIR__ . '/../lang/form.en_US.neon', 'en_US', 'form']),
                new Statement('addResource', ['neon', __DIR__ . '/../lang/mailing.cs_CZ.neon', 'cs_CZ', 'mailing']),
                new Statement('addResource', ['neon', __DIR__ . '/../lang/mailing.en_US.neon', 'en_US', 'mailing']),
                new Statement('addResource', ['neon', __DIR__ . '/../lang/main.cs_CZ.neon', 'cs_CZ', 'main']),
                new Statement('addResource', ['neon', __DIR__ . '/../lang/main.en_US.neon', 'en_US', 'main'])
            ];
            $def->setSetup(array_merge($def->getSetup(), $setup));
        } catch (\Nette\DI\MissingServiceException $ex) {
            throw new \Nette\DI\MissingServiceException("Missing extension 'kdyby/translation'");
        }
    }

    private function setTracy() {
        $builder = $this->getContainerBuilder();
        try {
            $tracy = $builder->getByType(Tracy::class);
            $builder->getDefinition($tracy)
                    ->addSetup('enableMail', ['@' . $this->prefix('configurator') . '::mailPanel']);
        } catch (\Nette\DI\MissingServiceException $ex) {
            throw new \Nette\DI\MissingServiceException("Missing extension 'nattreid/tracyplugin'");
        }
    }

    private function setFlash() {
        $builder = $this->getContainerBuilder();
        try {
            $flash = $builder->getByType(FlashNotifier::class);
            $builder->getDefinition($flash);
        } catch (\Nette\DI\MissingServiceException $ex) {
            throw new \Nette\DI\MissingServiceException("Missing extension 'nattreid/flash-messages'");
        }
    }

    /**
     * 
     * @param string $type
     * @return \Nette\DI\ServiceDefinition
     */
    private function findByType($type) {
        $type = ltrim($type, '\\');
        return array_filter($this->getContainerBuilder()->getDefinitions(), function ($def) use ($type) {
            return is_a($def->getClass(), $type, TRUE) || is_a($def->getImplement(), $type, TRUE);
        });
    }

}
