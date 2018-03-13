<?php

declare(strict_types=1);

namespace NAttreid\Cms\Control;

use NAttreid\AppManager\AppManager;
use NAttreid\Utils\Date;
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
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
	public function renderDefault(): void
	{
		$this->addBreadcrumbLink('dockbar.info.logs');
	}

	/**
	 * Zobrazeni souboru
	 * @param string $id
	 * @throws AbortException
	 * @throws BadRequestException
	 */
	public function actionShowFile(string $id): void
	{
		$response = $this->app->logs->getFile($id);
		if ($response) {
			$this->sendResponse($response);
		} else {
			$this->error();
		}
	}

	/**
	 * Stazeni souboru
	 * @param string[]|string $id
	 * @throws AbortException
	 * @throws BadRequestException
	 */
	public function actionDownloadFile($id): void
	{
		$this->sendResponse($this->app->logs->downloadFile($id));
	}

	/**
	 * Smazani logu
	 * @param string[]|string $id
	 * @secured
	 * @throws AbortException
	 */
	public function handleDelete($id): void
	{
		if ($this->isAjax()) {
			$this->app->logs->delete($id);
			$grid = $this['logsList'];
			$grid->setDataSource($this->app->logs->logs);
			$grid->reload();
		} else {
			$this->terminate();
		}
	}

	/**
	 * Smazani logu
	 * @throws AbortException
	 */
	public function handleClearLogs(): void
	{
		if ($this->isAjax()) {
			$this->app->logs->delete();
			$grid = $this['logsList'];
			$grid->setDataSource($this->app->logs->logs);
			$grid->reload();
		} else {
			$this->terminate();
		}
	}

	/**
	 * Seznamu logu
	 * @param string $name
	 * @return DataGrid
	 * @throws \Ublaboo\DataGrid\Exception\DataGridException
	 */
	protected function createComponentLogsList(string $name): DataGrid
	{
		$grid = $this->dataGridFactory->create($this, $name);

		$grid->setDataSource($this->app->logs->logs);

		$grid->setDefaultSort(['changed' => 'DESC']);

		$clearLogs = $grid->addToolbarButton('clearLogs!', 'cms.logs.clearLogs');
		$clearLogs->setClass($clearLogs->getClass() . ' ajax');
		$clearLogs->addAttributes(['data-confirm' => $this->translate('cms.logs.confirmDelete', 2)]);

		$grid->addColumnText('name', 'cms.logs.log')
			->setFilterText();

		$grid->addColumnText('formatedSize', 'cms.logs.size');

		$grid->addColumnDateTime('changed', 'cms.logs.lastChange')
			->setSortable()
			->setFormat(Date::getFormat());

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
