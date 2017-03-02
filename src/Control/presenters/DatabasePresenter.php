<?php

declare(strict_types = 1);

namespace NAttreid\Cms\Control;

use NAttreid\AppManager\AppManager;
use NAttreid\Form\Form;
use Nette\NotSupportedException;
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
	protected function createComponentUploadForm(): Form
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
	public function uploadFormSucceeded(Form $form, ArrayHash $values)
	{
		try {
			$this->app->loadDatabase((string)$values->sql);

			$this->app->invalidateCache();
			$this->flashNotifier->success('cms.database.uploaded');
		} catch (NotSupportedException $ex) {
			$this->flashNotifier->error('cms.database.error');
		}
	}

}
