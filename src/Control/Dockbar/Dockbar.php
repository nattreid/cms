<?php

namespace NAttreid\Crm\Control;

use NAttreid\AppManager\AppManager,
    NAttreid\Security\User,
    NAttreid\Crm\Configurator,
    Nette\Utils\Strings,
    IPub\FlashMessages\FlashNotifier,
    Nette\Localization\ITranslator;

/**
 * DockBar
 *
 * @author Attreid <attreid@gmail.com>
 */
class Dockbar extends \Nette\Application\UI\Control {

    /** @var string */
    private $module;

    /** @var AppManager */
    private $app;

    /** @var User */
    private $user;

    /** @var Configurator */
    private $configurator;

    /** @var ITranslator */
    private $translator;

    /** @var FlashNotifier */
    private $flashNotifier;

    /** @var array */
    private $links;

    /** @var array */
    private $allowedLinks = [];

    /** @var array */
    private $allowedHandler = [];

    public function __construct($permissions, $module, AppManager $app, User $user, Configurator $configurator, FlashNotifier $flashNotifier) {
        $this->app = $app;
        $this->user = $user;
        $this->configurator = $configurator;
        $this->flashNotifier = $flashNotifier;

        $this->module = $module;
        $this->links = $this->createLinks('main.dockbar', $permissions);
    }

    /**
     * Nastavi modul
     * @param string $mainPresenter
     * @param string $module
     */
    public function setModule() {
        
    }

    /**
     * Nastavi translator
     * @param ITranslator $translator
     */
    public function setTranslator(ITranslator $translator) {
        $this->translator = $translator;
    }

    /**
     * Nastavi aktivni tlacitko pro menu (posun)
     * @param boolean $shifted
     */
    public function setShifted($shifted = TRUE) {
        $this->template->shifted = $shifted;
    }

    /**
     * Odhlaseni
     */
    public function handleLogOut() {
        $this->user->logout();
        $this->flashNotifier->info('main.user.youAreLoggedOut');
        $this->presenter->redirect(":{$this->module}:Sign:in");
    }

    /**
     * Znovunacte CSS
     */
    public function handleRestoreCss() {
        $this->checkHandlerPermission();

        $this->app->clearCss();
        $this->flashNotifier->success('main.dockbar.management.application.cssRestored');
    }

    /**
     * Znovunacte Javascript
     */
    public function handleRestoreJs() {
        $this->checkHandlerPermission();

        $this->app->clearJs();
        $this->flashNotifier->success('main.dockbar.management.application.jsRestored');
    }

    /**
     * Smazani cache
     */
    public function handleClearSessions() {
        $this->checkHandlerPermission();

        $this->app->clearSession('0 minute');
        $this->flashNotifier->success('main.dockbar.management.application.sessionsCleared');
    }

    /**
     * Smazani cache
     */
    public function handleClearCache() {
        $this->checkHandlerPermission();

        $this->app->clearCache();
        $this->flashNotifier->success('main.dockbar.management.application.cacheCleared');
    }

    /**
     * Smazani cache
     */
    public function handleInvalidateCache() {
        $this->checkHandlerPermission();

        $this->app->invalidateCache();
        $this->flashNotifier->success('main.dockbar.management.application.cacheInvalidated');
    }

    /**
     * Smazani temp
     */
    public function handleClearTemp() {
        $this->checkHandlerPermission(FALSE);

        $this->app->clearTemp();
        $this->flashNotifier->success('main.dockbar.management.application.tempCleared');
        $this->redirect('this');
    }

    /**
     * Deploy
     */
    public function handleDeploy() {
        $this->checkHandlerPermission();

        try {
            $this->app->gitPull(TRUE);
            $this->app->clearCache();
            $this->flashNotifier->success('main.dockbar.management.source.deployed');
        } catch (\InvalidArgumentException $ex) {
            $this->flashNotifier->error('main.dockbar.management.source.deployNotSet');
        }
        $this->redirect('this');
    }

    /**
     * Aktualizace composeru
     */
    public function handleComposerUpdate() {
        $this->checkHandlerPermission();

        $this->app->composerUpdate(TRUE);
        $this->flashNotifier->success('main.dockbar.management.source.composerUpdated');
        $this->redirect('this');
    }

    /**
     * Zaloha databaze
     */
    public function handleBackupDatabase() {
        $this->checkHandlerPermission(FALSE);

        $file = $this->app->backupDatabase();
        $this->presenter->sendResponse(new \Nette\Application\Responses\FileResponse($file, 'backup.zip'));
    }

    /**
     * Smazání databaze
     */
    public function handleDropDatabase() {
        $this->checkHandlerPermission();

        $this->app->dropDatabase();
        $this->app->clearCache();
        $this->flashNotifier->success('main.dockbar.management.database.databaseDroped');
        $this->redirect('this');
    }

    /**
     * Ma opravneni zobrazit stranku?
     * @param string $link
     * @return boolean
     */
    public function isLinkAllowed($link) {
        if (isset($this->allowedLinks[$link])) {
            return TRUE;
        } else {
            $pos = strrpos($link, ':');
            $link = substr($link, 0, ($pos + 1));
            return isset($this->allowedLinks[$link]);
        }
    }

    public function render() {
        $template = $this->template;

//        $template->addFilter('translate', [$this->translator, 'translate']);
        // linky pro dockbar
        $template->links = $this->links;
        $template->profileLink = $this->presenter->link(":{$this->module}:Profile:");

        //uzivatelske jmeno
        $identity = $this->user->getIdentity();
        $username = $identity->firstName;
        if (!empty($username) || !empty($identity->surname)) {
            $username .= ' ';
        }
        $username .= $identity->surname;
        $template->userName = $username;

        if (!isset($template->shifted)) {
            $template->shifted = false;
        }

        $template->setFile(__DIR__ . '/default.latte');

        $template->render();
    }

    /**
     * Prava pro dockbar
     * @param string $parent
     * @param array $items
     * @return array
     */
    private function createLinks($parent, $items) {
        $arr = [];
        foreach ($items as $name => $item) {
            $uniqid = $parent . '.' . $name;
            if ($this->isLink($item)) {
                if ($this->user->isAllowed($uniqid, 'view')) {

                    if (!empty($item['advanced']) && !$this->configurator->dockbarAdvanced) {
                        continue;
                    }

                    if (isset($item['link'])) {
                        $link = $item['link'] = ":{$this->module}:" . $item['link'];
                        if (Strings::endsWith($link, ':default')) {
                            $link = substr($link, 0, -7);
                        }
                        $this->allowedLinks[$link] = TRUE;
                    } else {
                        $this->allowedHandler[$name] = TRUE;
                        $item['handler'] = $name;
                    }
                    $arr[$name] = $item;
                }
            } else {
                $result = $this->createLinks($uniqid, $item);
                if (!empty($result)) {
                    $arr[$name] = $result;
                }
            }
        }
        return $arr;
    }

    /**
     * Je link
     * @param mixed $item
     * @return boolean
     */
    private function isLink($item) {
        if ($item === NULL) {
            return TRUE;
        } elseif (is_array($item)) {
            return !is_array(current($item));
        } else {
            throw new \InvalidArgumentException('Error in dockbar.neon');
        }
    }

    /**
     * Zkontroluje opravneni a pokud je nema, ukonci aplikaci
     * @param boolean $ajax
     */
    private function checkHandlerPermission($ajax = TRUE) {
        if (!isset($this->allowedHandler[$this->presenter->getSignal()[1]])) {
            $this->presenter->terminate();
        }
        if ($ajax && !$this->presenter->isAjax()) {
            $this->presenter->terminate();
        }
    }

}

interface IDockbarFactory {

    /** @return Dockbar */
    public function create();
}