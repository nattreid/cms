<?php

namespace NAttreid\Crm\Control;

use NAttreid\Crm\Mailing\Mailer;
use NAttreid\Form\Form;
use NAttreid\Security\Model\Orm;
use NAttreid\Security\Model\User;
use Nette\Forms\Container;
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

	public function __construct($passwordChars, $minPasswordLength, Model $orm, Mailer $mailer)
	{
		parent::__construct();
		$this->passwordChars = $passwordChars;
		$this->minPasswordLength = $minPasswordLength;
		$this->orm = $orm;
		$this->mailer = $mailer;
	}

	/**
	 * Zobrazeni seznamu
	 */
	public function renderDefault()
	{
		$this->addBreadcrumbLink('dockbar.settings.users');
		$this->storeBacklink();
	}

	/**
	 * Zmena hesla
	 * @param int $id
	 */
	public function renderChangePassword($id)
	{
		$this->addBreadcrumbLink('dockbar.settings.users', ':Crm:Users:');
		$this->addBreadcrumbLink('crm.user.changePassword');

		$user = $this->orm->users->getById($id);
		$this['passwordForm']->setDefaults([
			'id' => $user->id,
			'username' => $user->username
		]);
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
	 * Editace uzivatele
	 * @param Container $container
	 */
	public function userForm(Container $container)
	{
		$container->addText('username', 'crm.user.username')
			->setRequired();
		$container->addText('firstName', 'crm.user.firstName');
		$container->addText('surname', 'crm.user.surname');
		$container->addEmail('email', 'crm.user.email')
			->setRequired()
			->addRule(Form::EMAIL);
		$container->addMultiSelect('roles', 'crm.permissions.roles')
			->setTranslator()
			->setItems($this->orm->aclRoles->fetchPairs())
			->setRequired();
	}

	public function setUserForm(Container $container, User $user)
	{
		$container->setDefaults($user->toArray($user::TO_ARRAY_RELATIONSHIP_AS_ID));
	}

	/**
	 * Ulozi uzivatele
	 * @param int $id
	 * @param ArrayHash $values
	 */
	public function updateUser($id, $values)
	{
		if ($this->isAjax()) {
			$user = $this->orm->users->getById($id);
			try {
				$user->setUsername($values->username);
			} catch (UniqueConstraintViolationException $ex) {
				$this->flashNotifier->error('crm.user.dupliciteUsername');
				return;
			} catch (InvalidArgumentException $ex) {
				$this->flashNotifier->error('crm.user.invalidUsername');
				return;
			}
			try {
				$user->setEmail($values->email);
			} catch (UniqueConstraintViolationException $ex) {
				$this->flashNotifier->error('crm.user.dupliciteEmail');
				return;
			}
			$user->firstName = $values->firstName;
			$user->surname = $values->surname;

			$user->roles->set($values->roles);
			$this->orm->persistAndFlush($user);

			$this->flashNotifier->success('crm.user.dataSaved');
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
	protected function createComponentAddUserForm()
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

		$form->addMultiSelectUntranslated('roles', 'crm.permissions.roles', $this->orm->aclRoles->fetchPairs())
			->setRequired();

		if ($this->configurator->sendNewUserPassword) {
			$form->addCheckbox('generatePassword', 'crm.user.generatePassword');
		} else {
			$form->addHidden('generatePassword', false);
		}

		$form->addPassword('password', 'crm.user.newPassword')
			->addConditionOn($form['generatePassword'], Form::EQUAL, false)
			->setRequired()
			->addRule(Form::MIN_LENGTH, null, $this->minPasswordLength)
			->endCondition();
		$form->addPassword('passwordVerify', 'crm.user.passwordVerify')
			->addConditionOn($form['generatePassword'], Form::EQUAL, false)
			->setRequired()
			->addRule(Form::EQUAL, null, $form['password'])
			->endCondition();

		$form->addSubmit('save', 'form.save');
		$form->addLink('back', 'form.back', $this->getBacklink());

		$form->onSuccess[] = [$this, 'addUserFormSucceeded'];
		return $form;
	}

	/**
	 * Zpracovani noveho uzivatele
	 * @param Form $form
	 * @param ArrayHash $values
	 */
	public function addUserFormSucceeded(Form $form, $values)
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
			$form->addError('crm.user.invalideUsernameLetters');
			return;
		}
		try {
			$user->setEmail($values->email);
		} catch (UniqueConstraintViolationException $ex) {
			$form->addError('crm.user.dupliciteEmail');
			return;
		}
		$user->firstName = $values->firstName;
		$user->surname = $values->surname;
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
	 * Zmena hesla
	 * @return Form
	 */
	protected function createComponentPasswordForm()
	{
		$form = $this->formFactory->create();
		$form->addProtection();

		$form->addHidden('id', null);

		$form->addText('username', 'crm.user.username')
			->setDisabled();

		$form->addPassword('password', 'crm.user.newPassword')
			->setRequired()
			->addRule(Form::MIN_LENGTH, null, $this->minPasswordLength);
		$form->addPassword('passwordVerify', 'crm.user.passwordVerify')
			->setRequired()
			->addRule(Form::EQUAL, null, $form['password']);

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
		$user = $this->orm->users->getById($values->id);
		$user->setPassword($values->password);

		$this->orm->persistAndFlush($user);

		if ($this->configurator->sendChangePassword) {
			$this->mailer->sendNewPassword($user->email, $user->username, $values->password);
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
					$link->href = $this->link('Permissions:editRolePermission', ['id' => $role->id]);
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

		$edit = $grid->addInlineEdit();
		$edit->onControlAdd[] = [$this, 'userForm'];
		$edit->onSetDefaults[] = [$this, 'setUserForm'];
		$edit->onSubmit[] = [$this, 'updateUser'];

		if ($this['tryUser']->isAllowed()) {
			$grid->addAction('tryUser', null, 'tryUser!')
				->addAttributes(['target' => '_blank'])
				->setIcon('user')
				->setTitle('crm.user.tryUser');
		}

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
