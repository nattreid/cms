<?php

namespace NAttreid\Crm\Control;

use NAttreid\Crm\Mailing\Mailer;
use NAttreid\Form\Form;
use NAttreid\Security\Model\AclRolesMapper;
use NAttreid\Security\Model\Orm;
use NAttreid\Security\Model\User;
use NAttreid\Utils\Hasher;
use Nette\Security\AuthenticationException;
use Nette\Security\IAuthenticator;
use Nette\Utils\ArrayHash;
use Nette\Utils\Random;
use Nextras\Orm\Model\Model;

/**
 * Prihlaseni do crm
 *
 * @author Attreid <attreid@gmail.com>
 */
class SignPresenter extends BasePresenter
{

	const
		MAX_TRY = 3;

	/** @persistent */
	public $backlink;

	/** @var string */
	private $loginExpiracy;

	/** @var string */
	private $sessionExpiracy;

	/** @var int */
	private $minPasswordLength;

	/** @var Orm */
	private $orm;

	/** @var Mailer */
	private $mailer;

	/** @var Hasher */
	private $hasher;

	public function __construct($loginExpiracy, $sessionExpiracy, $minPasswordLength, Model $orm, Mailer $mailer, Hasher $hasher)
	{
		parent::__construct();
		$this->loginExpiracy = $loginExpiracy;
		$this->sessionExpiracy = $sessionExpiracy;
		$this->minPasswordLength = $minPasswordLength;
		$this->orm = $orm;
		$this->mailer = $mailer;
		$this->hasher = $hasher;
	}

	protected function startup()
	{
		parent::startup();
		if ($this->user->isLoggedIn()) {
			$this->redirect(":{$this->module}:Homepage:");
		}
		$this->template->layout = __DIR__ . '/templates/Sign/@layout.latte';
	}

	/**
	 * Prihlaseni
	 */
	public function actionIn()
	{
		if ($this->orm->users->isEmpty()) {
			$this->redirect('registerAdministrator');
		}
	}

	/**
	 * Prvni prihlaseni administratora
	 */
	public function actionRegisterAdministrator()
	{
		if (!$this->orm->users->isEmpty()) {
			$this->terminate();
		}
	}

	/**
	 * Obnoveni hesla je mozno jen trikrat do hodiny
	 */
	public function actionForgottenPassword()
	{
		$session = $this->getSession('crm/forgottenPassword');
		if ($session->count >= self::MAX_TRY) {
			$this->flashNotifier->warning('main.user.restorePasswordDisabledForHour');
			$this->redirect(":{$this->module}:Sign:in");
		}
	}

	/**
	 * Obnoveni hesla
	 * @param string $hash
	 */
	public function renderRestorePassword($hash)
	{
		$session = $this->getSession('crm/restorePassword');
		if (!isset($session->$hash)) {
			$this->redirect(":{$this->module}:Sign:in");
		} else {
			$this['restorePasswordForm']->setDefaults([
				'hash' => $hash
			]);
		}
	}

	/**
	 * Prihlasovaci formular
	 * @return Form
	 */
	protected function createComponentSignInForm()
	{
		$form = $this->formFactory->create();
		$form->addProtection();

		$form->addText('username', 'main.user.username')
			->setAttribute('autofocus', true)
			->setRequired();

		$form->addPassword('password', 'main.user.password')
			->setRequired();

		$form->addCheckbox('remember', 'main.user.stayLogin');

		$form->addSubmit('send', 'main.user.login');

		$form->onSuccess[] = [$this, 'signInFormSucceeded'];

		return $form;
	}

	/**
	 * Zpracovani prihlasovaciho formulare
	 * @param Form $form
	 * @param ArrayHash $values
	 */
	public function signInFormSucceeded(Form $form, $values)
	{
		try {
			$this->user->login($values->username, $values->password);
			if ($values->remember) {
				$this->user->setExpiration('+ ' . $this->sessionExpiracy, false);
			} else {
				$this->user->setExpiration('+ ' . $this->loginExpiracy, true);
			}
			$this->restoreRequest($this->backlink);
			$this->redirect(":{$this->module}:Homepage:");
		} catch (AuthenticationException $e) {
			if ($e->getCode() == IAuthenticator::NOT_APPROVED) {
				$form->addError('main.user.accountDeactived');
			} else {
				$form->addError('main.user.incorrectUsernameOrPassword');
			}
		}
	}

	/**
	 * Formular pro zapomenute heslo
	 * @return Form
	 */
	protected function createComponentForgottenPasswordForm()
	{
		$form = $this->formFactory->create();
		$form->addProtection();

		$form->addText('usernameOrEmail', 'main.user.usernameOrEmail')
			->setRequired();

		$form->addSubmit('send', 'form.send');

		$form->addLink('back', 'form.back', $this->link('in'));

		$form->onSuccess[] = [$this, 'forgottenPasswordFormSucceeded'];

		return $form;
	}

	/**
	 * Zpracovani formulare pro zapomenute heslo
	 * @param Form $form
	 * @param ArrayHash $values
	 */
	public function forgottenPasswordFormSucceeded(Form $form, $values)
	{
		$value = $values->usernameOrEmail;
		$user = $this->orm->users->getByUsername($value);
		if (!$user) {
			$user = $this->orm->users->getByEmail($value);
			if (!$user) {
				$form->addError('main.user.incorrectUsernameOrEmail');
				$session = $this->getSession('crm/forgottenPassword');
				if (isset($session->count)) {
					$session->count++;
				} else {
					$session->setExpiration('1 hours');
					$session->count = 1;
				}
				$this->actionForgottenPassword();
				return;
			}
		}

		$hash = $this->hasher->hash(Random::generate());

		$session = $this->getSession('crm/restorePassword');
		$session->setExpiration('1 hours');
		$session->$hash = $user->email;

		$this->mailer->sendRestorePassword($user->email, $hash);
		$this->flashNotifier->info('main.user.mailToRestorePasswordSent');
		$this->redirect(":{$this->module}:Sign:in");
	}

	/**
	 * Formular pro obnoveni hesla
	 * @return Form
	 */
	protected function createComponentRestorePasswordForm()
	{
		$form = $this->formFactory->create();
		$form->addProtection();

		$form->addHidden('hash');

		$form->addPassword('password', 'main.user.newPassword')
			->setRequired()
			->addRule(Form::MIN_LENGTH, null, $this->minPasswordLength);
		$form->addPassword('passwordVerify', 'main.user.passwordVerify')
			->setRequired()
			->addRule(Form::EQUAL, null, $form['password']);

		$form->addSubmit('restore', 'form.save');

		$form->onSuccess[] = [$this, 'restorePasswordFormSucceeded'];

		return $form;
	}

	/**
	 * Zpracovani formulare pro obnoveni hesla
	 * @param Form $form
	 * @param ArrayHash $values
	 */
	public function restorePasswordFormSucceeded(Form $form, $values)
	{
		$session = $this->getSession('crm/restorePassword');
		$email = $session->{$values->hash};
		$session->remove();

		$user = $this->orm->users->getByEmail($email);
		if ($user) {
			$user->setPassword($values->password);
			$this->orm->persistAndFlush($user);
			$this->flashNotifier->success('main.user.passwordChanged');
		} else {
			$this->flashNotifier->error('main.permissions.accessDenied');
		}
		$this->redirect(":{$this->module}:Sign:in");
	}

	/**
	 * Formular pro prvniho uzivatele
	 * @return Form
	 */
	protected function createComponentRegisterAdministratorForm()
	{
		$form = $this->formFactory->create();
		$form->addProtection();

		$form->addText('username', 'main.user.username')
			->setRequired();
		$form->addText('firstName', 'main.user.firstName');
		$form->addText('surname', 'main.user.surname');
		$form->addText('email', 'main.user.email')
			->setRequired()
			->addRule(Form::EMAIL);

		$form->addPassword('password', 'main.user.newPassword')
			->setRequired()
			->addRule(Form::MIN_LENGTH, null, $this->minPasswordLength);
		$form->addPassword('passwordVerify', 'main.user.passwordVerify')
			->setRequired()
			->addRule(Form::EQUAL, null, $form['password']);

		$form->addSubmit('create', 'form.save');

		$form->onSuccess[] = [$this, 'registerAdministratorFormSucceeded'];

		return $form;
	}

	/**
	 * Zpracovani formulare pro prvniho uzivatele
	 * @param Form $form
	 * @param ArrayHash $values
	 */
	public function registerAdministratorFormSucceeded(Form $form, $values)
	{
		$password = $values->password;

		$role = $this->orm->aclRoles->getByName(AclRolesMapper::SUPERADMIN);

		$user = new User;
		$user->firstName = $values->firstName;
		$user->surname = $values->surname;
		$user->email = $values->email;
		$user->username = $values->username;
		$user->setPassword($password);
		$user->roles->add($role);

		$this->orm->persistAndFlush($user);

		$this->user->setExpiration('+ ' . $this->loginExpiracy, true);
		$this->user->login($values->username, $password);

		$this->flashNotifier->success('main.user.dataSaved');

		$this->redirect(":{$this->module}:Homepage:");
	}

}
