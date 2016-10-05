<?php

namespace NAttreid\Crm\Control;

use NAttreid\Security\AuthorizatorFactory;
use NAttreid\Security\Model\Acl;
use NAttreid\Security\Model\AclRole;
use NAttreid\Security\Model\Orm;
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
class PermissionsPresenter extends CrmPresenter
{

	private $privileges = [
		Acl::PRIVILEGE_VIEW => 'default.view',
		Acl::PRIVILEGE_EDIT => 'default.edit'
	];
	private $access = [
		1 => 'crm.permissions.allowed',
		0 => 'crm.permissions.denied'
	];

	/** @var Orm */
	private $orm;

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
	public function handleDeleteRule($id)
	{
		if ($this->isAjax()) {
			$rule = $this->orm->acl->getById($id);
			$this->orm->acl->removeAndFlush($rule);
			$this['rulesList']->reload();
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
			$this['rulesList']->reload();
		} else {
			$this->terminate();
		}
	}

	/**
	 * Smaze pravidla
	 * @param array $ids
	 */
	public function deleteRules(array $ids)
	{
		if ($this->isAjax()) {
			$rules = $this->orm->acl->findById($ids);
			foreach ($rules as $rule) {
				$this->orm->acl->remove($rule);
			}
			$this->orm->flush();
			$this['rulesList']->reload();
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
			$this->flashNotifier->success('crm.permissions.unusedResourcesDeleted');
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
			$this->flashNotifier->success('crm.permissions.aclCacheCLeared');
		} else {
			$this->terminate();
		}
	}

	/**
	 * Editace role
	 * @param Container $container
	 */
	public function roleForm(Container $container)
	{
		$roles = ['' => $this->translate('form.none')] + $this->orm->aclRoles->fetchPairs();
		$container->addText('title', 'crm.permissions.role')
			->setRequired();
		$container->addText('name', 'crm.permissions.name')
			->setRequired();
		$container->addSelect('parent', $this->translate('crm.permissions.parent'))
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
				$this->flashNotifier->error('crm.permissions.dupliciteName');
			} catch (InvalidArgumentException $ex) {
				$this->flashNotifier->error('crm.permissions.invalidName');
			}
		} else {
			$this->terminate();
		}
	}

	/**
	 * Editace pravidla
	 * @param Container $container
	 */
	public function ruleForm(Container $container)
	{
		$container->addSelect('role', $this->translate('crm.permissions.role'))
			->setTranslator()
			->setItems($this->orm->aclRoles->fetchPairs());
		$container->addMultiSelect('resource', $this->translate('crm.permissions.resource'))
			->setTranslator()
			->setItems($this->orm->aclResources->fetchPairsByName());
		$container->addSelect('privilege', 'crm.permissions.privilege', $this->privileges);
		$container->addSelect('allowed', 'default.state', $this->access);
	}

	/**
	 * Pridani pravidla
	 * @param ArrayHash $values
	 */
	public function addRule($values)
	{
		if ($this->isAjax()) {
			foreach ($values->resource as $resource) {
				try {
					$rule = new Acl;
					$this->orm->acl->attach($rule);
					$rule->role = $values->role;
					$rule->privilege = $values->privilege;
					$rule->resource = $resource;
					$rule->allowed = $values->allowed;

					$this->orm->persistAndFlush($rule);
				} catch (UniqueConstraintViolationException $ex) {

				}
			}

			$this->flashNotifier->success('default.dataSaved');
			$this['rulesList']->reload();
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
				$this->flashNotifier->error('crm.permissions.dupliciteName');
				$grid->redrawItem($id);
			} catch (InvalidArgumentException $ex) {
				$this->flashNotifier->error('crm.permissions.invalidName');
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
	public function setRuleRole($id, $value)
	{
		if ($this->isAjax()) {
			$acl = $this->orm->acl->getById($id);
			$acl->role = $value;
			$this->orm->persistAndFlush($acl);

			$this->flashNotifier->success('default.dataSaved');

			$this['rulesList']->redrawItem($id);
		} else {
			$this->terminate();
		}
	}

	/**
	 * Nastavi zdroj pravidlu
	 * @param int $id
	 * @param array $value
	 */
	public function setRuleResource($id, $value)
	{
		if ($this->isAjax()) {
			$acl = $this->orm->acl->getById($id);
			$acl->resource = $value;
			$this->orm->persistAndFlush($acl);

			$this->flashNotifier->success('default.dataSaved');

			$this['rulesList']->redrawItem($id);
		} else {
			$this->terminate();
		}
	}

	/**
	 * Ulozi operaci pravidla
	 * @param int $id
	 * @param boolean $value
	 */
	public function setRulePrivilege($id, $value)
	{
		if ($this->isAjax()) {
			$rule = $this->orm->acl->getById($id);
			$rule->privilege = $value;
			$this->orm->persistAndFlush($rule);

			$this->flashNotifier->success('default.dataSaved');

			$this['rulesList']->redrawItem($id);
		} else {
			$this->terminate();
		}
	}

	/**
	 * Ulozi stav pravidla
	 * @param int $id
	 * @param boolean $value
	 */
	public function setRuleState($id, $value)
	{
		if ($this->isAjax()) {
			$rule = $this->orm->acl->getById($id);
			$rule->allowed = $value;
			$this->orm->persistAndFlush($rule);

			$this->flashNotifier->success('default.dataSaved');

			$this['rulesList']->redrawItem($id);
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

		$grid->addColumnText('title', 'crm.permissions.role')
			->setEditableInputType('text')
			->setEditableCallback([$this, 'setRoleTitle']);

		$grid->addColumnText('name', 'crm.permissions.name')
			->setEditableInputType('text')
			->setEditableCallback([$this, 'setRoleName']);

		$grid->addColumnText('parent', 'crm.permissions.parent')
			->setRenderer(function (AclRole $role) {
				if (!empty($role->parent)) {
					return $role->parent->title;
				}
				return null;
			})
			->setEditableInputTypeSelect([0 => $this->translate('form.none')] + $this->orm->aclRoles->fetchPairs())
			->setEditableCallback([$this, 'setRoleParent']);

		$grid->addAction('delete', null, 'deleteRole!')
			->setIcon('trash')
			->setTitle('default.delete')
			->setClass('btn btn-xs btn-danger ajax')
			->setConfirm(function (AclRole $role) {
				return $this->translate('crm.permissions.confirmDeleteRole', 1, ['name' => $role->title]);
			});

		$add = $grid->addInlineAdd()
			->setPositionTop()
			->setTitle('crm.permissions.addRole');
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
	protected function createComponentRulesList($name)
	{
		$grid = $this->dataGridFactory->create($this, $name);

		$grid->setDataSource($this->orm->acl->findAll());

		$deleteUnusedResources = $grid->addToolbarButton('deleteUnusedResources!', 'crm.permissions.deleteUnusedResources');
		$deleteUnusedResources->setClass($deleteUnusedResources->getClass() . ' ajax');
		$clearCacheACL = $grid->addToolbarButton('clearCacheAcl!', 'crm.permissions.clearCacheAcl');
		$clearCacheACL->setClass($clearCacheACL->getClass() . ' ajax');

		$grid->addColumnText('role', 'crm.permissions.role')
			->setRenderer(function (Acl $acl) {
				return $acl->role->title;
			})
			->setEditableInputTypeSelect($this->orm->aclRoles->fetchPairs())
			->setEditableCallback([$this, 'setRuleRole'])
			->setFilterSelect(['' => $this->translate('form.none')] + $this->orm->aclRoles->fetchPairs());

		$grid->addColumnText('resource', 'crm.permissions.resource')
			->setRenderer(function (Acl $acl) {
				return $acl->resource->name;
			})
			->setEditableInputTypeSelect($this->orm->aclResources->fetchPairsByName())
			->setEditableCallback([$this, 'setRuleResource'])
			->setFilterSelect(['' => $this->translate('form.none')] + $this->orm->aclResources->fetchPairsByName());

		$privilege = $grid->addColumnStatus('privilege', 'crm.permissions.privilege');
		$privilege->setFilterSelect(['' => 'form.none'] + $this->privileges)
			->setTranslateOptions();
		foreach ($this->privileges as $key => $name) {
			$privilege->addOption($key, $name)
				->setClass('btn-default');
		}
		$privilege->onChange[] = [$this, 'setRulePrivilege'];

		$state = $grid->addColumnStatus('allowed', 'default.state');
		$state->setFilterSelect(['' => 'form.none'] + $this->access)
			->setTranslateOptions();
		$state->addOption(1, 'crm.permissions.allowed')
			->setClass('btn-success');
		$state->addOption(0, 'crm.permissions.denied')
			->setClass('btn-danger');
		$state->onChange[] = [$this, 'setRuleState'];

		$grid->addAction('delete', null, 'deleteRule!')
			->setIcon('trash')
			->setTitle('default.delete')
			->setClass('btn btn-xs btn-danger ajax')
			->setConfirm(function (Acl $rule) {
				return $this->translate('crm.permissions.confirmDeleteRule', 1, ['name' => $rule->resource->name]);
			});

		$add = $grid->addInlineAdd()
			->setPositionTop()
			->setTitle('crm.permissions.addRule');
		$add->onControlAdd[] = [$this, 'ruleForm'];
		$add->onSubmit[] = [$this, 'addRule'];

		$grid->addGroupAction('default.delete')->onSelect[] = [$this, 'deleteRules'];

		return $grid;
	}

}
