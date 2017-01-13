<?php

namespace NAttreid\Crm\Control;

use NAttreid\AppManager\AppManager;

/**
 * Informace o serveru, php, atd
 *
 * @author Attreid <attreid@gmail.com>
 */
class InfoPresenter extends CrmPresenter
{

	/** @var int */
	private $refresh;

	/** @var AppManager */
	private $app;

	public function __construct($refresh, AppManager $app)
	{
		parent::__construct();
		$this->refresh = $refresh * 1000;
		$this->app = $app;
	}

	/**
	 * Refresh
	 */
	public function handleRefresh()
	{
		if ($this->isAjax()) {
			$this->redrawControl('server');

			$this->redrawControl('load');
			$this->redrawControl('uptime');
			$this->redrawControl('users');
			$this->redrawControl('processes');

			$this->redrawControl('cpu');

			$this->redrawControl('memory');

			$this->redrawControl('fileSystem');

			$this->redrawControl('network');
		} else {
			$this->terminate();
		}
	}

	/**
	 * Zobrazeni informaci o serveru
	 */
	public function renderServer()
	{
		$this->addBreadcrumbLink('dockbar.info.server');
		$this->template->refresh = $this->refresh;
		$this->template->system = $this->app->info->system;
		$this->template->fileSystem = $this->app->info->fileSystem;
		$this->template->hardware = $this->app->info->hardware;
		$this->template->memory = $this->app->info->memory;
		$this->template->network = $this->app->info->network;
	}

	/**
	 * Zobrazeni informaci o PHP
	 */
	public function renderPhp()
	{
		$this->addBreadcrumbLink('dockbar.info.php');
		$this->template->php = $this->app->info->phpInfo;
	}

}
