<?php

namespace NAttreid\Crm\Control;

use NAttreid\AppManager\AppManager;
use NAttreid\Form\Form;
use Nette\Utils\ArrayHash;

/**
 * Databaze
 *
 * @author Attreid <attreid@gmail.com>
 */
class DatabasePresenter extends CrmPresenter
{

	/** @var AppManager */
	private $app;

	public function __construct(AppManager $app)
	{
		parent::__construct();
		$this->app = $app;
	}

	/**
	 * Formular nahrani databaze
	 * @return Form
	 */
	protected function createComponentUploadForm()
	{
		$form = $this->formFactory->create();

		$form->addUpload('sql', 'crm.database.file')
			->setRequired();

		$form->addSubmit('upload', 'crm.database.upload');

		$form->onSuccess[] = [$this, 'uploadFormSucceeded'];

		return $form;
	}

	/**
	 *
	 * @param Form $form
	 * @param ArrayHash $values
	 */
	public function uploadFormSucceeded(Form $form, $values)
	{
		$uploaded = $this->app->loadDatabase($values->sql);
		if ($uploaded) {
			$this->app->invalidateCache();
			$this->flashNotifier->success('crm.database.uploaded');
		} else {
			$this->flashNotifier->error('crm.database.error');
		}
	}

}
