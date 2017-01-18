<?php

namespace NAttreid\Cms\Control;

use NAttreid\AppManager\AppManager;
use NAttreid\Form\Form;
use Nette\Utils\ArrayHash;

/**
 * Databaze
 *
 * @author Attreid <attreid@gmail.com>
 */
class DatabasePresenter extends CmsPresenter
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

		$form->addUpload('sql', 'cms.database.file')
			->setRequired();

		$form->addSubmit('upload', 'cms.database.upload');

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
			$this->flashNotifier->success('cms.database.uploaded');
		} else {
			$this->flashNotifier->error('cms.database.error');
		}
	}

}
