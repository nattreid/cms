<?php

declare(strict_types=1);

namespace NAttreid\Cms\Control;

use NAttreid\FileManager\FileManager;
use NAttreid\FileManager\IFileManagerFactory;

/**
 * FileManager
 *
 * @author Attreid <attreid@gmail.com>
 */
class FileManagerPresenter extends CmsPresenter
{

	/** @var string */
	private $basePath;

	/** @var IFileManagerFactory */
	private $fileManagerFactory;

	public function __construct(string $basePath, IFileManagerFactory $fileManagerFactory)
	{
		parent::__construct();
		$this->basePath = $basePath;
		$this->fileManagerFactory = $fileManagerFactory;
	}

	/**
	 * Zobrazeni seznamu
	 */
	public function renderDefault(): void
	{
		$this->addBreadcrumbLink('dockbar.application.fileManager');
	}

	/**
	 * Komponenta mangeru
	 * @return FileManager
	 */
	protected function createComponentFileManager(): FileManager
	{
		$manager = $this->fileManagerFactory->create($this->basePath);

		$manager->editable($this->user->isAllowed('dockbar.application.fileManager', 'edit'));

		$manager->getTranslator()->setLang($this->locale);

		return $manager;
	}

}
