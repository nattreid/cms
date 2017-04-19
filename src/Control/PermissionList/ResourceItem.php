<?php

declare(strict_types=1);

namespace NAttreid\Cms\Control;

use NAttreid\Security\Model\Acl\Acl;
use NAttreid\Security\Model\AclResources\AclResource;
use NAttreid\Utils\Strings;
use Nette\Security\IAuthorizator;
use Nette\SmartObject;

/**
 * Class ResourceItem
 *
 * @property-read bool $resource
 * @property-read string $name
 * @property-read ResourceItem[] $items
 * @property-read ResourceItem|null $parent
 * @property-read string $id
 * @property-read bool $hasItems
 * @property-read bool $allowed
 * @property-read string $privilege
 *
 * @author Attreid <attreid@gmail.com>
 */
class ResourceItem
{
	use SmartObject;

	/** @var bool */
	private $isResource;

	/** @var string */
	private $name;

	/** @var int */
	private $id;

	/** @var self[] */
	private $items = [];

	/** @var bool */
	private $isAllowed = false;

	/** @var self|null */
	private $parent;

	/** @var string */
	private $privilege;

	/**
	 * ResourceItem constructor.
	 * @param IAuthorizator $authorizator
	 * @param AclResource|string $data
	 * @param string $role
	 * @param self|null $parent
	 */
	public function __construct(IAuthorizator $authorizator, $data, string $role, self $parent = null)
	{
		if ($data instanceof AclResource) {
			$this->id = $data->resource;
			$this->name = $data->name;
			$this->isResource = true;
			$this->privilege = Strings::endsWith($this->name, 'edit') ? Acl::PRIVILEGE_EDIT : Acl::PRIVILEGE_VIEW;
			$this->isAllowed = $authorizator->isAllowed($role, $data->resource, $this->privilege);
		} else {
			$this->id = ($parent !== null ? $parent->id . '.' : '') . $data;
			$this->name = $this->id . '.title';
			$this->isResource = false;
		}
		$this->parent = $parent;
		$this->setParentPermission($this->isAllowed);
	}

	private function setParentPermission(bool $isAllowed): void
	{
		if ($this->parent) {
			if (!$this->parent->resource) {
				$this->parent->isAllowed = $this->parent->isAllowed || $isAllowed;
			}
			$this->parent->setParentPermission($isAllowed);
		}
	}

	/**
	 * @return string
	 */
	protected function getId(): string
	{
		return $this->id;
	}

	/**
	 * @return ResourceItem|null
	 */
	protected function getParent(): ?ResourceItem
	{
		return $this->parent;
	}

	/**
	 * @return string
	 */
	public function getPrivilege(): string
	{
		return $this->privilege;
	}

	/**
	 * @return bool
	 */
	protected function isResource(): bool
	{
		return $this->isResource;
	}

	/**
	 * @return bool
	 */
	public function isAllowed(): bool
	{
		return $this->isAllowed;
	}

	/**
	 * @return string
	 */
	protected function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return self[]
	 */
	protected function getItems(): array
	{
		return $this->items;
	}

	/**
	 * @return bool
	 */
	public function getHasItems(): bool
	{
		return count($this->items) > 0;
	}

	/**
	 * @param string $name
	 * @param self $item
	 * @return self
	 */
	public function addItem(string $name, self $item): self
	{
		return $this->items[$name] = $item;
	}
}