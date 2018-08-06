<?php

declare(strict_types=1);

namespace NAttreid\Cms\Control\Dockbar;

use NAttreid\Utils\Strings;
use Nette\SmartObject;

/**
 * Class Item
 *
 * @property bool $handler
 * @property string $link
 * @property string $name
 * @property string $class
 * @property bool $ajax
 * @property string $confirm
 * @property string $title
 * @property Item[] $items
 * @property string $resource
 * @property bool $hasItems
 * @property bool $newWindow
 *
 * @author Attreid <attreid@gmail.com>
 */
class Item
{
	use SmartObject;

	/** @var self[] */
	private $items = [];

	/** @var string */
	private $name;

	/** @var string */
	private $resource;

	/** @var string */
	private $link;

	/** @var bool */
	private $handler;

	/** @var bool */
	private $ajax = false;

	/** @var string */
	private $confirm;

	/** @var string[] */
	private $class = [];

	/** @var bool */
	private $newWindow = false;

	public function __construct(string $name, array $item = null, string $parent = null, string $module = null)
	{
		$this->name = $name;
		$this->resource = ($parent !== null ? $parent . '.' : '') . $name;
		if ($this->_isLink($item)) {
			$this->parseLink($item, $module);
			$this->parseAjax($item);
			$this->parseConfirm($item);
		}
	}

	/**
	 * Nastavi tridu elementu
	 * @param string $class
	 * @return self
	 */
	public function addClass(string $class): self
	{
		$this->class[] = $class;
		return $this;
	}

	/**
	 * Prida item
	 * @param Item $item
	 * @return Item
	 */
	public function addItem(Item $item): Item
	{
		return $this->items[] = $item;
	}

	/**
	 * @param bool $bool
	 * @return self
	 */
	public function setNewWindow(bool $bool = true): self
	{
		$this->newWindow = $bool;
		return $this;
	}

	public function getNewWindow(): bool
	{
		return $this->newWindow;
	}

	public function isLink(): bool
	{
		return $this->link !== null;
	}

	protected function getClass(): string
	{
		$class = implode(' ', $this->class);
		return Strings::webalize($this->name, null, false) . ($class ? ' ' . $class : '');
	}

	public function isAjax(): bool
	{
		return $this->ajax;
	}

	protected function getLink(): string
	{
		return $this->link;
	}

	protected function getTitle(): string
	{
		return $this->resource . ($this->isLink() ? '' : '.title');
	}

	protected function isHandler(): bool
	{
		return $this->handler;
	}

	protected function getResource(): string
	{
		return $this->resource;
	}

	protected function getConfirm()
	{
		return $this->confirm;
	}

	protected function getItems(): array
	{
		return $this->items;
	}

	protected function getHasItems(): bool
	{
		return count($this->items) > 0;
	}

	protected function getName(): string
	{
		return $this->name;
	}

	private function parseLink(array $item = null, string $module = null): void
	{
		if (isset($item['link'])) {
			$link = $item['link'] = ($module !== null ? ":$module:" : '') . $item['link'];
			if (Strings::endsWith($link, ':default')) {
				$link = substr($link, 0, -7);
			}
			$this->link = $link;
			$this->handler = false;
		} else {
			$this->link = $this->name;
			$this->handler = true;
		}
	}

	private function parseAjax(array $item = null): void
	{
		$this->ajax = $item['ajax'] ?? false;
	}

	private function parseConfirm(array $item = null): void
	{
		$this->confirm = $item['confirm'] ?? null;
	}

	/**
	 * Je link
	 * @param string|array $item
	 * @return bool
	 */
	private function _isLink($item): bool
	{
		if ($item === null) {
			return true;
		} elseif (is_array($item)) {
			return !is_array(current($item));
		} else {
			throw new \InvalidArgumentException('Cms menu items is wrong in config.neon');
		}
	}
}