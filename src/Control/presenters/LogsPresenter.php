<?php

declare(strict_types = 1);

namespace NAttreid\Cms\Control;

use NAttreid\AppManager\AppManager;
use NAttreid\Utils\Date;
use Ublaboo\DataGrid\DataGrid;

/**
 * Logy
 *
 * @author Attreid <attreid@gmail.com>
 */
class LogsPresenter extends CmsPresenter
{

	/** @var AppManager */
	private $app;

	public function __construct(AppManager $app)
	{
		parent::__construct();
		$this->app = $app;
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
	 * @param string $id
	 */
	public function actionShowFile(string $id)
	{
		$this->sendResponse($this->app->logs->getFile($id));
	}

	/**
	 * Stazeni souboru
	 * @param string[]|string $id
	 */
	public function actionDownloadFile($id)
	{
		$this->sendResponse($this->app->logs->downloadFile($id));
	}

	/**
	 * Smazani logu
	 * @param string[]|string $id
	 * @secured
	 */
	public function handleDelete($id)
	{
		if ($this->isAjax()) {
			$this->app->logs->delete($id);

			$grid = $this['logsList'];
			$grid->setDataSource($this->app->logs->getLogs());
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
	protected function createComponentLogsList(string $name): DataGrid
	{
		$grid = $this->dataGridFactory->create($this, $name);

		$grid->setDataSource($this->app->logs->getLogs());

		$grid->setDefaultSort(['changed' => 'DESC']);

		$clearLogs = $grid->addToolbarButton('clearLogs!', 'cms.logs.clearLogs');
		$clearLogs->setClass($clearLogs->getClass() . ' ajax');
		$clearLogs->addAttributes(['data-confirm' => $this->translate('cms.logs.confirmDelete', 2)]);

		$grid->addColumnText('name', 'cms.logs.log')
			->setFilterText();

		$grid->addColumnText('formatedSize', 'cms.logs.size');

		$grid->addColumnDateTime('changed', 'cms.logs.lastChange')
			->setSortable()
			->setRenderer(function ($row) {
				return Date::getDateTime($row['changed']);
			});

		$grid->addAction('showFile', null)
			->addAttributes(['target' => '_blank'])
			->setIcon('edit')
			->setTitle('cms.logs.show');

		$grid->addAction('downloadFile', null)
			->setIcon('download')
			->setTitle('cms.logs.download');

		$grid->addAction('delete', null, 'delete!')
			->setIcon('trash')
			->setTitle('default.delete')
			->setClass('btn btn-xs btn-danger ajax')
			->setConfirm(function ($item) {
				return $this->translate('cms.logs.confirmDelete', 1, ['name' => $item['name']]);
			});

		$grid->addGroupAction('cms.logs.download')->onSelect[] = [$this, 'actionDownloadFile'];
		$grid->addGroupAction('default.delete')->onSelect[] = [$this, 'handleDelete'];

		return $grid;
	}

}
