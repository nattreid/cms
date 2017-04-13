<?php

declare(strict_types=1);

namespace NAttreid\Cms\Control\Dockbar;

use IPub\FlashMessages\FlashNotifier;
use NAttreid\AppManager\AppManager;
use NAttreid\Cms\Configurator\Configurator;
use NAttreid\Cms\Control\BasePresenter;
use NAttreid\Security\Control\TryUser;
use NAttreid\Security\Model\Acl\Acl;
use NAttreid\Security\User;
use Nette\Application\Responses\FileResponse;
use Nette\Application\UI\Control;

/**
 * DockBar
 *
 * @property-read BasePresenter $presenter
 *
 * @author Attreid <attreid@gmail.com>
 */
class Dockbar extends Control
{

	/** @var string */
	private $module;

	/** @var string */
	private $front;

	/** @var AppManager */
	private $app;

	/** @var User */
	private $user;

	/** @var Configurator */
	private $configurator;

	/** @var FlashNotifier */
	private $flashNotifier;

	/** @var Item[] */
	private $items = [];

	/** @var Item[] */
	private $addedItems = [];

	/** @var bool[] */
	private $allowedLinks = [];

	/** @var bool[] */
	private $allowedHandler = [];

	public function __construct(array $permissions, string $module, string $front, AppManager $app, User $user, Configurator $configurator, FlashNotifier $flashNotifier)
	{
		parent::__construct();
		$this->app = $app;
		$this->user = $user;
		$this->configurator = $configurator;
		$this->flashNotifier = $flashNotifier;

		$this->module = $module;
		$this->front = $front;
		$this->parseLinks($permissions);
	}

	/**
	 * @return TryUser
	 */
	private function getTryUser(): TryUser
	{
		$presenter = $this->presenter;
		return $presenter['tryUser'];
	}

	public function addLink(string $name, string $link, bool $ajax = false)
	{
		$this->addedItems[] = new Item('', $this->module, $name, [
			'link' => $link,
			'ajax' => $ajax
		]);
	}

	/**
	 * Nastavi aktivni tlacitko pro menu (posun)
	 * @param bool $shifted
	 */
	public function setShifted(bool $shifted = true)
	{
		$this->template->shifted = $shifted;
	}

	/**
	 * Odhlaseni
	 */
	public function handleLogOut()
	{
		$this->user->logout();
		$this->flashNotifier->info('cms.user.youAreLoggedOut');
		$this->presenter->redirect(":{$this->module}:Sign:in");
	}

	/**
	 * Vypnuti TryUser
	 */
	public function handleCloseTryUser()
	{
		$this->getTryUser()->handleLogoutTryRole();
	}

	/**
	 * Znovunacte CSS
	 */
	public function handleRestoreCss()
	{
		$this->checkHandlerPermission();

		$this->app->clearCss();
		$this->flashNotifier->success('dockbar.management.application.cssRestored');
	}

	/**
	 * Znovunacte Javascript
	 */
	public function handleRestoreJs()
	{
		$this->checkHandlerPermission();

		$this->app->clearJs();
		$this->flashNotifier->success('dockbar.management.application.jsRestored');
	}

	/**
	 * Smazani cache
	 */
	public function handleClearSessions()
	{
		$this->checkHandlerPermission();

		$this->app->clearSession('0 minute');
		$this->flashNotifier->success('dockbar.management.application.sessionsCleared');
	}

	/**
	 * Smazani cache
	 */
	public function handleClearCache()
	{
		$this->checkHandlerPermission(false);

		$this->app->clearCache();
		$this->flashNotifier->success('dockbar.management.application.cacheCleared');
		$this->redirect('this');
	}

	/**
	 * Smazani cache
	 */
	public function handleInvalidateCache()
	{
		$this->checkHandlerPermission();

		$this->app->invalidateCache();
		$this->flashNotifier->success('dockbar.management.application.cacheInvalidated');
	}

	/**
	 * Smazani temp
	 */
	public function handleClearTemp()
	{
		$this->checkHandlerPermission(false);

		$this->app->clearTemp();
		$this->flashNotifier->success('dockbar.management.application.tempCleared');
		$this->redirect('this');
	}

	/**
	 * Deploy
	 */
	public function handleDeploy()
	{
		$this->checkHandlerPermission();

		try {
			$this->app->gitPull(true);
			$this->app->clearCache();
			$this->flashNotifier->success('dockbar.management.source.deployed');
		} catch (\InvalidArgumentException $ex) {
			$this->flashNotifier->error('dockbar.management.source.deployNotSet');
		}
	}

	/**
	 * Aktualizace composeru
	 */
	public function handleComposerUpdate()
	{
		$this->checkHandlerPermission();

		$this->app->composerUpdate(true);
		$this->flashNotifier->success('dockbar.management.source.composerUpdated');
	}

	/**
	 * Zaloha databaze
	 */
	public function handleBackupDatabase()
	{
		$this->checkHandlerPermission(false);

		$file = $this->app->backupDatabase();
		$this->presenter->sendResponse(new FileResponse($file, 'database.zip'));
	}

	/**
	 * Smazání databaze
	 */
	public function handleDropDatabase()
	{
		$this->checkHandlerPermission(false);

		$this->app->dropDatabase();
		$this->app->clearCache();
		$this->flashNotifier->success('dockbar.management.database.databaseDroped');
		$this->presenter->redirect('this');
	}

	/**
	 * Zaloha
	 */
	public function handleBackup()
	{
		$this->checkHandlerPermission(false);

		$file = $this->app->backup();
		$this->presenter->sendResponse(new FileResponse($file, 'backup.zip'));
	}

	/**
	 * Ma opravneni zobrazit stranku?
	 * @param string $link
	 * @return bool
	 */
	public function isLinkAllowed(string $link): bool
	{
		if (isset($this->allowedLinks[$link])) {
			return true;
		} else {
			$pos = strrpos($link, ':');
			$link = substr($link, 0, ($pos + 1));
			return isset($this->allowedLinks[$link]);
		}
	}

	public function render()
	{
		$template = $this->template;

		// linky pro dockbar
		$template->front = $this->front;
		$template->items = $this->items;
		$template->addedItems = $this->addedItems;
		$template->profileLink = $this->presenter->link(":{$this->module}:Profile:");

		//uzivatelske jmeno
		$template->userName = $this->user->getIdentity()->fullName;

		$template->tryUserEnable = $this->getTryUser()->isEnable();

		if (!isset($template->shifted)) {
			$template->shifted = false;
		}

		$template->setFile(__DIR__ . '/default.latte');

		$template->render();
	}


	private function parseLinks(array $items, Item $parent = null)
	{
		$resource = $parent === null ? 'dockbar' : $parent->resource;

		foreach ($items as $name => $item) {
			$obj = new Item($resource, $this->module, $name, $item);
			if (
				$this->user->isAllowed($obj->resource, Acl::PRIVILEGE_VIEW)
				&& (!($item['advanced'] ?? false) || $this->configurator->dockbarAdvanced)
			) {
				if ($obj->isLink()) {
					if ($obj->handler) {
						$this->allowedHandler[$obj->link] = true;
					} else {
						$this->allowedLinks[$obj->link] = true;
					}
				} else {
					$this->parseLinks($item, $obj);
				}

				if ($parent !== null) {
					$parent->addItem($obj);
				} else {
					$this->items[] = $obj;
				}
			}
		}
	}

	/**
	 * Zkontroluje opravneni a pokud je nema, ukonci aplikaci
	 * @param bool $ajax
	 */
	private function checkHandlerPermission(bool $ajax = true)
	{
		if (!isset($this->allowedHandler[$this->presenter->getSignal()[1]])) {
			$this->presenter->terminate();
		}
		if ($ajax && !$this->presenter->isAjax()) {
			$this->presenter->terminate();
		}
	}

}

interface IDockbarFactory
{
	public function create(): Dockbar;
}
