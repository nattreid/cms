<?php

declare(strict_types=1);

namespace NAttreid\Cms\Control;

use NAttreid\Form\IContainer;
use NAttreid\Security\AuthorizatorFactory;
use NAttreid\Security\Model\Acl\Acl;
use NAttreid\Security\Model\AclRoles\AclRole;
use NAttreid\Security\Model\AclRoles\AclRolesMapper;
use NAttreid\Security\Model\Orm;
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
use Nette\Forms\Container;
use Nette\Http\IResponse;
use Nette\InvalidArgumentException;
use Nette\Utils\ArrayHash;
use Nextras\Dbal\UniqueConstraintViolationException;
use Nextras\Orm\Model\Model;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridColumnStatusException;
use Ublaboo\DataGrid\Exception\DataGridException;

/**
 * Prava uzivatelu
 *
 * @author Attreid <attreid@gmail.com>
 */
class PermissionsPresenter extends CmsPresenter
{

	private $privileges = [
		Acl::PRIVILEGE_VIEW => 'default.view',
		Acl::PRIVILEGE_EDIT => 'default.edit'
	];
	private $access = [
		1 => 'cms.permissions.allowed',
		0 => 'cms.permissions.denied'
	];

	/** @var Orm */
	private $orm;

	/** @var AclRole */
	private $role;

	/** @var AuthorizatorFactory */
	private $authorizatorFactory;

	/** @var IPermissionListFactory */
	private $permissionListFactory;

	/** @var bool */
	private $viewSuperadmin;

	public function __construct(Model $orm, AuthorizatorFactory $authorizatorFactory, IPermissionListFactory $permissionListFactory)
	{
		parent::__construct();
		$this->orm = $orm;
		$this->authorizatorFactory = $authorizatorFactory;
		$this->permissionListFactory = $permissionListFactory;
	}

	protected function startup(): void
	{
		parent::startup();
		$this->viewSuperadmin = $this->user->isAllowed('dockbar.settings.permissions.superadmin', 'view');
	}

	/**
	 * Zobrazeni seznamu
	 */
	public function renderDefault(): void
	{
		$this->addBreadcrumbLink('dockbar.settings.permissions');
	}

	/**
	 * @param int $id
	 * @throws BadRequestException
	 */
	public function actionEditRolePermissions(int $id): void
	{
		$this->role = $this->orm->aclRoles->getById($id);
		if (!$this->role) {
			$this->error();
		}
		if (!$this->viewSuperadmin && $this->role->name === AclRolesMapper::SUPERADMIN) {
			$this->flashNotifier->error('cms.permissions.accessDenied');
			$this->error(null, IResponse::S403_FORBIDDEN);
		}
	}

	public function renderEditRolePermissions(): void
	{
		$this->addBreadcrumbLink('dockbar.settings.permissions', 'default');
		$this->addBreadcrumbLinkUntranslated($this->role->title);
		$this->template->role = $this->role;
	}

	/**
	 * Smazani role
	 * @param int $id
	 * @secured
	 * @throws AbortException
	 */
	public function handleDeleteRole(int $id): void
	{
		if ($this->isAjax()) {
			$role = $this->orm->aclRoles->getById($id);
			$this->orm->aclRoles->removeAndFlush($role);
			$this['rolesList']->reload();
		} else {
			$this->terminate();
		}
	}

	/**
	 * Smazani pravidlo
	 * @param int $id
	 * @secured
	 * @throws AbortException
	 */
	public function handleDeletePermission(int $id): void
	{
		if ($this->isAjax()) {
			$permission = $this->orm->acl->getById($id);
			$this->orm->acl->removeAndFlush($permission);
			$this['permissionsList']->reload();
		} else {
			$this->terminate();
		}
	}

	/**
	 * Smaze role
	 * @param array $ids
	 * @throws AbortException
	 */
	public function deleteRoles(array $ids): void
	{
		if ($this->isAjax()) {
			$roles = $this->orm->aclRoles->findById($ids);
			foreach ($roles as $role) {
				$this->orm->aclRoles->remove($role);
			}
			$this->orm->flush();
			$this['permissionsList']->reload();
		} else {
			$this->terminate();
		}
	}

	/**
	 * Smaze pravidla
	 * @param array $ids
	 * @throws AbortException
	 */
	public function deletePermissions(array $ids): void
	{
		if ($this->isAjax()) {
			$permissions = $this->orm->acl->findById($ids);
			foreach ($permissions as $permission) {
				$this->orm->acl->remove($permission);
			}
			$this->orm->flush();
			$this['permissionsList']->reload();
		} else {
			$this->terminate();
		}
	}

	/**
	 * Smazani nepouzitych zdroju (pro prehlednost)
	 * @secured
	 * @throws AbortException
	 */
	public function handleDeleteUnusedResources(): void
	{
		if ($this->isAjax()) {
			$this->orm->aclResources->deleteUnused();
			$this->flashNotifier->success('cms.permissions.unusedResourcesDeleted');
		} else {
			$this->terminate();
		}
	}

	/**
	 * Smazani cache ACL
	 * @secured
	 * @throws AbortException
	 */
	public function handleClearCacheACL(): void
	{
		if ($this->isAjax()) {
			$this->authorizatorFactory->cleanCache();
			$this->flashNotifier->success('cms.permissions.aclCacheCleared');
		} else {
			$this->terminate();
		}
	}

	/**
	 * Editace role
	 * @param Container $container
	 */
	public function roleForm(Container $container): void
	{
		$roles = ['' => $this->translate('form.none')] + $this->orm->aclRoles->fetchPairs($this->viewSuperadmin);
		$container->addText('title', 'cms.permissions.role')
			->setRequired();
		$container->addText('name', 'cms.permissions.name')
			->setRequired();
		$container->addSelect('parent', $this->translate('cms.permissions.parent'))
			->setTranslator()
			->setItems($roles);
	}

	/**
	 * Pridani role
	 * @param ArrayHash $values
	 * @throws AbortException
	 */
	public function addRole(ArrayHash $values): void
	{
		if ($this->isAjax()) {
			try {
				$role = new AclRole;
				$this->orm->aclRoles->attach($role);
				$role->name = $values->name;
				$role->title = $values->title;
				$role->parent = $values->parent;

				$this->orm->persistAndFlush($role);

				$this->flashNotifier->success('default.dataSaved');
				$this['rolesList']->reload();
			} catch (UniqueConstraintViolationException $ex) {
				$this->flashNotifier->error('cms.permissions.duplicityName');
			} catch (InvalidArgumentException $ex) {
				$this->flashNotifier->error('cms.permissions.invalidName');
			}
		} else {
			$this->terminate();
		}
	}

	/**
	 * Editace pravidla
	 * @param Container $container
	 */
	public function permissionForm(Container $container): void
	{
		/* @var $container IContainer */
		$container->addSelectUntranslated('role', 'cms.permissions.role')
			->setItems($this->orm->aclRoles->fetchPairs($this->viewSuperadmin));
		$container->addMultiSelectUntranslated('resource', 'cms.permissions.resource')
			->setItems($this->orm->aclResources->fetchPairsByResourceName());
		$container->addSelect('privilege', 'cms.permissions.privilege', $this->privileges);
		$container->addSelect('allowed', 'default.state', $this->access);
	}

	/**
	 * Pridani pravidla
	 * @param ArrayHash $values
	 * @throws AbortException
	 */
	public function addPermission(ArrayHash $values): void
	{
		if ($this->isAjax()) {
			foreach ($values->resource as $resource) {
				try {
					$permission = new Acl;
					$this->orm->acl->attach($permission);
					$permission->role = $values->role;
					$permission->privilege = $values->privilege;
					$permission->resource = $resource;
					$permission->allowed = $values->allowed;

					$this->orm->persistAndFlush($permission);
				} catch (UniqueConstraintViolationException $ex) {

				}
			}

			$this->flashNotifier->success('default.dataSaved');
			$this['permissionsList']->reload();
		} else {
			$this->terminate();
		}
	}

	/**
	 * Ulozi nazev role
	 * @param int $id
	 * @param string $value
	 * @throws AbortException
	 */
	public function setRoleTitle(int $id, string $value): void
	{
		if ($this->isAjax()) {
			$role = $this->orm->aclRoles->getById($id);
			$role->title = $value;
			$this->orm->persistAndFlush($role);

			$this->flashNotifier->success('default.dataSaved');
		} else {
			$this->terminate();
		}
	}

	/**
	 * Ulozi jmeno role
	 * @param int $id
	 * @param string $value
	 * @throws AbortException
	 */
	public function setRoleName(int $id, string $value): void
	{
		if ($this->isAjax()) {
			$grid = $this['rolesList'];
			try {
				$role = $this->orm->aclRoles->getById($id);
				$role->setName($value);
				$this->orm->persistAndFlush($role);
				$this->flashNotifier->success('default.dataSaved');
			} catch (UniqueConstraintViolationException $ex) {
				$this->flashNotifier->error('cms.permissions.duplicityName');
				$grid->redrawItem($id);
			} catch (InvalidArgumentException $ex) {
				$this->flashNotifier->error('cms.permissions.invalidName');
				$grid->redrawItem($id);
			}
		} else {
			$this->terminate();
		}
	}

	/**
	 * Ulozi rodice role
	 * @param int $id
	 * @param string $value
	 * @throws AbortException
	 */
	public function setRoleParent(int $id, string $value): void
	{
		if ($this->isAjax()) {
			$role = $this->orm->aclRoles->getById($id);
			$role->parent = $value;
			$this->orm->persistAndFlush($role);

			$this->flashNotifier->success('default.dataSaved');
			$this['rolesList']->redrawItem($id);
		} else {
			$this->terminate();
		}
	}

	/**
	 * Nastavi roli pravidlu
	 * @param int $id
	 * @param int $value
	 * @throws AbortException
	 */
	public function setPermissionRole(int $id, int $value): void
	{
		if ($this->isAjax()) {
			$acl = $this->orm->acl->getById($id);
			$acl->role = $value;
			$this->orm->persistAndFlush($acl);

			$this->flashNotifier->success('default.dataSaved');

			$this['permissionsList']->redrawItem($id);
		} else {
			$this->terminate();
		}
	}

	/**
	 * Nastavi zdroj pravidlu
	 * @param int $id
	 * @param int $value
	 * @throws AbortException
	 */
	public function setPermissionResource(int $id, int $value): void
	{
		if ($this->isAjax()) {
			$acl = $this->orm->acl->getById($id);
			$acl->resource = $value;
			$this->orm->persistAndFlush($acl);

			$this->flashNotifier->success('default.dataSaved');

			$this['permissionsList']->redrawItem($id);
		} else {
			$this->terminate();
		}
	}

	/**
	 * Ulozi operaci pravidla
	 * @param int $id
	 * @param string $value
	 * @throws AbortException
	 */
	public function setPermissionPrivilege(int $id, string $value): void
	{
		if ($this->isAjax()) {
			$permission = $this->orm->acl->getById($id);
			$permission->privilege = $value;
			$this->orm->persistAndFlush($permission);

			$this->flashNotifier->success('default.dataSaved');

			$this['permissionsList']->redrawItem($id);
		} else {
			$this->terminate();
		}
	}

	/**
	 * Ulozi stav pravidla
	 * @param int $id
	 * @param bool $value
	 * @throws AbortException
	 */
	public function setPermissionState(int $id, bool $value): void
	{
		if ($this->isAjax()) {
			$permission = $this->orm->acl->getById($id);
			$permission->allowed = $value;
			$this->orm->persistAndFlush($permission);

			$this->flashNotifier->success('default.dataSaved');

			$this['permissionsList']->redrawItem($id);
		} else {
			$this->terminate();
		}
	}

	/**
	 * Seznam roli
	 * @return DataGrid
	 * @throws DataGridException
	 */
	protected function createComponentRolesList(): DataGrid
	{
		$grid = $this->dataGridFactory->create();

		$grid->setDataSource($this->orm->aclRoles->findRoles($this->viewSuperadmin));

		$grid->addColumnText('title', 'cms.permissions.role')
			->setEditableInputType('text')
			->setEditableCallback([$this, 'setRoleTitle']);

		$grid->addColumnText('name', 'cms.permissions.name')
			->setEditableInputType('text')
			->setEditableCallback([$this, 'setRoleName']);

		$grid->addColumnText('parent', 'cms.permissions.parent')
			->setRenderer(function (AclRole $role) {
				if (!empty($role->parent)) {
					return $role->parent->title;
				}
				return null;
			})
			->setEditableInputTypeSelect([0 => $this->translate('form.none')] + $this->orm->aclRoles->fetchPairs($this->viewSuperadmin))
			->setEditableCallback([$this, 'setRoleParent']);

		$grid->addAction('edit', null, 'editRolePermissions')
			->setIcon('pencil')
			->setTitle('default.edit');

		$grid->addAction('delete', null, 'deleteRole!')
			->setIcon('trash')
			->setTitle('default.delete')
			->setClass('btn btn-xs btn-danger ajax')
			->setConfirm(function (AclRole $role) {
				return $this->translate('cms.permissions.confirmDeleteRole', 1, ['name' => $role->title]);
			});

		$add = $grid->addInlineAdd()
			->setPositionTop()
			->setTitle('cms.permissions.addRole');
		$add->onControlAdd[] = [$this, 'roleForm'];
		$add->onSubmit[] = [$this, 'addRole'];

		$grid->addGroupAction('default.delete')->onSelect[] = [$this, 'deleteRoles'];

		return $grid;
	}

	/**
	 * Seznam pravidel
	 * @return DataGrid
	 * @throws DataGridException
	 * @throws DataGridColumnStatusException
	 */
	protected function createComponentPermissionsList(): DataGrid
	{
		$grid = $this->dataGridFactory->create();

		$grid->setDataSource($this->orm->acl->findByRoles($this->viewSuperadmin));

		$deleteUnusedResources = $grid->addToolbarButton('deleteUnusedResources!', 'cms.permissions.deleteUnusedResources');
		$deleteUnusedResources->setClass($deleteUnusedResources->getClass() . ' ajax');
		$clearCacheACL = $grid->addToolbarButton('clearCacheAcl!', 'cms.permissions.clearCacheAcl');
		$clearCacheACL->setClass($clearCacheACL->getClass() . ' ajax');

		$grid->addColumnText('role', 'cms.permissions.role')
			->setRenderer(function (Acl $acl) {
				return $acl->role->title;
			})
			->setEditableInputTypeSelect($this->orm->aclRoles->fetchPairs($this->viewSuperadmin))
			->setEditableCallback([$this, 'setPermissionRole'])
			->setFilterSelect(['' => $this->translate('form.none')] + $this->orm->aclRoles->fetchPairs($this->viewSuperadmin));

		$grid->addColumnText('resource', 'cms.permissions.resource')
			->setRenderer(function (Acl $acl) {
				return $acl->resource->resource . ' - ( ' . $this->translate($acl->resource->name) . ' )';
			})
			->setEditableInputTypeSelect($this->orm->aclResources->fetchPairsByResourceName())
			->setEditableCallback([$this, 'setPermissionResource'])
			->setFilterSelect(['' => $this->translate('form.none')] + $this->orm->aclResources->fetchPairsByResourceName());

		$privilege = $grid->addColumnStatus('privilege', 'cms.permissions.privilege');
		$privilege->setFilterSelect(['' => 'form.none'] + $this->privileges)
			->setTranslateOptions();
		foreach ($this->privileges as $key => $name) {
			$privilege->addOption($key, $name)
				->setClass('btn-default');
		}
		$privilege->onChange[] = [$this, 'setPermissionPrivilege'];

		$state = $grid->addColumnStatus('allowed', 'default.state');
		$state->setFilterSelect(['' => 'form.none'] + $this->access)
			->setTranslateOptions();
		$state->addOption(1, 'cms.permissions.allowed')
			->setClass('btn-success');
		$state->addOption(0, 'cms.permissions.denied')
			->setClass('btn-danger');
		$state->onChange[] = [$this, 'setPermissionState'];

		$grid->addAction('delete', null, 'deletePermission!')
			->setIcon('trash')
			->setTitle('default.delete')
			->setClass('btn btn-xs btn-danger ajax')
			->setConfirm(function (Acl $permission) {
				return $this->translate('cms.permissions.confirmDeletePermission', 1, ['name' => $permission->resource->name]);
			});

		$add = $grid->addInlineAdd()
			->setPositionTop()
			->setTitle('cms.permissions.addPermission');
		$add->onControlAdd[] = [$this, 'permissionForm'];
		$add->onSubmit[] = [$this, 'addPermission'];

		$grid->addGroupAction('default.delete')->onSelect[] = [$this, 'deletePermissions'];

		return $grid;
	}

	protected function createComponentEditRolePermissions(): PermissionList
	{
		$control = $this->permissionListFactory->create();
		$control->setRole($this->role);
		return $control;
	}
}
