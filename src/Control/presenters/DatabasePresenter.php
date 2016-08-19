<?php

namespace NAttreid\Crm\Control;

use NAttreid\Form\Form,
    NAttreid\AppManager\AppManager,
    Nette\Utils\ArrayHash;

/**
 * Databaze
 *
 * @author Attreid <attreid@gmail.com>
 */
class DatabasePresenter extends CrmPresenter {

    /** @var AppManager */
    private $app;

    public function __construct(AppManager $app) {
        $this->app = $app;
    }

    /**
     * Formular nahrani databaze
     * @return Form
     */
    protected function createComponentUploadForm() {
        $form = $this->formFactory->create();

        $form->addUpload('sql', 'main.database.file')
                ->setRequired();

        $form->addSubmit('upload', 'main.database.upload');

        $form->onSuccess[] = [$this, 'uploadFormSucceeded'];

        return $form;
    }

    /**
     * 
     * @param Form $form
     * @param ArrayHash $values
     */
    public function uploadFormSucceeded(Form $form, $values) {
        $uploaded = $this->app->loadDatabase($values->sql);
        if ($uploaded) {
            $this->app->cleanModelCache();
            $this->flashNotifier->success('main.database.uploaded');
        } else {
            $this->flashNotifier->error('main.database.error');
        }
    }

}
