<?php

declare(strict_types=1);

namespace NAttreid\Cms\Control;

use NAttreid\Breadcrumbs\Breadcrumb;
use NAttreid\Breadcrumbs\Link;
use NAttreid\Cms\Factories\ICmsMenuFactory;
use NAttreid\Menu\Menu\Menu;
use Nette\Security\IUserStorage;
use Nextras\Application\UI\SecuredLinksPresenterTrait;
use WebChemistry\Images\TPresenter;

/**
 * Presenter pro moduly CMS
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
				$this->flashNotifier->info('cms.user.inactivityLogout');
			}
			$this->redirect(":{$this->module}:Sign:in", ['backlink' => $this->storeRequest()]);
		}
	}

	protected function beforeRender()
	{
		parent::beforeRender();
		$this->template->cmsLogo = $this->configurator->cmsLogo;

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
	protected function createComponentDockbar(): Dockbar
	{
		$dockbar = $this->dockbarFactory->create();
		return $dockbar;
	}

	/* ###################################################################### */
	/*                                 Menu                                   */

	/** @var ICmsMenuFactory */
	private $menuFactory;

	public function injectMenu(ICmsMenuFactory $menuFactory)
	{
		$this->menuFactory = $menuFactory;
	}

	/**
	 * Hlavni menu
	 * @return Menu
	 */
	protected function createComponentMenu(): Menu
	{
		$moduleMenu = $this->menuFactory->create();
		$moduleMenu->setTranslator($this->translator);
		$moduleMenu->setBaseUrl('cms.title', ":{$this->module}:Homepage:");
		return $moduleMenu;
	}

	/**
	 * Drobeckova navigace
	 * @return Breadcrumb
	 */
	protected function createComponentBreadcrumb(): Breadcrumb
	{
		return $this['menu']->getBreadcrumb();
	}

	/**
	 * Prida link do drobeckove navigace
	 * @param string $name
	 * @param string $link
	 * @param array $arguments
	 * @return Link
	 */
	public function addBreadcrumbLink(string $name, string $link = null, array $arguments = []): Link
	{
		return $this['breadcrumb']->addLink($name, $link, $arguments);
	}

	/**
	 * Prida link do drobeckove navigace (bez prekladu)
	 * @param string $name
	 * @param string $link
	 * @param array $arguments
	 * @return Link
	 */
	public function addBreadcrumbLinkUntranslated(string $name, string $link = null, array $arguments = []): Link
	{
		return $this['breadcrumb']->addLinkUntranslated($name, $link, $arguments);
	}

	/**
	 * Nastavi zobrazeni menu v mobilni verzi
	 * @param bool $view
	 */
	public function viewMobileMenu(bool $view = true)
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
	 * @param string|null $backlink
	 */
	public function handleBack(string $backlink = null)
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
