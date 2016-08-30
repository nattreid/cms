<?php

namespace NAttreid\Crm\Control;

use NAttreid\Form\Form,
    NAttreid\Crm\Mailing\Mailer,
    NAttreid\Security\Model\User,
    Nette\Forms\Container,
    Nextras\Dbal\UniqueConstraintViolationException,
    Nette\InvalidArgumentException,
    Ublaboo\DataGrid\DataGrid,
    Nette\Utils\ArrayHash,
    Nextras\Orm\Model\Model,
    NAttreid\Security\Model\Orm;

/**
 * Uzivatele
 * 
 * @author Attreid <attreid@gmail.com>
 */
class UsersPresenter extends CrmPresenter {

    /** @var string */
    private $passwordChars;

    /** @var int */
    private $minPasswordLength;

    /** @var Orm */
    private $orm;

    /** @var Mailer */
    private $mailer;

    public function __construct($passwordChars, $minPasswordLength, Model $orm, Mailer $mailer) {
        $this->passwordChars = $passwordChars;
        $this->minPasswordLength = $minPasswordLength;
        $this->orm = $orm;
        $this->mailer = $mailer;
    }

    /**
     * {@inheritdoc }
     */
    public function restoreBacklink() {
        parent::restoreBacklink();
        $this->redirect('default');
    }

    /**
     * Zobrazeni seznamu
     */
    public function renderDefault() {
        $this->addBreadcrumbLink('main.dockbar.settings.users');
        $this->storeBacklink();
    }

    /**
     * Zmena hesla
     * @param int $id
     */
    public function renderChangePassword($id) {
        $this->addBreadcrumbLink('main.dockbar.settings.users', ':Crm:Users:');
        $this->addBreadcrumbLink('main.user.profile');

        $this['passwordForm']->setDefaults(['id' => $id]);
    }

    /**
     * Prepne na uzivatele pod id
     * @param int $id
     * @secured
     */
    public function handleTryUser($id) {
        $this->getTryUser()->set($id);
        $this->restoreBacklink();
    }

    /**
     * Smaze uzivatele
     * @param int $id
     * @secured
     */
    public function handleDelete($id) {
        $grid = $this['userList']; /* @var $grid DataGrid */
        if ($this->isAjax()) {
            $user = $this->orm->users->getById($id);
            $this->orm->users->removeAndFlush($user);
            $grid->reload();
        } else {
            $this->terminate();
        }
    }

    /**
     * Ulozi stav uzivatele
     * @param int $id
     * @param boolean $value
     */
    public function setState($id, $value) {
        $grid = $this['userList']; /* @var $grid DataGrid */
        if ($this->isAjax()) {
            $user = $this->orm->users->getById($id); /* @var $user User */
            $user->active = $value;
            $this->orm->persistAndFlush($user);
            $grid->redrawItem($id);
        } else {
            $this->terminate();
        }
    }

    /**
     * Editace uzivatele
     * @param Container $container
     */
    public function userForm(Container $container) {
        $container->addText('username', 'main.user.username')
                ->setRequired();
        $container->addText('firstName', 'main.user.firstName');
        $container->addText('surname', 'main.user.surname');
        $container->addEmail('email', 'main.user.email')
                ->setRequired()
                ->addRule(Form::EMAIL);
        $container->addMultiSelect('roles', 'main.permissions.roles')
                ->setTranslator()
                ->setItems($this->orm->aclRoles->fetchPairs())
                ->setRequired();
    }

    public function setUserForm(Container $container, User $user) {
        $container->setDefaults([
            'username' => $user->username,
            'firstName' => $user->firstName,
            'surname' => $user->surname,
            'email' => $user->email,
            'roles' => $user->roles->getRawValue(),
        ]);
    }

    /**
     * Ulozi uzivatele
     * @param int $id
     * @param ArrayHash $values
     */
    public function updateUser($id, $values) {
        if ($this->isAjax()) {
            $user = $this->orm->users->getById($id); /* @var $user User */
            try {
                $user->setUsername($values->username);
            } catch (UniqueConstraintViolationException $ex) {
                $this->flashNotifier->error('main.user.dupliciteUsername');
                return;
            } catch (InvalidArgumentException $ex) {
                $this->flashNotifier->error('main.user.invalidUsername');
                return;
            }
            try {
                $user->setEmail($values->email);
            } catch (UniqueConstraintViolationException $ex) {
                $this->flashNotifier->error('main.user.dupliciteEmail');
                return;
            }
            $user->firstName = $values->firstName;
            $user->surname = $values->surname;

            $user->roles->set($values->roles);
            $this->orm->persistAndFlush($user);

            $this->flashNotifier->success('main.user.dataSaved');
        } else {
            $this->terminate();
        }
    }

    /**
     * Smaze uzivatele
     * @param array $ids
     */
    public function deleteUsers(array $ids) {
        $grid = $this['userList']; /* @var $grid DataGrid */
        if ($this->isAjax()) {
            $users = $this->orm->users->findById($ids);
            foreach ($users as $user) {
                $this->orm->users->remove($user);
            }
            $this->orm->flush();
            $grid->reload();
        } else {
            $this->terminate();
        }
    }

    /**
     * Aktivuje uzivatele
     * @param array $ids
     */
    public function activateUsers(array $ids) {
        $grid = $this['userList']; /* @var $grid DataGrid */
        if ($this->isAjax()) {
            $users = $this->orm->users->findById($ids); /* @var $user User */
            foreach ($users as $user) {
                $user->active = TRUE;
                $this->orm->users->persist($user);
            }
            $this->orm->flush();
            $grid->reload();
        } else {
            $this->terminate();
        }
    }

    /**
     * Deaktivuje uzivatele
     * @param array $ids
     */
    public function deactivateUsers(array $ids) {
        $grid = $this['userList']; /* @var $grid DataGrid */
        if ($this->isAjax()) {
            $users = $this->orm->users->findById($ids); /* @var $user User */
            foreach ($users as $user) {
                $user->active = FALSE;
                $this->orm->users->persist($user);
            }
            $this->orm->flush();
            $grid->reload();
        } else {
            $this->terminate();
        }
    }

    /**
     * Formular pridani uzivatele
     * @return Form
     */
    protected function createComponentAddUserForm() {
        $form = $this->formFactory->create();
        $form->addProtection();

        $form->addText('username', 'main.user.username')
                ->setRequired();
        $form->addText('firstName', 'main.user.firstName');
        $form->addText('surname', 'main.user.surname');
        $form->addText('email', 'main.user.email')
                ->setRequired()
                ->addRule(Form::EMAIL);

        $form->addMultiSelectUntranslated('roles', 'main.permissions.roles', $this->orm->aclRoles->fetchPairs())
                ->setRequired();

        if ($this->configurator->sendNewUserPassword) {
            $form->addCheckbox('generatePassword', 'main.user.generatePassword');
        } else {
            $form->addHidden('generatePassword', FALSE);
        }

        $form->addPassword('password', 'main.user.newPassword')
                ->addConditionOn($form['generatePassword'], Form::EQUAL, FALSE)
                ->setRequired()
                ->addRule(Form::MIN_LENGTH, NULL, $this->minPasswordLength)
                ->endCondition();
        $form->addPassword('passwordVerify', 'main.user.passwordVerify')
                ->addConditionOn($form['generatePassword'], Form::EQUAL, FALSE)
                ->setRequired()
                ->addRule(Form::EQUAL, NULL, $form['password'])
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
    public function addUserFormSucceeded(Form $form, $values) {
        if ($values->generatePassword) {
            $password = \Nette\Utils\Random::generate($this->minPasswordLength, $this->passwordChars);
        } else {
            $password = $values->password;
        }

        $user = new User;
        $this->orm->users->attach($user);
        try {
            $user->setUsername($values->username);
        } catch (UniqueConstraintViolationException $ex) {
            $form->addError('main.user.dupliciteUsername');
            return;
        } catch (InvalidArgumentException $ex) {
            $form->addError('main.user.invalideUsernameLetters');
            return;
        }
        try {
            $user->setEmail($values->email);
        } catch (UniqueConstraintViolationException $ex) {
            $form->addError('main.user.dupliciteEmail');
            return;
        }
        $user->firstName = $values->firstName;
        $user->roles->set($values->roles);
        $user->setPassword($password);

        $this->orm->persistAndFlush($user);

        if ($this->configurator->sendNewUserPassword) {
            $this->mailer->sendNewUser($user->email, $user->username, $password);
        }

        $this->flashNotifier->success('main.user.dataSaved');
        $this->restoreBacklink();
    }

    /**
     * Zmena hesla
     * @return Form
     */
    protected function createComponentPasswordForm() {
        $form = $this->formFactory->create();
        $form->addProtection();

        $form->addHidden('id', NULL);

        $form->addPassword('password', 'main.user.newPassword')
                ->setRequired()
                ->addRule(Form::MIN_LENGTH, NULL, $this->minPasswordLength);
        $form->addPassword('passwordVerify', 'main.user.passwordVerify')
                ->setRequired()
                ->addRule(Form::EQUAL, NULL, $form['password']);

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
    public function passwordFormSucceeded(Form $form, $values) {
        $user = $this->orm->users->getById($values->id); /* @var $user User */
        $user->setPassword($values->password);

        $this->orm->persistAndFlush($user);

        if ($this->configurator->sendChangePassword) {
            $this->mailer->sendNewPassword($user->email, $user->username, $values->password);
        }

        $this->flashNotifier->success('main.user.passwordChanged');
        $this->restoreBacklink();
    }

    /**
     * Seznam uzivatelu
     * @return DataGrid
     */
    protected function createComponentUserList($name) {
        $grid = $this->dataGridFactory->create($this, $name);

        $grid->setDataSource($this->orm->users->findAll());

        $grid->addToolbarButton('add', 'main.user.add');

        $grid->addColumnText('username', 'main.user.username')
                ->setSortable()
                ->setFilterText();

        $grid->addColumnText('firstName', 'main.user.firstName')
                ->setSortable()
                ->setFilterText();

        $grid->addColumnText('surname', 'main.user.surname')
                ->setSortable()
                ->setFilterText();

        $grid->addColumnText('email', 'main.user.email')
                ->setSortable()
                ->setFilterText();

        $grid->addColumnText('roles', 'main.permissions.roles', 'roles.id')
                ->setRenderer(function(User $user) {
                    return implode(', ', $user->getRoleTitles());
                })
                ->setFilterSelect(['' => $this->translate('form.none')] + $this->orm->aclRoles->fetchPairs());

        $active = [
            '' => 'form.none',
            1 => 'main.user.active',
            0 => 'main.user.deactive'
        ];
        $state = $grid->addColumnStatus('active', 'main.user.state');
        $state->setFilterSelect($active)
                ->setTranslateOptions();
        $state->addOption(1, 'main.user.active')
                ->setClass('btn-success');
        $state->addOption(0, 'main.user.deactive')
                ->setClass('btn-danger');
        $state->onChange[] = [$this, 'setState'];

        $edit = $grid->addInlineEdit();
        $edit->onControlAdd[] = [$this, 'userForm'];
        $edit->onSetDefaults[] = [$this, 'setUserForm'];
        $edit->onSubmit[] = [$this, 'updateUser'];

        if ($this->getTryUser()->isAllowed()) {
            $grid->addAction('tryUser', NULL, 'tryUser!')
                    ->addAttributes(['target' => '_blank'])
                    ->setIcon('user')
                    ->setTitle('main.user.tryUser');
        }

        $grid->addAction('changePassword', NULL)
                ->setIcon('wrench')
                ->setTitle('main.user.changePassword');

        $grid->addAction('delete', NULL, 'delete!')
                ->setIcon('trash')
                ->setTitle('main.user.delete')
                ->setClass('btn btn-xs btn-danger ajax')
                ->setConfirm(function(User $user) {
                    return $this->translate('main.user.confirmDelete', NULL, ['name' => $user->fullName]);
                });

        $grid->addGroupAction('main.user.activate')->onSelect[] = [$this, 'activateUsers'];
        $grid->addGroupAction('main.user.deactivate')->onSelect[] = [$this, 'deactivateUsers'];
        $grid->addGroupAction('main.user.delete')->onSelect[] = [$this, 'deleteUsers'];

        return $grid;
    }

}
