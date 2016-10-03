<?php

namespace NAttreid\Crm\Control;

use NAttreid\Form\Form;
use NAttreid\Security\Model\Orm;
use NAttreid\Security\Model\User;
use Nette\Security\AuthenticationException;
use Nette\Utils\ArrayHash;
use Nextras\Dbal\UniqueConstraintViolationException;
use Nextras\Orm\Model\Model;

/**
 * Profil
 *
 * @author Attreid <attreid@gmail.com>
 */
class ProfilePresenter extends CrmPresenter
{

	/** @var int */
	private $minPasswordLength;

	/** @var Orm */
	private $orm;

	/** @var User */
	private $profile;

	public function __construct($minPasswordLength, Model $orm)
	{
		parent::__construct();
		$this->minPasswordLength = $minPasswordLength;
		$this->orm = $orm;
	}

	public function actionDefault()
	{
		$this->profile = $this->orm->users->getById($this->user->getId());
		if (!$this->profile) {
			$this->error();
		}
	}

	public function renderDefault()
	{
		$this->addBreadcrumbLink('main.dockbar.myProfile');
	}

	/**
	 * Formular uzivatele
	 * @return Form
	 */
	protected function createComponentUserForm()
	{
		$form = $this->formFactory->create();
		$form->setAjaxRequest();

		$form->addProtection();
		$form->addHidden('id', $this->profile->id);

		$form->addText('username', 'main.user.username')
			->setDisabled()
			->setDefaultValue($this->profile->username);
		$form->addText('firstName', 'main.user.firstName')
			->setDefaultValue($this->profile->firstName);
		$form->addText('surname', 'main.user.surname')
			->setDefaultValue($this->profile->surname);
		$form->addText('email', 'main.user.email')
			->setDefaultValue($this->profile->email)
			->setRequired()
			->addRule(Form::EMAIL);

		$form->addSubmit('save', 'form.save');

		$form->onError[] = function (Form $form) {
			if ($this->isAjax()) {
				$this->redrawControl('userForm');
			}
		};

		$form->onSuccess[] = [$this, 'userFormSucceeded'];

		return $form;
	}

	/**
	 * Ulozeni profilu
	 * @param Form $form
	 * @param ArrayHash $values
	 */
	public function userFormSucceeded(Form $form, $values)
	{
		try {
			$this->profile->firstName = $values->firstName;
			$this->profile->surname = $values->surname;
			$this->profile->setEmail($values->email);

			$this->orm->persistAndFlush($this->profile);

			$this->flashNotifier->success('main.user.dataSaved');
		} catch (UniqueConstraintViolationException $ex) {
			$form->addError('main.user.dupliciteEmail');
		}

		if ($this->isAjax()) {
			$this->redrawControl('userForm');
		}
	}

	/**
	 * Formular zmeny hesla
	 * @return \Nette\Application\UI\Form
	 */
	protected function createComponentPasswordForm()
	{
		$form = $this->formFactory->create();
		$form->setAjaxRequest();

		$form->addHidden('id', $this->profile->id);

		$form->addPassword('oldPassword', 'main.user.oldPassword')
			->setRequired();

		$form->addPassword('password', 'main.user.newPassword')
			->setRequired()
			->addRule(Form::MIN_LENGTH, null, $this->minPasswordLength);
		$form->addPassword('passwordVerify', 'main.user.passwordVerify')
			->setRequired()
			->addRule(Form::EQUAL, null, $form['password']);

		$form->addProtection();

		$form->addSubmit('save', 'form.save');

		$form->onSuccess[] = [$this, 'passwordFormSucceeded'];

		return $form;
	}

	/**
	 * Zmena hesla
	 * @param Form $form
	 * @param ArrayHash $values
	 */
	public function passwordFormSucceeded(Form $form, $values)
	{
		try {
			$this->profile->setPassword($values->password, $values->oldPassword);
			$this->flashNotifier->success('main.user.passwordChanged');
		} catch (AuthenticationException $e) {
			$form->addError('main.user.incorrectPassword');
		}
		if ($this->isAjax()) {
			$this->redrawControl('passwordForm');
		}
	}

}
