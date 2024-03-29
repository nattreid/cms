<?php

declare(strict_types=1);

namespace NAttreid\Cms\Control;

use NAttreid\Cms\Mailing\Mailer;
use NAttreid\Form\Form;
use NAttreid\Security\Model\AclRoles\AclRolesMapper;
use NAttreid\Security\Model\Orm;
use NAttreid\Security\Model\Users\User;
use NAttreid\Utils\Hasher;
use Nette\Application\AbortException;
use Nette\Security\AuthenticationException;
use Nette\Security\IAuthenticator;
use Nette\Security\UserStorage;
use Nette\Utils\ArrayHash;
use Nette\Utils\Random;
use Nextras\Orm\Model\Model;

/**
 * Prihlaseni do CMS
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
	private $loginExpiration;

	/** @var string */
	private $sessionExpiration;

	/** @var int */
	private $minPasswordLength;

	/** @var Orm */
	private $orm;

	/** @var Mailer */
	private $mailer;

	/** @var Hasher */
	private $hasher;

	public function __construct(string $loginExpiration, string $sessionExpiration, int $minPasswordLength, Model $orm, Mailer $mailer, Hasher $hasher)
	{
		parent::__construct();
		$this->loginExpiration = $loginExpiration;
		$this->sessionExpiration = $sessionExpiration;
		$this->minPasswordLength = $minPasswordLength;
		$this->orm = $orm;
		$this->mailer = $mailer;
		$this->hasher = $hasher;
	}

	/**
	 * @throws AbortException
	 */
	protected function startup(): void
	{
		parent::startup();
		if ($this->user->loggedIn) {
			$this->redirect(":{$this->module}:Homepage:");
		}
		$this->template->layout = __DIR__ . '/templates/Sign/@layout.latte';
	}

	/**
	 * Prihlaseni
	 * @throws AbortException
	 */
	public function actionIn(): void
	{
		if ($this->orm->users->isEmpty()) {
			$this->redirect('registerAdministrator');
		}
	}

	/**
	 * Prvni prihlaseni administratora
	 * @throws AbortException
	 */
	public function actionRegisterAdministrator(): void
	{
		if (!$this->orm->users->isEmpty()) {
			$this->terminate();
		}
	}

	/**
	 * Obnoveni hesla je mozno jen trikrat do hodiny
	 * @throws AbortException
	 */
	public function actionForgottenPassword(): void
	{
		$session = $this->getSession('cms/forgottenPassword');
		if ($session->count >= self::MAX_TRY) {
			$this->flashNotifier->warning('cms.user.restorePasswordDisabledForHour');
			$this->redirect(":{$this->module}:Sign:in");
		}
	}

	/**
	 * Obnoveni hesla
	 * @param string $hash
	 * @throws AbortException
	 */
	public function renderRestorePassword(string $hash): void
	{
		$session = $this->getSession('cms/restorePassword');
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
	protected function createComponentSignInForm(): Form
	{
		$form = $this->formFactory->create();
		$form->addProtection();

		$form->addText('username', 'cms.user.username')
			->setAttribute('autofocus', true)
			->setRequired();

		$form->addPassword('password', 'cms.user.password')
			->setRequired();

		$form->addCheckbox('remember', 'cms.user.staySignedIn');

		$form->addSubmit('send', 'cms.user.signIn');

		$form->onSuccess[] = [$this, 'signInFormSucceeded'];

		return $form;
	}

	/**
	 * Zpracovani prihlasovaciho formulare
	 * @param Form $form
	 * @param ArrayHash $values
	 * @throws AbortException
	 */
	public function signInFormSucceeded(Form $form, ArrayHash $values)
	{
		try {
			$this->user->login($values->username, $values->password);
			if ($values->remember) {
				$this->user->setExpiration('+ ' . $this->sessionExpiration);
			} else {
				$this->user->setExpiration('+ ' . $this->loginExpiration, UserStorage::LOGOUT_MANUAL);
			}
			$this->restoreRequest($this->backlink);
			$this->redirect(":{$this->module}:Homepage:");
		} catch (AuthenticationException $e) {
			if ($e->getCode() == IAuthenticator::NOT_APPROVED) {
				$form->addError('cms.user.accountDeactivated');
			} else {
				$form->addError('cms.user.incorrectUsernameOrPassword');
			}
		}
	}

	/**
	 * Formular pro zapomenute heslo
	 * @return Form
	 * @throws \Nette\Application\UI\InvalidLinkException
	 */
	protected function createComponentForgottenPasswordForm(): Form
	{
		$form = $this->formFactory->create();
		$form->addProtection();

		$form->addText('usernameOrEmail', 'cms.user.usernameOrEmail')
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
	 * @throws AbortException
	 * @throws \Nette\Application\UI\InvalidLinkException
	 */
	public function forgottenPasswordFormSucceeded(Form $form, ArrayHash $values): void
	{
		$value = $values->usernameOrEmail;
		$user = $this->orm->users->getByUsername($value);
		if (!$user) {
			$user = $this->orm->users->getByEmail($value);
			if (!$user) {
				$form->addError('cms.user.incorrectUsernameOrEmail');
				$session = $this->getSession('cms/forgottenPassword');
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

		$session = $this->getSession('cms/restorePassword');
		$session->setExpiration('1 hours');
		$session->$hash = $user->email;

		$this->mailer->sendRestorePassword($user->email, $hash);
		$this->flashNotifier->info('cms.user.mailToRestorePasswordSent');
		$this->redirect(":{$this->module}:Sign:in");
	}

	/**
	 * Formular pro obnoveni hesla
	 * @return Form
	 */
	protected function createComponentRestorePasswordForm(): Form
	{
		$form = $this->formFactory->create();
		$form->addProtection();

		$form->addHidden('hash');

		$form->addPassword('password', 'cms.user.newPassword')
			->setRequired()
			->addRule(Form::MIN_LENGTH, null, $this->minPasswordLength);
		$form->addPassword('passwordVerify', 'cms.user.passwordVerify')
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
	 * @throws AuthenticationException
	 * @throws AbortException
	 */
	public function restorePasswordFormSucceeded(Form $form, ArrayHash $values): void
	{
		$session = $this->getSession('cms/restorePassword');
		$email = $session->{$values->hash};
		$session->remove();

		$user = $this->orm->users->getByEmail($email);
		if ($user) {
			$user->setPassword($values->password);
			$this->orm->persistAndFlush($user);
			$this->flashNotifier->success('cms.user.passwordChanged');
		} else {
			$this->flashNotifier->error('cms.permissions.accessDenied');
		}
		$this->redirect(":{$this->module}:Sign:in");
	}

	/**
	 * Formular pro prvniho uzivatele
	 * @return Form
	 */
	protected function createComponentRegisterAdministratorForm(): Form
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

		$form->addPassword('password', 'cms.user.newPassword')
			->setRequired()
			->addRule(Form::MIN_LENGTH, null, $this->minPasswordLength);
		$form->addPassword('passwordVerify', 'cms.user.passwordVerify')
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
	 * @throws AuthenticationException
	 * @throws AbortException
	 */
	public function registerAdministratorFormSucceeded(Form $form, ArrayHash $values): void
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

		$this->user->setExpiration('+ ' . $this->loginExpiration);
		$this->user->login($values->username, $password);

		$this->flashNotifier->success('cms.user.dataSaved');

		$this->redirect(":{$this->module}:Homepage:");
	}

}
