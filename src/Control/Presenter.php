<?php

namespace NAttreid\Crm\Control;

use NAttreid\Crm\ICrmMenuFactory;
use NAttreid\Menu\Breadcrumb;
use NAttreid\Menu\Link;
use NAttreid\Menu\Menu;
use Nette\Security\IUserStorage;
use Nextras\Application\UI\SecuredLinksPresenterTrait;
use WebChemistry\Images\TPresenter;

/**
 * Presenter pro moduly CRM
 *
 * @author Attreid <attreid@gmail.com>
 */
abstract class Presenter extends BasePresenter
{

	use TPresenter,
		SecuredLinksPresenterTrait;

	protected function startup()
	{
		parent::startup();

		if (!$this->user->isLoggedIn()) {
			if ($this->user->logoutReason === IUserStorage::INACTIVITY) {
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

	protected function beforeRender()
	{
		parent::beforeRender();
		$this->template->crmLogo = $this->configurator->crmLogo;

		if (!isset($this->template->shifted)) {
			$this->template->shifted = FALSE;
		}
	}

	/* ###################################################################### */
	/*                                 DockBar                                */

	/** @var IDockbarFactory */
	private $dockbarFactory;

	public function injectDockbarFactory(IDockbarFactory $dockbarFactory)
	{
		$this->dockbarFactory = $dockbarFactory;
	}

	/**
	 * Vytvori komponentu Dockbar
	 * @return Dockbar
	 */
	protected function createComponentDockbar()
	{
		$dockbar = $this->dockbarFactory->create();
		return $dockbar;
	}

	/* ###################################################################### */
	/*                                 Menu                                   */

	/** @var ICrmMenuFactory */
	private $menuFactory;

	public function injectMenu(ICrmMenuFactory $menuFactory)
	{
		$this->menuFactory = $menuFactory;
	}

	/**
	 * Hlavni menu
	 * @return Menu
	 */
	protected function createComponentMenu()
	{
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
	protected function setMenuCount($link, $count, $type = Link::INFO)
	{
		$this['menu']->setCount($link, $count, $type);
	}

	/**
	 * Drobeckova navigace
	 * @return Breadcrumb
	 */
	protected function createComponentBreadcrumb()
	{
		return $this['menu']->getBreadcrumb();
	}

	/**
	 * Prida link do drobeckove navigace
	 * @param string $name
	 * @param string $link
	 */
	public function addBreadcrumbLink($name, $link = NULL)
	{
		$this['breadcrumb']->addLink($name, $link);
	}

	/**
	 * Nastavi zobrazeni menu v mobilni verzi
	 * @param boolean $view
	 */
	public function viewMobileMenu($view = TRUE)
	{
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
	public function handleBack($backlink)
	{
		$this->restoreRequest($backlink);
		$this->redirect('default');
	}

	/**
	 * Ulozi aktualni request
	 */
	public function storeBacklink()
	{
		$this->cbl = $this->storeRequest('+30 minutes');
	}

	/**
	 * Obnovi predchozi request
	 */
	public function restoreBacklink()
	{
		$this->restoreRequest($this->getParameter('cbl'));
		$this->redirect('default');
	}

	/**
	 * Vrati zpatecni adresu
	 */
	public function getBacklink()
	{
		return $this->link('back!', [$this->getParameter('cbl')]);
	}

}
