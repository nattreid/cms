<?php

namespace NAttreid\Crm\Control;

use NAttreid\Filemanager\FileManager,
    NAttreid\Filemanager\IFileManagerFactory;

/**
 * FileManager
 *
 * @author Attreid <attreid@gmail.com>
 */
class FileManagerPresenter extends CrmPresenter {

    /** @var string */
    private $basePath;

    /** @var IFileManagerFactory */
    private $fileManagerFactory;

    public function __construct($basePath, IFileManagerFactory $fileManagerFactory) {
        $this->basePath = $basePath;
        $this->fileManagerFactory = $fileManagerFactory;
    }

    /**
     * Zobrazeni seznamu
     */
    public function renderDefault() {
        $this->addBreadcrumbLink('main.dockbar.application.fileManager');
    }

    /**
     * Komponenta mangeru
     * @return FileManager
     */
    protected function createComponentFileManager() {
        $manager = $this->fileManagerFactory->create($this->basePath);

        $manager->editable($this->user->isAllowed('main.dockbar.application.fileManager', 'edit'));

        $manager->getTranslator()->setLang($this->locale);

        return $manager;
    }

}