<?php

declare(strict_types=1);

namespace NAttreid\Cms\Control;

use NAttreid\Cms\LocaleService;
use NAttreid\Cms\Mailing\Mailer;
use NAttreid\Form\Form;
use NAttreid\Security\Model\Orm;
use NAttreid\Security\Model\Users\User;
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
use Nette\Application\UI\InvalidLinkException;
use Nette\InvalidArgumentException;
use Nette\Security\AuthenticationException;
use Nette\Utils\ArrayHash;
use Nette\Utils\Html;
use Nette\Utils\Random;
use Nextras\Dbal\UniqueConstraintViolationException;
use Nextras\Orm\Model\Model;
use Ublaboo\DataGrid\Column\Action\Confirmation\CallbackConfirmation;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridColumnStatusException;
use Ublaboo\DataGrid\Exception\DataGridException;

/**
 * Uzivatele
 *
 * @author Attreid <attreid@gmail.com>
 */
class UsersPresenter extends CmsPresenter
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
	private $currentUser;

	public function __construct(string $passwordChars, int $minPasswordLength, Model $orm, Mailer $mailer, LocaleService $localeService)
	{
		parent::__construct();
		$this->passwordChars = $passwordChars;
		$this->minPasswordLength = $minPasswordLength;
		$this->orm = $orm;
		$this->mailer = $mailer;
		$this->localeService = $localeService;
	}

	/**
	 * @param string|null $backlink
	 * @throws AbortException
	 */
	public function handleBack(string $backlink = null): void
	{
		$this->redirect('default');
	}

	/**
	 * @param int $id
	 * @throws BadRequestException
	 */
	public function actionEdit(int $id): void
	{
		$this->currentUser = $this->orm->users->getById($id);
		if (!$this->currentUser) {
			$this->error();
		}
	}

	/**
	 * @param int $id
	 * @throws BadRequestException
	 */
	public function actionChangePassword(int $id): void
	{
		$this->actionEdit($id);
	}

	/**
	 * Zobrazeni seznamu
	 */
	public function renderDefault(): void
	{
		$this->addBreadcrumbLink('dockbar.settings.users');
	}

	/**
	 * Novy uzivatel
	 */
	public function renderAdd(): void
	{
		$this->addBreadcrumbLink('dockbar.settings.users', ':Cms:Users:');
		$this->addBreadcrumbLink('cms.user.add');
	}

	/**
	 * Editace uzivatele
	 */
	public function renderEdit(): void
	{
		$this->addBreadcrumbLink('dockbar.settings.users', ':Cms:Users:');
		$this->addBreadcrumbLink('cms.user.edit');
	}

	/**
	 * Zmena hesla
	 */
	public function renderChangePassword(): void
	{
		$this->addBreadcrumbLink('dockbar.settings.users', ':Cms:Users:');
		$this->addBreadcrumbLink('cms.user.changePassword');
	}

	/**
	 * Prepne na uzivatele pod id
	 * @param int $id
	 * @secured
	 * @throws AbortException
	 */
	public function handleTryUser(int $id): void
	{
		$this['tryUser']->set($id);
		$this->restoreBacklink();
	}

	/**
	 * Smaze uzivatele
	 * @param int $id
	 * @secured
	 * @throws AbortException
	 */
	public function handleDelete(int $id): void
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
	 * @param bool $value
	 * @throws AbortException
	 */
	public function setState(int $id, bool $value): void
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
	 * @throws AbortException
	 */
	public function deleteUsers(array $ids): void
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
	 * @throws AbortException
	 */
	public function activateUsers(array $ids): void
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
	 * @throws AbortException
	 */
	public function deactivateUsers(array $ids): void
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
	protected function createComponentAddForm(): Form
	{
		$form = $this->formFactory->create();
		$form->addProtection();

		$form->addText('username', 'cms.user.username')
			->setRequired();
		$form->addText('firstName', 'cms.user.firstName');
		$form->addText('surname', 'cms.user.surname');
		$form->addText('email', 'cms.user.email')
			->setRequired()
			->addRule(Form::EMAIL);
		$form->addPhone('phone', 'cms.user.phone');
		$form->addSelectUntranslated('language', 'cms.user.language', $this->localeService->allowed, 'form.none');

		$form->addMultiSelectUntranslated('roles', 'cms.permissions.roles', $this->orm->aclRoles->fetchPairs($this->user->isAllowed('dockbar.settings.permissions.superadmin', 'view')))
			->setRequired();

		if ($this->configurator->sendNewUserPassword) {
			$form->addCheckbox('generatePassword', 'cms.user.generatePassword')
				->addCondition($form::EQUAL, false)
				->toggle('password')
				->toggle('passwordVerify');
		} else {
			$form->addHidden('generatePassword', false);
		}

		$form->addPassword('password', 'cms.user.newPassword')
			->setOption('id', 'password')
			->addConditionOn($form['generatePassword'], Form::EQUAL, false)
			->setRequired()
			->addRule(Form::MIN_LENGTH, null, $this->minPasswordLength)
			->endCondition();
		$form->addPassword('passwordVerify', 'cms.user.passwordVerify')
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
	 * @throws AuthenticationException
	 * @throws InvalidLinkException
	 */
	public function addFormSucceeded(Form $form, ArrayHash $values): void
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
			$form->addError('cms.user.duplicityUsername');
			return;
		} catch (InvalidArgumentException $ex) {
			$form->addError('cms.user.invalidUsername');
			return;
		}

		try {
			$user->setEmail($values->email);
		} catch (UniqueConstraintViolationException $ex) {
			$form->addError('cms.user.duplicityEmail');
			return;
		} catch (InvalidArgumentException $ex) {
			$form->addError('cms.user.invalidUsername');
			return;
		}

		try {
			$user->setPhone($values->phone ?: null);
		} catch (InvalidArgumentException $ex) {
			$form->addError('cms.user.invalidPhone');
			return;
		}

		$user->firstName = $values->firstName;
		$user->surname = $values->surname;

		$language = $this->localeService->getById($values->language);
		$user->language = $language === null ? null : $language->name;

		$user->roles->set($values->roles);
		$user->setPassword($password);

		$this->orm->persistAndFlush($user);

		if ($this->configurator->sendNewUserPassword) {
			$this->mailer->sendNewUser($user->email, $user->username, $password);
		}

		$this->flashNotifier->success('cms.user.dataSaved');
		$this->restoreBacklink();
	}

	/**
	 * Formular editace uzivatele
	 * @return Form
	 */
	protected function createComponentEditForm(): Form
	{
		$form = $this->formFactory->create();
		$form->addProtection();

		$form->addText('username', 'cms.user.username')
			->setDefaultValue($this->currentUser->username)
			->setRequired();
		$form->addText('firstName', 'cms.user.firstName')
			->setDefaultValue($this->currentUser->firstName);
		$form->addText('surname', 'cms.user.surname')
			->setDefaultValue($this->currentUser->surname);
		$form->addText('email', 'cms.user.email')
			->setDefaultValue($this->currentUser->email)
			->setRequired()
			->addRule(Form::EMAIL);
		$form->addPhone('phone', 'cms.user.phone')
			->setDefaultValue($this->currentUser->phone);

		$language = $form->addSelectUntranslated('language', 'cms.user.language', $this->localeService->allowed, 'form.none');
		if (!empty($this->currentUser->language)) {
			$locale = $this->localeService->get($this->currentUser->language);
			if ($locale) {
				$language->setDefaultValue($locale->id);
			}
		}

		$roles = $form->addMultiSelectUntranslated('roles', 'cms.permissions.roles', $this->orm->aclRoles->fetchPairs($this->user->isAllowed('dockbar.settings.permissions.superadmin', 'view')))
			->setRequired();
		try {
			$roles->setDefaultValue($this->currentUser->roles->getRawValue());
		} catch (InvalidArgumentException $ex) {

		}

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
	public function editFormSucceeded(Form $form, ArrayHash $values): void
	{
		try {
			$this->currentUser->setUsername($values->username);
		} catch (UniqueConstraintViolationException $ex) {
			$form->addError('cms.user.duplicityUsername');
			return;
		} catch (InvalidArgumentException $ex) {
			$form->addError('cms.user.invalidUsername');
			return;
		}

		try {
			$this->currentUser->setEmail($values->email);
		} catch (UniqueConstraintViolationException $ex) {
			$form->addError('cms.user.duplicityEmail');
			return;
		} catch (InvalidArgumentException $ex) {
			$form->addError('cms.user.invalidUsername');
			return;
		}

		try {
			$this->currentUser->setPhone($values->phone ?: null);
		} catch (InvalidArgumentException $ex) {
			$form->addError('cms.user.invalidPhone');
			return;
		}

		$this->currentUser->firstName = $values->firstName;
		$this->currentUser->surname = $values->surname;
		$this->currentUser->roles->set($values->roles);

		$language = $this->localeService->getById($values->language);
		$this->currentUser->language = $language === null ? null : $language->name;

		$this->orm->persistAndFlush($this->currentUser);

		$this->flashNotifier->success('cms.user.dataSaved');
		$this->restoreBacklink();
	}

	/**
	 * Zmena hesla
	 * @return Form
	 */
	protected function createComponentPasswordForm(): Form
	{
		$form = $this->formFactory->create();
		$form->addProtection();

		$form->addText('username', 'cms.user.username')
			->setDisabled()
			->setDefaultValue($this->currentUser->username);

		if ($this->configurator->sendNewUserPassword) {
			$form->addCheckbox('generatePassword', 'cms.user.generatePassword')
				->addCondition($form::EQUAL, false)
				->toggle('password')
				->toggle('passwordVerify');
		} else {
			$form->addHidden('generatePassword', false);
		}

		$form->addPassword('password', 'cms.user.newPassword')
			->setOption('id', 'password')
			->addConditionOn($form['generatePassword'], Form::EQUAL, false)
			->setRequired()
			->addRule(Form::MIN_LENGTH, null, $this->minPasswordLength)
			->endCondition();
		$form->addPassword('passwordVerify', 'cms.user.passwordVerify')
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
	 * @throws AuthenticationException
	 * @throws InvalidLinkException
	 */
	public function passwordFormSucceeded(Form $form, ArrayHash $values): void
	{
		if ($values->generatePassword) {
			$password = Random::generate($this->minPasswordLength, $this->passwordChars);
		} else {
			$password = $values->password;
		}

		$this->currentUser->setPassword($password);

		$this->orm->persistAndFlush($this->currentUser);

		if ($this->configurator->sendChangePassword) {
			$this->mailer->sendNewPassword($this->currentUser->email, $this->currentUser->username, $password);
		}

		$this->flashNotifier->success('cms.user.passwordChanged');
		$this->restoreBacklink();
	}

	/**
	 * Seznam uzivatelu
	 * @return DataGrid
	 * @throws DataGridException
	 * @throws DataGridColumnStatusException
	 */
	protected function createComponentUserList(): DataGrid
	{
		$grid = $this->dataGridFactory->create();

		$grid->setDataSource($this->orm->users->findAll());

		$grid->addToolbarButton('add', 'cms.user.add')
			->setIcon('plus');

		$grid->addColumnText('username', 'cms.user.username')
			->setSortable()
			->setFilterText();

		$grid->addColumnText('firstName', 'cms.user.firstName')
			->setSortable()
			->setFilterText();

		$grid->addColumnText('surname', 'cms.user.surname')
			->setSortable()
			->setFilterText();

		$grid->addColumnText('email', 'cms.user.email')
			->setSortable()
			->setFilterText();

		$grid->addColumnText('roles', 'cms.permissions.roles', 'roles.id')
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
			->setFilterSelect(['' => $this->translate('form.none')] + $this->orm->aclRoles->fetchPairs($this->user->isAllowed('dockbar.settings.permissions.superadmin', 'view')));

		$active = [
			'' => 'form.none',
			1 => 'default.active',
			0 => 'default.deactive'
		];
		$state = $grid->addColumnStatus('active', 'cms.user.state');
		$state->setFilterSelect($active)
			->setTranslateOptions();
		$state->addOption(1, 'default.active')
			->setClass('btn-success');
		$state->addOption(0, 'default.deactive')
			->setClass('btn-danger');
		$state->onChange[] = [$this, 'setState'];

		if ($this['tryUser']->isAllowed()) {
			$grid->addAction('tryUser', '', 'tryUser!')
				->addAttributes(['target' => '_blank'])
				->setIcon('user')
				->setTitle('cms.user.tryUser');
		}

		$grid->addAction('edit', '')
			->setIcon('pencil')
			->setTitle('cms.user.edit');

		$grid->addAction('changePassword', '')
			->setIcon('wrench')
			->setTitle('cms.user.changePassword');

		$grid->addAction('delete', '', 'delete!')
			->setIcon('trash')
			->setTitle('default.delete')
			->setClass('btn btn-xs btn-danger ajax')
			->setConfirmation(
				new CallbackConfirmation(
					function (User $user) {
						return $this->translate('default.confirmDelete', null, ['name' => $user->fullName]);
					}
				));

		$grid->addGroupAction('default.activate')->onSelect[] = [$this, 'activateUsers'];
		$grid->addGroupAction('default.deactivate')->onSelect[] = [$this, 'deactivateUsers'];
		$grid->addGroupAction('default.delete')->onSelect[] = [$this, 'deleteUsers'];

		return $grid;
	}

}
