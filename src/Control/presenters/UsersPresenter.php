<?php

namespace NAttreid\Crm\Control;

use NAttreid\Crm\LocaleService;
use NAttreid\Crm\Mailing\Mailer;
use NAttreid\Form\Form;
use NAttreid\Security\Model\Orm;
use NAttreid\Security\Model\User;
use NAttreid\Utils\Strings;
use Nette\InvalidArgumentException;
use Nette\Utils\ArrayHash;
use Nette\Utils\Html;
use Nette\Utils\Random;
use Nextras\Dbal\UniqueConstraintViolationException;
use Nextras\Orm\Model\Model;
use Ublaboo\DataGrid\DataGrid;

/**
 * Uzivatele
 *
 * @author Attreid <attreid@gmail.com>
 */
class UsersPresenter extends CrmPresenter
{

	/** @var string */
	private $passwordChars;

	/** @var int */
	private $minPasswordLength;

	/** @var Orm */
	private $orm;

	/** @var Mailer */
	private $mailer;

	/** @var LocaleService */
	private $localeService;

	/** @var User */
	private $user;

	public function __construct($passwordChars, $minPasswordLength, Model $orm, Mailer $mailer, LocaleService $localeService)
	{
		parent::__construct();
		$this->passwordChars = $passwordChars;
		$this->minPasswordLength = $minPasswordLength;
		$this->orm = $orm;
		$this->mailer = $mailer;
		$this->localeService = $localeService;
	}

	public function handleBack($backlink)
	{
		$this->redirect('default');
	}

	public function actionEdit($id)
	{
		$this->user = $this->orm->users->getById($id);
		if (!$this->user) {
			$this->error();
		}
	}

	public function actionChangePassword($id)
	{
		$this->actionEdit($id);
	}

	/**
	 * Zobrazeni seznamu
	 */
	public function renderDefault()
	{
		$this->addBreadcrumbLink('dockbar.settings.users');
	}

	/**
	 * Novy uzivatel
	 */
	public function renderAdd()
	{
		$this->addBreadcrumbLink('dockbar.settings.users', ':Crm:Users:');
		$this->addBreadcrumbLink('crm.user.add');
	}

	/**
	 * Editace uzivatele
	 */
	public function renderEdit()
	{
		$this->addBreadcrumbLink('dockbar.settings.users', ':Crm:Users:');
		$this->addBreadcrumbLink('crm.user.edit');
	}

	/**
	 * Zmena hesla
	 */
	public function renderChangePassword()
	{
		$this->addBreadcrumbLink('dockbar.settings.users', ':Crm:Users:');
		$this->addBreadcrumbLink('crm.user.changePassword');
	}

	/**
	 * Prepne na uzivatele pod id
	 * @param int $id
	 * @secured
	 */
	public function handleTryUser($id)
	{
		$this['tryUser']->set($id);
		$this->restoreBacklink();
	}

	/**
	 * Smaze uzivatele
	 * @param int $id
	 * @secured
	 */
	public function handleDelete($id)
	{
		if ($this->isAjax()) {
			$user = $this->orm->users->getById($id);
			$this->orm->users->removeAndFlush($user);
			$this['userList']->reload();
		} else {
			$this->terminate();
		}
	}

	/**
	 * Ulozi stav uzivatele
	 * @param int $id
	 * @param boolean $value
	 */
	public function setState($id, $value)
	{
		if ($this->isAjax()) {
			$user = $this->orm->users->getById($id);
			$user->active = $value;
			$this->orm->persistAndFlush($user);
			$this['userList']->redrawItem($id);
		} else {
			$this->terminate();
		}
	}

	/**
	 * Smaze uzivatele
	 * @param array $ids
	 */
	public function deleteUsers(array $ids)
	{
		if ($this->isAjax()) {
			$users = $this->orm->users->findById($ids);
			foreach ($users as $user) {
				$this->orm->users->remove($user);
			}
			$this->orm->flush();
			$this['userList']->reload();
		} else {
			$this->terminate();
		}
	}

	/**
	 * Aktivuje uzivatele
	 * @param array $ids
	 */
	public function activateUsers(array $ids)
	{
		if ($this->isAjax()) {
			$users = $this->orm->users->findById($ids);
			foreach ($users as $user) {
				$user->active = true;
				$this->orm->users->persist($user);
			}
			$this->orm->flush();
			$this['userList']->reload();
		} else {
			$this->terminate();
		}
	}

	/**
	 * Deaktivuje uzivatele
	 * @param array $ids
	 */
	public function deactivateUsers(array $ids)
	{
		if ($this->isAjax()) {
			$users = $this->orm->users->findById($ids);
			foreach ($users as $user) {
				$user->active = false;
				$this->orm->users->persist($user);
			}
			$this->orm->flush();
			$this['userList']->reload();
		} else {
			$this->terminate();
		}
	}

	/**
	 * Formular pridani uzivatele
	 * @return Form
	 */
	protected function createComponentAddForm()
	{
		$form = $this->formFactory->create();
		$form->addProtection();

		$form->addText('username', 'crm.user.username')
			->setRequired();
		$form->addText('firstName', 'crm.user.firstName');
		$form->addText('surname', 'crm.user.surname');
		$form->addText('email', 'crm.user.email')
			->setRequired()
			->addRule(Form::EMAIL);
		$form->addPhone('phone', 'crm.user.phone');
		$form->addSelectUntranslated('language', 'crm.user.language', $this->localeService->allowed, 'form.none');

		$form->addMultiSelectUntranslated('roles', 'crm.permissions.roles', $this->orm->aclRoles->fetchPairs())
			->setRequired();

		if ($this->configurator->sendNewUserPassword) {
			$form->addCheckbox('generatePassword', 'crm.user.generatePassword')
				->addCondition($form::EQUAL, false)
				->toggle('password')
				->toggle('passwordVerify');
		} else {
			$form->addHidden('generatePassword', false);
		}

		$form->addPassword('password', 'crm.user.newPassword')
			->setOption('id', 'password')
			->addConditionOn($form['generatePassword'], Form::EQUAL, false)
			->setRequired()
			->addRule(Form::MIN_LENGTH, null, $this->minPasswordLength)
			->endCondition();
		$form->addPassword('passwordVerify', 'crm.user.passwordVerify')
			->setOption('id', 'passwordVerify')
			->addConditionOn($form['generatePassword'], Form::EQUAL, false)
			->setRequired()
			->addRule(Form::EQUAL, null, $form['password'])
			->endCondition();

		$form->addSubmit('save', 'form.save');
		$form->addLink('back', 'form.back', $this->getBacklink());

		$form->onSuccess[] = [$this, 'addFormSucceeded'];
		return $form;
	}

	/**
	 * Zpracovani noveho uzivatele
	 * @param Form $form
	 * @param ArrayHash $values
	 */
	public function addFormSucceeded(Form $form, $values)
	{
		if ($values->generatePassword) {
			$password = Random::generate($this->minPasswordLength, $this->passwordChars);
		} else {
			$password = $values->password;
		}

		$user = new User;
		$this->orm->users->attach($user);
		try {
			$user->setUsername($values->username);
		} catch (UniqueConstraintViolationException $ex) {
			$form->addError('crm.user.dupliciteUsername');
			return;
		} catch (InvalidArgumentException $ex) {
			$form->addError('crm.user.invalideUsername');
			return;
		}

		try {
			$user->setEmail($values->email);
		} catch (UniqueConstraintViolationException $ex) {
			$form->addError('crm.user.dupliciteEmail');
			return;
		} catch (InvalidArgumentException $ex) {
			$form->addError('crm.user.invalideUsername');
			return;
		}

		try {
			$user->setPhone(Strings::ifEmpty($values->phone));
		} catch (InvalidArgumentException $ex) {
			$form->addError('crm.user.invalidePhone');
			return;
		}

		$user->firstName = $values->firstName;
		$user->surname = $values->surname;
		$user->language = $values->language;
		$user->roles->set($values->roles);
		$user->setPassword($password);

		$this->orm->persistAndFlush($user);

		if ($this->configurator->sendNewUserPassword) {
			$this->mailer->sendNewUser($user->email, $user->username, $password);
		}

		$this->flashNotifier->success('crm.user.dataSaved');
		$this->restoreBacklink();
	}

	/**
	 * Formular editace uzivatele
	 * @return Form
	 */
	protected function createComponentEditForm()
	{
		$form = $this->formFactory->create();
		$form->addProtection();

		$form->addText('username', 'crm.user.username')
			->setDefaultValue($this->user->username)
			->setRequired();
		$form->addText('firstName', 'crm.user.firstName')
			->setDefaultValue($this->user->firstName);
		$form->addText('surname', 'crm.user.surname')
			->setDefaultValue($this->user->surname);
		$form->addText('email', 'crm.user.email')
			->setDefaultValue($this->user->email)
			->setRequired()
			->addRule(Form::EMAIL);
		$form->addPhone('phone', 'crm.user.phone')
			->setDefaultValue($this->user->phone);

		$language = $form->addSelectUntranslated('language', 'crm.user.language', $this->localeService->allowed, 'form.none');
		$locale = $this->localeService->get($this->user->language);
		if ($locale) {
			$language->setDefaultValue($locale->id);
		}

		$form->addMultiSelectUntranslated('roles', 'crm.permissions.roles', $this->orm->aclRoles->fetchPairs())
			->setDefaultValue($this->user->roles->getRawValue())
			->setRequired();

		$form->addSubmit('save', 'form.save');
		$form->addLink('back', 'form.back', $this->getBacklink());

		$form->onSuccess[] = [$this, 'editFormSucceeded'];

		return $form;
	}

	/**
	 * Zpracovani editace uzivatele
	 * @param Form $form
	 * @param ArrayHash $values
	 */
	public function editFormSucceeded(Form $form, $values)
	{
		try {
			$this->user->setUsername($values->username);
		} catch (UniqueConstraintViolationException $ex) {
			$form->addError('crm.user.dupliciteUsername');
			return;
		} catch (InvalidArgumentException $ex) {
			$form->addError('crm.user.invalideUsername');
			return;
		}

		try {
			$this->user->setEmail($values->email);
		} catch (UniqueConstraintViolationException $ex) {
			$form->addError('crm.user.dupliciteEmail');
			return;
		} catch (InvalidArgumentException $ex) {
			$form->addError('crm.user.invalideUsername');
			return;
		}

		try {
			$this->user->setPhone(Strings::ifEmpty($values->phone));
		} catch (InvalidArgumentException $ex) {
			$form->addError('crm.user.invalidePhone');
			return;
		}

		$this->user->firstName = $values->firstName;
		$this->user->surname = $values->surname;
		$this->user->roles->set($values->roles);

		$language = $this->localeService->getById($values->language);
		$this->user->language = $language === null ? null : $language->name;

		$this->orm->persistAndFlush($this->user);

		$this->flashNotifier->success('crm.user.dataSaved');
		$this->restoreBacklink();
	}

	/**
	 * Zmena hesla
	 * @return Form
	 */
	protected function createComponentPasswordForm()
	{
		$form = $this->formFactory->create();
		$form->addProtection();

		$form->addText('username', 'crm.user.username')
			->setDisabled()
			->setDefaultValue($this->user->username);

		if ($this->configurator->sendNewUserPassword) {
			$form->addCheckbox('generatePassword', 'crm.user.generatePassword')
				->addCondition($form::EQUAL, false)
				->toggle('password')
				->toggle('passwordVerify');
		} else {
			$form->addHidden('generatePassword', false);
		}

		$form->addPassword('password', 'crm.user.newPassword')
			->setOption('id', 'password')
			->addConditionOn($form['generatePassword'], Form::EQUAL, false)
			->setRequired()
			->addRule(Form::MIN_LENGTH, null, $this->minPasswordLength)
			->endCondition();
		$form->addPassword('passwordVerify', 'crm.user.passwordVerify')
			->setOption('id', 'passwordVerify')
			->addConditionOn($form['generatePassword'], Form::EQUAL, false)
			->setRequired()
			->addRule(Form::EQUAL, null, $form['password'])
			->endCondition();

		$form->addSubmit('save', 'form.save');
		$form->addLink('back', 'form.back', $this->getBacklink());

		$form->onSuccess[] = [$this, 'passwordFormSucceeded'];
		return $form;
	}

	/**
	 * Zpracovani zmeny hesla
	 * @param Form $form
	 * @param ArrayHash $values
	 */
	public function passwordFormSucceeded(Form $form, $values)
	{
		if ($values->generatePassword) {
			$password = Random::generate($this->minPasswordLength, $this->passwordChars);
		} else {
			$password = $values->password;
		}

		$this->user->setPassword($password);

		$this->orm->persistAndFlush($this->user);

		if ($this->configurator->sendChangePassword) {
			$this->mailer->sendNewPassword($this->user->email, $this->user->username, $password);
		}

		$this->flashNotifier->success('crm.user.passwordChanged');
		$this->restoreBacklink();
	}

	/**
	 * Seznam uzivatelu
	 * @param string $name
	 * @return DataGrid
	 */
	protected function createComponentUserList($name)
	{
		$grid = $this->dataGridFactory->create($this, $name);

		$grid->setDataSource($this->orm->users->findAll());

		$grid->addToolbarButton('add', 'crm.user.add');

		$grid->addColumnText('username', 'crm.user.username')
			->setSortable()
			->setFilterText();

		$grid->addColumnText('firstName', 'crm.user.firstName')
			->setSortable()
			->setFilterText();

		$grid->addColumnText('surname', 'crm.user.surname')
			->setSortable()
			->setFilterText();

		$grid->addColumnText('email', 'crm.user.email')
			->setSortable()
			->setFilterText();

		$grid->addColumnText('roles', 'crm.permissions.roles', 'roles.id')
			->setRenderer(function (User $user) {
				$obj = new Html;
				$delimiter = '';
				foreach ($user->roles as $role) {
					$link = Html::el('a');
					$link->href = $this->link('Permissions:editRolePermissions', ['id' => $role->id]);
					$link->addText($role->title);
					$obj->addHtml($delimiter . $link);
					$delimiter = ', ';
				}
				return $obj;
			})
			->setFilterSelect(['' => $this->translate('form.none')] + $this->orm->aclRoles->fetchPairs());

		$active = [
			'' => 'form.none',
			1 => 'default.active',
			0 => 'default.deactive'
		];
		$state = $grid->addColumnStatus('active', 'crm.user.state');
		$state->setFilterSelect($active)
			->setTranslateOptions();
		$state->addOption(1, 'default.active')
			->setClass('btn-success');
		$state->addOption(0, 'default.deactive')
			->setClass('btn-danger');
		$state->onChange[] = [$this, 'setState'];

		if ($this['tryUser']->isAllowed()) {
			$grid->addAction('tryUser', null, 'tryUser!')
				->addAttributes(['target' => '_blank'])
				->setIcon('user')
				->setTitle('crm.user.tryUser');
		}

		$grid->addAction('edit', null)
			->setIcon('pencil')
			->setTitle('crm.user.edit');

		$grid->addAction('changePassword', null)
			->setIcon('wrench')
			->setTitle('crm.user.changePassword');

		$grid->addAction('delete', null, 'delete!')
			->setIcon('trash')
			->setTitle('default.delete')
			->setClass('btn btn-xs btn-danger ajax')
			->setConfirm(function (User $user) {
				return $this->translate('default.confirmDelete', null, ['name' => $user->fullName]);
			});

		$grid->addGroupAction('default.activate')->onSelect[] = [$this, 'activateUsers'];
		$grid->addGroupAction('default.deactivate')->onSelect[] = [$this, 'deactivateUsers'];
		$grid->addGroupAction('default.delete')->onSelect[] = [$this, 'deleteUsers'];

		return $grid;
	}

}
