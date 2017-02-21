<?php

namespace NAttreid\Cms\Control;

use NAttreid\Security\AuthorizatorFactory;
use NAttreid\Security\Model\Acl;
use NAttreid\Security\Model\AclRole;
use NAttreid\Security\Model\Orm;
use NAttreid\Security\Model\ResourceItem;
use Nette\Forms\Container;
use Nette\InvalidArgumentException;
use Nette\Utils\ArrayHash;
use Nextras\Dbal\UniqueConstraintViolationException;
use Nextras\Orm\Model\Model;
use Ublaboo\DataGrid\DataGrid;

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

	public function __construct(Model $orm, AuthorizatorFactory $authorizatorFactory)
	{
		parent::__construct();
		$this->orm = $orm;
		$this->authorizatorFactory = $authorizatorFactory;
	}

	/**
	 * Zobrazeni seznamu
	 */
	public function renderDefault()
	{
		$this->addBreadcrumbLink('dockbar.settings.permissions');
	}

	public function actionEditRolePermissions($id)
	{
		$this->role = $this->orm->aclRoles->getById($id);
		if (!$this->role) {
			$this->error();
		}
	}

	public function renderEditRolePermissions()
	{
		$this->addBreadcrumbLink('dockbar.settings.permissions', 'default');
		$this->addBreadcrumbLinkUntranslated($this->role->title);
		$this->template->role = $this->role;
	}

	/**
	 * Smazani role
	 * @param int $id
	 * @secured
	 */
	public function handleDeleteRole($id)
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
	 */
	public function handleDeletePermission($id)
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
	 */
	public function deleteRoles(array $ids)
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
	 */
	public function deletePermissions(array $ids)
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
	 */
	public function handleDeleteUnusedResources()
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
	 */
	public function handleClearCacheACL()
	{
		if ($this->isAjax()) {
			$this->authorizatorFactory->cleanCache();
			$this->flashNotifier->success('cms.permissions.aclCacheCleared');
		} else {
			$this->terminate();
		}
	}

	/**
	 * @param $resource
	 */
	public function handlePermission($resource)
	{
		$permission = $this->orm->acl->getPermission($resource, $this->role->name);
		if (!$permission) {
			$permission = new Acl;
			$this->orm->acl->attach($permission);
			$permission->role = $this->role;
			$permission->privilege = Acl::PRIVILEGE_VIEW;
			$permission->resource = $this->orm->aclResources->getByResource($resource);
			$permission->allowed = true;
		} else {
			$permission->allowed = !$permission->allowed;
		}
		$this->orm->persistAndFlush($permission);
		$grid = $this['editRolePermissions'];
		$grid->setDataSource([$this->orm->aclResources->getResource($this->role->name, $resource)]);
		$grid->redrawItem($resource);
		$this->flashNotifier->success('default.dataSaved');
	}

	/**
	 * Editace role
	 * @param Container $container
	 */
	public function roleForm(Container $container)
	{
		$roles = ['' => $this->translate('form.none')] + $this->orm->aclRoles->fetchPairs();
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
	 */
	public function addRole($values)
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
				$this->flashNotifier->error('cms.permissions.dupliciteName');
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
	public function permissionForm(Container $container)
	{
		$container->addSelect('role', $this->translate('cms.permissions.role'))
			->setTranslator()
			->setItems($this->orm->aclRoles->fetchPairs());
		$container->addMultiSelect('resource', $this->translate('cms.permissions.resource'))
			->setItems($this->orm->aclResources->fetchPairsByResourceName())
			->setTranslator();
		$container->addSelect('privilege', 'cms.permissions.privilege', $this->privileges);
		$container->addSelect('allowed', 'default.state', $this->access);
	}

	/**
	 * Pridani pravidla
	 * @param ArrayHash $values
	 */
	public function addPermission($values)
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
	 * @param array $value
	 */
	public function setRoleTitle($id, $value)
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
	 * @param array $value
	 */
	public function setRoleName($id, $value)
	{
		if ($this->isAjax()) {
			$grid = $this['rolesList'];
			try {
				$role = $this->orm->aclRoles->getById($id);
				$role->setName($value);
				$this->orm->persistAndFlush($role);
				$this->flashNotifier->success('default.dataSaved');
			} catch (UniqueConstraintViolationException $ex) {
				$this->flashNotifier->error('cms.permissions.dupliciteName');
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
	 * @param array $value
	 */
	public function setRoleParent($id, $value)
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
	 * @param array $value
	 */
	public function setPermissionRole($id, $value)
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
	 * @param array $value
	 */
	public function setPermissionResource($id, $value)
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
	 * @param boolean $value
	 */
	public function setPermissionPrivilege($id, $value)
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
	 * @param boolean $value
	 */
	public function setPermissionState($id, $value)
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
	 * @param string $name
	 * @return DataGrid
	 */
	protected function createComponentRolesList($name)
	{
		$grid = $this->dataGridFactory->create($this, $name);

		$grid->setDataSource($this->orm->aclRoles->findAll());

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
			->setEditableInputTypeSelect([0 => $this->translate('form.none')] + $this->orm->aclRoles->fetchPairs())
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
	 * @param string $name
	 * @return DataGrid
	 */
	protected function createComponentPermissionsList($name)
	{
		$grid = $this->dataGridFactory->create($this, $name);

		$grid->setDataSource($this->orm->acl->findAll());

		$deleteUnusedResources = $grid->addToolbarButton('deleteUnusedResources!', 'cms.permissions.deleteUnusedResources');
		$deleteUnusedResources->setClass($deleteUnusedResources->getClass() . ' ajax');
		$clearCacheACL = $grid->addToolbarButton('clearCacheAcl!', 'cms.permissions.clearCacheAcl');
		$clearCacheACL->setClass($clearCacheACL->getClass() . ' ajax');

		$grid->addColumnText('role', 'cms.permissions.role')
			->setRenderer(function (Acl $acl) {
				return $acl->role->title;
			})
			->setEditableInputTypeSelect($this->orm->aclRoles->fetchPairs())
			->setEditableCallback([$this, 'setPermissionRole'])
			->setFilterSelect(['' => $this->translate('form.none')] + $this->orm->aclRoles->fetchPairs());

		$grid->addColumnText('resource', 'cms.permissions.resource')
			->setRenderer(function (Acl $acl) {
				return $this->translate($acl->resource->name) . ' (' . $acl->resource->resource . ')';
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

	protected function createComponentEditRolePermissions($name)
	{
		$grid = $this->dataGridFactory->create($this, $name);
		$grid->setDataSource($this->orm->aclResources->getResources($this->role->name));

		$grid->setTreeView([$this, 'getChildren'], 'hasChildren');

		$grid->addColumnText('name', 'cms.permissions.resource')
			->setRenderer(function (ResourceItem $item) {
				return $this->translate($item->name);
			});

		$grid->addAction('permission', null, 'permission!', ['resource' => 'id'])
			->setClass(function (ResourceItem $item) {
				return $item->allowed ? 'btn btn-xs btn-success ajax' : 'btn btn-xs btn-default ajax';
			})
			->setIcon(function (ResourceItem $item) {
				return $item->allowed ? 'check' : 'close';
			});

		$grid->allowRowsAction('permission', function (ResourceItem $item) {
			return $item->resource !== null;
		});

		return $grid;
	}

	public function getChildren($id)
	{
		return $this->orm->aclResources->getResources($this->role->name, $id);
	}
}
