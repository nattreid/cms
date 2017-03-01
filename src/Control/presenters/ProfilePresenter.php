<?php

declare(strict_types = 1);

namespace NAttreid\Cms\Control;

use InvalidArgumentException;
use NAttreid\Cms\LocaleService;
use NAttreid\Form\Form;
use NAttreid\Security\Model\Orm;
use NAttreid\Security\Model\Users\User;
use NAttreid\Utils\Strings;
use Nette\Security\AuthenticationException;
use Nette\Utils\ArrayHash;
use Nextras\Dbal\UniqueConstraintViolationException;
use Nextras\Orm\Model\Model;

/**
 * Profil
 *
 * @author Attreid <attreid@gmail.com>
 */
class ProfilePresenter extends CmsPresenter
{

	/** @var int */
	private $minPasswordLength;

	/** @var Orm */
	private $orm;

	/** @var User */
	private $profile;

	/** @var LocaleService */
	private $localeService;

	public function __construct(int $minPasswordLength, Model $orm, LocaleService $localeService)
	{
		parent::__construct();
		$this->minPasswordLength = $minPasswordLength;
		$this->orm = $orm;
		$this->localeService = $localeService;
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
		$this->addBreadcrumbLink('dockbar.myProfile');
	}

	/**
	 * Formular uzivatele
	 * @return Form
	 */
	protected function createComponentUserForm(): Form
	{
		$form = $this->formFactory->create();
		$form->setAjaxRequest();

		$form->addProtection();

		$form->addText('username', 'cms.user.username')
			->setDisabled()
			->setDefaultValue($this->profile->username);
		$form->addText('firstName', 'cms.user.firstName')
			->setDefaultValue($this->profile->firstName);
		$form->addText('surname', 'cms.user.surname')
			->setDefaultValue($this->profile->surname);
		$form->addText('email', 'cms.user.email')
			->setDefaultValue($this->profile->email)
			->setRequired()
			->addRule(Form::EMAIL);
		$form->addPhone('phone', 'cms.user.phone')
			->setDefaultValue($this->profile->phone);

		$language = $form->addSelectUntranslated('language', 'cms.user.language', $this->localeService->allowed, 'form.none');
		$locale = $this->localeService->get($this->profile->language);
		if ($locale) {
			$language->setDefaultValue($locale->id);
		}

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
	public function userFormSucceeded(Form $form, ArrayHash $values)
	{
		try {
			$this->profile->setEmail($values->email);
		} catch (UniqueConstraintViolationException $ex) {
			$form->addError('cms.user.dupliciteEmail');
			return;
		} catch (InvalidArgumentException $ex) {
			$form->addError('cms.user.invalideEmail');
			return;
		}

		try {
			$this->profile->setPhone(Strings::ifEmpty($values->phone));
		} catch (InvalidArgumentException $ex) {
			$form->addError('cms.user.invalidePhone');
			return;
		}

		$this->profile->firstName = $values->firstName;
		$this->profile->surname = $values->surname;

		$language = $this->localeService->getById($values->language);
		$this->profile->language = $language === null ? null : $language->name;

		$this->orm->persistAndFlush($this->profile);

		$this->flashNotifier->success('cms.user.dataSaved');


		if ($this->isAjax()) {
			$this->redrawControl('userForm');
		}
	}

	/**
	 * Formular zmeny hesla
	 * @return Form
	 */
	protected function createComponentPasswordForm(): Form
	{
		$form = $this->formFactory->create();
		$form->setAjaxRequest();

		$form->addPassword('oldPassword', 'cms.user.oldPassword')
			->setRequired();

		$form->addPassword('password', 'cms.user.newPassword')
			->setRequired()
			->addRule(Form::MIN_LENGTH, null, $this->minPasswordLength);
		$form->addPassword('passwordVerify', 'cms.user.passwordVerify')
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
	public function passwordFormSucceeded(Form $form, ArrayHash $values)
	{
		try {
			$this->profile->setPassword($values->password, $values->oldPassword);
			$this->orm->persistAndFlush($this->profile);
			$this->flashNotifier->success('cms.user.passwordChanged');
		} catch (AuthenticationException $e) {
			$form->addError('cms.user.incorrectPassword');
		}
		if ($this->isAjax()) {
			$this->redrawControl('passwordForm');
		}
	}

}
