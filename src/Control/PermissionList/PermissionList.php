<?php

declare(strict_types=1);

namespace NAttreid\Cms\Control;

use NAttreid\Security\Model\Acl\Acl;
use NAttreid\Security\Model\AclRoles\AclRole;
use NAttreid\Security\Model\Orm;
use NAttreid\Security\User;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\InvalidArgumentException;
use Nextras\Application\UI\SecuredLinksControlTrait;
use Nextras\Orm\Model\Model;

/**
 * Class PermissionList
 *
 * @author Attreid <attreid@gmail.com>
 */
class PermissionList extends Control
{
	use SecuredLinksControlTrait;

	/** @var AclRole */
	private $role;

	/** @var Orm */
	private $orm;

	/** @var User */
	private $user;

	public function __construct(Model $orm, User $user)
	{
		$this->orm = $orm;
		$this->user = $user;
	}

	public function setRole(AclRole $role): void
	{
		$this->role = $role;
	}

	public function render(): void
	{
		$this->template->setFile(__DIR__ . '/default.latte');

		$this->template->resources = $this->getResources();

		$this->template->render();
	}

	private function getResources(): array
	{
		$result = [];
		$resources = $this->orm->aclResources->findByResource();
		foreach ($resources as $resource) {
			$list = explode('.', $resource->resource);
			end($list);
			$last = key($list);
			/* @var $current ResourceItem */
			$current = null;

			foreach ($list as $key => $row) {
				if ($current === null) {
					if (isset($result[$row])) {
						$current = $result[$row];
					} else {
						if ($key === $last) {
							$item = new ResourceItem($this->user->getAuthorizator(), $resource, $this->role->name);
						} else {
							$item = new ResourceItem($this->user->getAuthorizator(), $row, $this->role->name);
						}
						$current = $result[$row] = $item;
					}
				} else {
					if (isset($current->items[$row])) {
						$current = $current->items[$row];
					} else {
						if ($key === $last) {
							$item = new ResourceItem($this->user->getAuthorizator(), $resource, $this->role->name, $current);
						} else {
							$item = new ResourceItem($this->user->getAuthorizator(), $row, $this->role->name, $current);
						}
						$current = $current->addItem($row, $item);
					}
				}
			}
		}
		return $result;
	}

	private function getResource(string $resource): ResourceItem
	{
		$result = null;
		$resources = $this->getResources();
		$list = explode('.', $resource);
		foreach ($list as $name) {
			if (!isset($resources[$name])) {
				throw new InvalidArgumentException;
			} else {
				$result = $resources[$name];
				$resources = $result->items;
			}
		}
		return $result;
	}

	/**
	 * @param string $resource
	 * @secured
	 * @throws AbortException
	 */
	public function handlePermission(string $resource): void
	{
		if ($this->presenter->isAjax()) {
			$childrenPermission = function (ResourceItem $resourceItem, bool $allowed = null) use (&$childrenPermission) {
				if ($resourceItem->resource) {
					$allowed = $this->savePermission($resourceItem, $allowed);
				} else {
					$allowed = $allowed ?? !$resourceItem->allowed;
				}
				foreach ($resourceItem->items as $item) {
					$childrenPermission($item, $allowed);
				}
				return $allowed;
			};
			$parentPermission = function (ResourceItem $resourceItem, bool $allowed) use (&$parentPermission) {
				if ($allowed) {
					$parent = $resourceItem->parent;
					if ($parent !== null) {
						if ($parent->resource) {
							$this->savePermission($parent, $allowed);
						}
						$parentPermission($parent, $allowed);
					}
				}
			};

			$resourceItem = $this->getResource($resource);
			$allowed = $childrenPermission($resourceItem);
			$parentPermission($resourceItem, $allowed);

			$this->orm->flush();
			$this->user->refreshPermissions();
			$this->redrawControl();
		} else {
			throw new AbortException;
		}
	}

	private function savePermission(ResourceItem $item, bool $allowed = null): bool
	{
		$permission = $this->orm->acl->getPermission($item->id, $this->role->name, $item->privilege);
		if (!$permission) {
			$permission = new Acl;
			$this->orm->acl->attach($permission);
			$permission->role = $this->role;
			$permission->privilege = $item->privilege;
			$permission->resource = $this->orm->aclResources->getByResource($item->id);
			$permission->allowed = true;
		} else {
			$permission->allowed = $allowed ?? !$permission->allowed;
		}
		$this->orm->persist($permission);
		return $permission->allowed;
	}
}

interface IPermissionListFactory
{
	public function create(): PermissionList;
}