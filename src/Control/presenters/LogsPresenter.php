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
		$this->app = $app;
		$this->logs = $logs;
	}

	/**
	 * Zobrazeni seznamu
	 */
	public function renderDefault()
	{
		$this->addBreadcrumbLink('main.dockbar.info.logs');
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
	 */
	public function actionDownloadFile($id)
	{
		$this->sendResponse($this->logs->downloadFile($id));
	}

	/**
	 * Smazani logu
	 * @secured
	 */
	public function handleDelete($id)
	{
		if ($this->isAjax()) {
			$this->logs->delete($id);

			$grid = $this['logsList'];
			/* @var $grid DataGrid */
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
	 * @return DataGrid
	 */
	protected function createComponentLogsList($name)
	{
		$grid = $this->dataGridFactory->create($this, $name);

		$grid->setDataSource($this->logs->getLogs());

		$grid->setDefaultSort(['changed' => 'DESC']);

		$clearLogs = $grid->addToolbarButton('clearLogs!', 'main.logs.clearLogs');
		$clearLogs->setClass($clearLogs->getClass() . ' ajax');

		$grid->addColumnText('name', 'main.logs.log')
			->setFilterText();

		$grid->addColumnText('size', 'main.logs.size');

		$grid->addColumnDateTime('changed', 'main.logs.lastChange')
			->setSortable()
			->setRenderer(function ($row) {
				return Date::getDateTime($row['changed']);
			});

		$grid->addAction('showFile', NULL)
			->addAttributes(['target' => '_blank'])
			->setIcon('edit')
			->setTitle('main.logs.show');

		$grid->addAction('downloadFile', NULL)
			->setIcon('download')
			->setTitle('main.logs.download');

		$grid->addAction('delete', NULL, 'delete!')
			->setIcon('trash')
			->setTitle('default.delete')
			->setClass('btn btn-xs btn-danger ajax')
			->setConfirm(function ($item) {
				return $this->translate('main.logs.confirmDelete', 1, ['name' => $item['name']]);
			});

		$grid->addGroupAction('main.logs.download')->onSelect[] = [$this, 'actionDownloadFile'];
		$grid->addGroupAction('default.delete')->onSelect[] = [$this, 'handleDelete'];

		return $grid;
	}

}
