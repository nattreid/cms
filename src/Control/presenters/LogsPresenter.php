<?php

namespace NAttreid\Crm\Control;

use NAttreid\AppManager\AppManager;
use NAttreid\AppManager\Logs;
use NAttreid\Utils\Date;
use Ublaboo\DataGrid\DataGrid;

/**
 * Logy
 *
 * @author Attreid <attreid@gmail.com>
 */
class LogsPresenter extends CrmPresenter
{

	/** @var AppManager */
	private $app;

	/** @var Logs */
	private $logs;

	public function __construct(AppManager $app, Logs $logs)
	{
		parent::__construct();
		$this->app = $app;
		$this->logs = $logs;
	}

	/**
	 * Zobrazeni seznamu
	 */
	public function renderDefault()
	{
		$this->addBreadcrumbLink('dockbar.info.logs');
	}

	/**
	 * Zobrazeni souboru
	 * @param int $id
	 */
	public function actionShowFile($id)
	{
		$this->sendResponse($this->logs->getFile($id));
	}

	/**
	 * Stazeni souboru
	 * @param int $id
	 */
	public function actionDownloadFile($id)
	{
		$this->sendResponse($this->logs->downloadFile($id));
	}

	/**
	 * Smazani logu
	 * @param int $id
	 * @secured
	 */
	public function handleDelete($id)
	{
		if ($this->isAjax()) {
			$this->logs->delete($id);

			$grid = $this['logsList'];
			$grid->setDataSource($this->logs->getLogs());
			$grid->reload();
		} else {
			$this->terminate();
		}
	}

	/**
	 * Smazani logu
	 */
	public function handleClearLogs()
	{
		if ($this->isAjax()) {
			$this->app->clearLog();
			$this['logsList']->redrawControl();
		} else {
			$this->terminate();
		}
	}

	/**
	 * Seznamu logu
	 * @param string $name
	 * @return DataGrid
	 */
	protected function createComponentLogsList($name)
	{
		$grid = $this->dataGridFactory->create($this, $name);

		$grid->setDataSource($this->logs->getLogs());

		$grid->setDefaultSort(['changed' => 'DESC']);

		$clearLogs = $grid->addToolbarButton('clearLogs!', 'crm.logs.clearLogs');
		$clearLogs->setClass($clearLogs->getClass() . ' ajax');

		$grid->addColumnText('name', 'crm.logs.log')
			->setFilterText();

		$grid->addColumnText('size', 'crm.logs.size');

		$grid->addColumnDateTime('changed', 'crm.logs.lastChange')
			->setSortable()
			->setRenderer(function ($row) {
				return Date::getDateTime($row['changed']);
			});

		$grid->addAction('showFile', null)
			->addAttributes(['target' => '_blank'])
			->setIcon('edit')
			->setTitle('crm.logs.show');

		$grid->addAction('downloadFile', null)
			->setIcon('download')
			->setTitle('crm.logs.download');

		$grid->addAction('delete', null, 'delete!')
			->setIcon('trash')
			->setTitle('default.delete')
			->setClass('btn btn-xs btn-danger ajax')
			->setConfirm(function ($item) {
				return $this->translate('crm.logs.confirmDelete', 1, ['name' => $item['name']]);
			});

		$grid->addGroupAction('crm.logs.download')->onSelect[] = [$this, 'actionDownloadFile'];
		$grid->addGroupAction('default.delete')->onSelect[] = [$this, 'handleDelete'];

		return $grid;
	}

}
