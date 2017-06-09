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
	private static $parent = 'dockbar';

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

	/**
	 * Prida link do dockbaru
	 * @param string $name
	 * @param string $link
	 * @param bool $ajax
	 */
	public function addLink(string $name, string $link = null, bool $ajax = false): void
	{
		$this->addedItems[] = new Item($name, [
			'link' => $link ?? '#',
			'ajax' => $ajax
		]);
	}

	/**
	 * Nastavi aktivni tlacitko pro menu (posun)
	 * @param bool $shifted
	 */
	public function setShifted(bool $shifted = true): void
	{
		$this->template->shifted = $shifted;
	}

	/**
	 * Odhlaseni
	 */
	public function handleLogOut(): void
	{
		$this->user->logout();
		$this->flashNotifier->info('cms.user.youAreLoggedOut');
		$this->presenter->redirect(":{$this->module}:Sign:in");
	}

	/**
	 * Vypnuti TryUser
	 */
	public function handleCloseTryUser(): void
	{
		$this->getTryUser()->handleLogoutTryRole();
	}

	/**
	 * Znovunacte CSS
	 */
	public function handleRestoreCss(): void
	{
		$this->checkHandlerPermission();

		$this->app->clearCss();
		$this->flashNotifier->success('dockbar.management.application.cssRestored');
	}

	/**
	 * Znovunacte Javascript
	 */
	public function handleRestoreJs(): void
	{
		$this->checkHandlerPermission();

		$this->app->clearJs();
		$this->flashNotifier->success('dockbar.management.application.jsRestored');
	}

	/**
	 * Smazani cache
	 */
	public function handleClearSessions(): void
	{
		$this->checkHandlerPermission();

		$this->app->clearSession('0 minute');
		$this->flashNotifier->success('dockbar.management.application.sessionsCleared');
	}

	/**
	 * Smazani cache
	 */
	public function handleClearCache(): void
	{
		$this->checkHandlerPermission(false);

		$this->app->clearCache();
		$this->flashNotifier->success('dockbar.management.application.cacheCleared');
		$this->redirect('this');
	}

	/**
	 * Smazani cache
	 */
	public function handleInvalidateCache(): void
	{
		$this->checkHandlerPermission();

		$this->app->invalidateCache();
		$this->flashNotifier->success('dockbar.management.application.cacheInvalidated');
	}

	/**
	 * Smazani temp
	 */
	public function handleClearTemp(): void
	{
		$this->checkHandlerPermission(false);

		$this->app->clearTemp();
		$this->flashNotifier->success('dockbar.management.application.tempCleared');
		$this->redirect('this');
	}

	/**
	 * Deploy
	 */
	public function handleDeploy(): void
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
	public function handleComposerUpdate(): void
	{
		$this->checkHandlerPermission();

		$this->app->composerUpdate(true);
		$this->flashNotifier->success('dockbar.management.source.composerUpdated');
	}

	/**
	 * Zaloha databaze
	 */
	public function handleBackupDatabase(): void
	{
		$this->checkHandlerPermission(false);

		$file = $this->app->backupDatabase();
		$this->presenter->sendResponse(new FileResponse($file, 'database.zip'));
	}

	/**
	 * Smazání databaze
	 */
	public function handleDropDatabase(): void
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
	public function handleBackup(): void
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

	public function render(): void
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


	private function parseLinks(array $items, Item $parent = null): void
	{
		$resource = $parent === null ? self::$parent : $parent->resource;

		foreach ($items as $name => $item) {
			$obj = new Item($name, $item, $resource, $this->module);
			if (
				!($item['advanced'] ?? false)
				|| $this->configurator->dockbarAdvanced
			) {
				if ($obj->isLink()) {
					if (!$this->user->isAllowed($obj->resource, Acl::PRIVILEGE_VIEW)) {
						continue;
					}

					if ($obj->handler) {
						$this->allowedHandler[$obj->link] = true;
					} else {
						$this->allowedLinks[$obj->link] = true;
					}
				} else {
					$this->parseLinks($item, $obj);
				}

				if ($parent !== null) {
					if ($obj->isLink() || $obj->hasItems) {
						$parent->addItem($obj);
					}
				} else {
					if ($obj->hasItems) {
						$this->items[] = $obj;
					}
				}
			}
		}
	}

	/**
	 * Zkontroluje opravneni a pokud je nema, ukonci aplikaci
	 * @param bool $ajax
	 */
	private function checkHandlerPermission(bool $ajax = true): void
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
