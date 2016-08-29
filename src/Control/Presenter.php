<?php

namespace NAttreid\Crm\Control;

use NAttreid\Menu\IMenuFactory,
    NAttreid\Menu\Item,
    NAttreid\Crm\Control\IDockbarFactory;

/**
 * Presenter pro moduly CRM
 *
 * @author Attreid <attreid@gmail.com>
 */
abstract class Presenter extends BasePresenter {

    use \WebChemistry\Images\TPresenter,
        \Nextras\Application\UI\SecuredLinksPresenterTrait;

    protected function startup() {
        parent::startup();

        if (!$this->user->isLoggedIn()) {
            if ($this->user->logoutReason === \Nette\Security\IUserStorage::INACTIVITY) {
                $this->flashNotifier->info('main.user.inactivityLogout');
            }
            $this->redirect(":{$this->module}:Sign:in", ['backlink' => $this->storeRequest()]);
        }

        // opravneni
        $link = $this->getAction(TRUE);
        if ($this->isLinkCurrent(":{$this->module}:Homepage:") || $this->isLinkCurrent(":{$this->module}:Profile:")) {
            
        } elseif ($this->crmModule !== NULL && ($this['menu']->isLinkAllowed($link))) {
            
        } elseif ($this['dockbar']->isLinkAllowed($link)) {
            
        } else {
            $this->flashNotifier->error('main.permissions.accessDenied');
            $this->redirect(":{$this->module}:Homepage:");
        }
    }

    protected function beforeRender() {
        parent::beforeRender();
        $logo = $this->configurator->logo;
        if ($logo == NULL) {
            $logo = 'empty.png';
        }
        $this->template->headerLogo = $logo;

        if (!isset($this->template->shifted)) {
            $this->template->shifted = FALSE;
        }
    }

    /* ###################################################################### */
    /*                                 DockBar                                */

    /** @var IDockbarFactory */
    private $dockbarFactory;

    public function injectDockbarFactory(IDockbarFactory $dockbarFactory) {
        $this->dockbarFactory = $dockbarFactory;
    }

    /**
     * Vytvori komponentu Dockbar
     * @return Dockbar\Dockbar
     */
    protected function createComponentDockbar() {
        $dockbar = $this->dockbarFactory->create();
        return $dockbar;
    }

    /* ###################################################################### */
    /*                                 Menu                                   */

    /** @var IMenuFactory */
    private $menuFactory;

    public function injectMenu(IMenuFactory $menuFactory) {
        $this->menuFactory = $menuFactory;
    }

    /**
     * Hlavni menu
     * @return \NAttreid\Menu\Menu
     */
    protected function createComponentMenu() {
        $moduleMenu = $this->menuFactory->create();
        $moduleMenu->setTranslator($this->translator);
        $moduleMenu->setBaseUrl('main.title', ":{$this->module}:Homepage:");
        return $moduleMenu;
    }

    /**
     * Nastavi pocet (cislo za text linku)
     * @param string $link
     * @param int $count
     * @param string $type
     */
    protected function setMenuCount($link, $count, $type = Item::INFO) {
        $menu = $this['menu'];
        $menu->setCount($link, $count, $type);
    }

    /**
     * Drobeckova navigace
     * @return \NAttreid\Menu\Breadcrumb\Breadcrumb
     */
    protected function createComponentBreadcrumb() {
        $breadcrumb = $this['menu']->getBreadcrumb();
        return $breadcrumb;
    }

    /**
     * Prida link do drobeckove navigace
     * @param string $name
     * @param string $link
     */
    public function addBreadcrumbLink($name, $link = NULL) {
        $this['breadcrumb']->addLink($name, $link);
    }

    /**
     * Nastavi zobrazeni menu v mobilni verzi
     * @param boolean $view
     */
    public function viewMobileMenu($view = TRUE) {
        $this->template->shifted = $view;
        $this['dockbar']->setShifted($view);
    }

    /* ###################################################################### */
    /*                               Backlink                                 */

    /** @persistent */
    public $cbl;

    /**
     * Navrat na predchozi stranku
     */
    public function handleBack($backlink) {
        $this->restoreRequest($backlink);
    }

    /**
     * Ulozi aktualni request
     */
    public function storeBacklink() {
        $this->cbl = $this->storeRequest('+30 minutes');
    }

    /**
     * Obnovi predchozi request
     */
    public function restoreBacklink() {
        $this->restoreRequest($this->getParameter('cbl'));
    }

    /**
     * Vrati zpatecni adresu
     */
    public function getBacklink() {
        return $this->link('back!', [$this->getParameter('cbl')]);
    }

}
