<?php

namespace NAttreid\Crm\Control;

use IPub\FlashMessages\FlashNotifier;
use NAttreid\AppManager\AppManager;
use NAttreid\Crm\Configurator\Configurator;
use NAttreid\Security\Control\TryUser;
use NAttreid\Security\User;
use Nette\Application\Responses\FileResponse;
use Nette\Application\UI\Control;
use Nette\Utils\Strings;

/**
 * DockBar
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

	public function __construct($permissions, $module, $front, AppManager $app, User $user, Configurator $configurator, FlashNotifier $flashNotifier)
	{
		parent::__construct();
		$this->app = $app;
		$this->user = $user;
		$this->configurator = $configurator;
		$this->flashNotifier = $flashNotifier;

		$this->module = $module;
		$this->front = $front;
		$this->links = $this->createLinks('main.dockbar', $permissions);
	}

	/**
	 * @return TryUser
	 */
	private function getTryUser()
	{
		/* @var $presenter BasePresenter */
		$presenter = $this->presenter;
		return $presenter->getTryUser();
	}

	/**
	 * Nastavi aktivni tlacitko pro menu (posun)
	 * @param boolean $shifted
	 */
	public function setShifted($shifted = true)
	{
		$this->template->shifted = $shifted;
	}

	/**
	 * Odhlaseni
	 */
	public function handleLogOut()
	{
		$this->user->logout();
		$this->flashNotifier->info('main.user.youAreLoggedOut');
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
		$this->flashNotifier->success('main.dockbar.management.application.cssRestored');
	}

	/**
	 * Znovunacte Javascript
	 */
	public function handleRestoreJs()
	{
		$this->checkHandlerPermission();

		$this->app->clearJs();
		$this->flashNotifier->success('main.dockbar.management.application.jsRestored');
	}

	/**
	 * Smazani cache
	 */
	public function handleClearSessions()
	{
		$this->checkHandlerPermission();

		$this->app->clearSession('0 minute');
		$this->flashNotifier->success('main.dockbar.management.application.sessionsCleared');
	}

	/**
	 * Smazani cache
	 */
	public function handleClearCache()
	{
		$this->checkHandlerPermission();

		$this->app->clearCache();
		$this->flashNotifier->success('main.dockbar.management.application.cacheCleared');
	}

	/**
	 * Smazani cache
	 */
	public function handleInvalidateCache()
	{
		$this->checkHandlerPermission();

		$this->app->invalidateCache();
		$this->flashNotifier->success('main.dockbar.management.application.cacheInvalidated');
	}

	/**
	 * Smazani temp
	 */
	public function handleClearTemp()
	{
		$this->checkHandlerPermission(false);

		$this->app->clearTemp();
		$this->flashNotifier->success('main.dockbar.management.application.tempCleared');
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
			$this->flashNotifier->success('main.dockbar.management.source.deployed');
		} catch (\InvalidArgumentException $ex) {
			$this->flashNotifier->error('main.dockbar.management.source.deployNotSet');
		}
		$this->redirect('this');
	}

	/**
	 * Aktualizace composeru
	 */
	public function handleComposerUpdate()
	{
		$this->checkHandlerPermission();

		$this->app->composerUpdate(true);
		$this->flashNotifier->success('main.dockbar.management.source.composerUpdated');
		$this->redirect('this');
	}

	/**
	 * Zaloha databaze
	 */
	public function handleBackupDatabase()
	{
		$this->checkHandlerPermission(false);

		$file = $this->app->backupDatabase();
		$this->presenter->sendResponse(new FileResponse($file, 'backup.zip'));
	}

	/**
	 * Smazání databaze
	 */
	public function handleDropDatabase()
	{
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
	public function isLinkAllowed($link)
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
		$identity = $this->user->getIdentity();
		$username = $identity->firstName;
		if (!empty($username) || !empty($identity->surname)) {
			$username .= ' ';
		}
		$username .= $identity->surname;
		$template->userName = $username;

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
	private function createLinks($parent, $items)
	{
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
						$this->allowedLinks[$link] = true;
					} else {
						$this->allowedHandler[$name] = true;
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
	private function isLink($item)
	{
		if ($item === null) {
			return true;
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
	private function checkHandlerPermission($ajax = true)
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

	/** @return Dockbar */
	public function create();
}
