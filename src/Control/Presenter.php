<?php

namespace NAttreid\Crm\Control;

use NAttreid\Crm\ICrmMenuFactory;
use NAttreid\Menu\Breadcrumb\Breadcrumb;
use NAttreid\Menu\Breadcrumb\Link as BLink;
use NAttreid\Menu\Menu\Link;
use NAttreid\Menu\Menu\Menu;
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

		if (!$this->user->loggedIn) {
			if ($this->user->logoutReason === IUserStorage::INACTIVITY) {
				$this->flashNotifier->info('crm.user.inactivityLogout');
			}
			$this->redirect(":{$this->module}:Sign:in", ['backlink' => $this->storeRequest()]);
		}
	}

	protected function beforeRender()
	{
		parent::beforeRender();
		$this->template->crmLogo = $this->configurator->crmLogo;

		if (!isset($this->template->shifted)) {
			$this->template->shifted = false;
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
		$moduleMenu->setBaseUrl('crm.title', ":{$this->module}:Homepage:");
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
	 * @param array $arguments
	 * @return BLink
	 */
	public function addBreadcrumbLink($name, $link = null, $arguments = [])
	{
		return $this['breadcrumb']->addLink($name, $link, $arguments);
	}

	/**
	 * Prida link do drobeckove navigace (bez prekladu)
	 * @param string $name
	 * @param string $link
	 * @param array $arguments
	 * @return BLink
	 */
	public function addBreadcrumbLinkUntranslated($name, $link = null, $arguments = [])
	{
		return $this['breadcrumb']->addLinkUntranslated($name, $link, $arguments);
	}

	/**
	 * Nastavi zobrazeni menu v mobilni verzi
	 * @param boolean $view
	 */
	public function viewMobileMenu($view = true)
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
	 * @param string $backlink
	 */
	public function handleBack($backlink)
	{
		$this->restoreRequest($backlink);
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
		$this->handleBack($this->getParameter('cbl'));
	}

	/**
	 * Vrati zpatecni adresu
	 */
	public function getBacklink()
	{
		return $this->link('back!', [$this->getParameter('cbl')]);
	}

}
