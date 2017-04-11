<?php

declare(strict_types=1);

namespace NAttreid\Cms\Control;

use IPub\FlashMessages\FlashNotifier;
use NAttreid\AppManager\AppManager;
use NAttreid\Cms\Configurator\Configurator;
use NAttreid\Security\Control\TryUser;
use NAttreid\Security\Model\Acl\Acl;
use NAttreid\Security\User;
use Nette\Application\Responses\FileResponse;
use Nette\Application\UI\Control;
use Nette\Utils\Strings;

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

	/** @var array */
	private $links;

	/** @var array */
	private $allowedLinks = [];

	/** @var array */
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
		$this->links = $this->createLinks('dockbar', $permissions);
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
		$template->links = $this->links;
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

	/**
	 * Prava pro dockbar
	 * @param string $parent
	 * @param array $items
	 * @return array
	 */
	private function createLinks(string $parent, array $items): array
	{
		$arr = [];
		foreach ($items as $name => $item) {
			$resource = $parent . '.' . $name;
			if ($this->isLink($item)) {
				if ($this->user->isAllowed($resource, Acl::PRIVILEGE_VIEW)) {

					if (!empty($item['advanced']) && !$this->configurator->dockbarAdvanced) {
						continue;
					}

					if (isset($item['link'])) {
						$link = $item['link'] = ":{$this->module}:" . $item['link'];
						if (Strings::endsWith($link, ':default')) {
							$link = substr($link, 0, -7);
						}
						$this->allowedLinks[$link] = true;
					} else {
						$this->allowedHandler[$name] = true;
						$item['handler'] = $name;
					}
					$item['name'] = $resource;
					$arr[$name] = $item;
				}
			} else {
				$result = $this->createLinks($resource, $item);
				if (!empty($result)) {
					$result['name'] = $resource . '.title';
					$arr[$name] = $result;
				}
			}
		}
		return $arr;
	}

	/**
	 * Je link
	 * @param string|array $item
	 * @return bool
	 */
	private function isLink($item): bool
	{
		if ($item === null) {
			return true;
		} elseif (is_array($item)) {
			return !is_array(current($item));
		} else {
			throw new \InvalidArgumentException('Cms menu items is wrong in config.neon');
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
