<?php

namespace NAttreid\Crm\Control;

use NAttreid\AppManager\Info;

/**
 * Informace o serveru, php, atd
 *
 * @author Attreid <attreid@gmail.com>
 */
class InfoPresenter extends CrmPresenter
{

	/** @var int */
	private $refresh;

	/** @var Info */
	private $info;

	public function __construct($refresh, Info $info)
	{
		parent::__construct();
		$this->refresh = $refresh * 1000;
		$this->info = $info;
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
		$this->template->server = $this->info->getServerInfo();
	}

	/**
	 * Zobrazeni informaci o PHP
	 */
	public function renderPhp()
	{
		$this->addBreadcrumbLink('dockbar.info.php');
		$this->template->php = $this->info->getPhpInfo();
	}

}
