<?php

declare(strict_types = 1);

namespace NAttreid\Cms\Routing;

use Nette\Application\Routers\Route;
use Nette\Utils\Strings;

/**
 * Router
 *
 * @author Attreid <attreid@gmail.com>
 */
class Router extends \NAttreid\Routing\Router
{

	/** @var string */
	private $namespace;

	/** @var */
	private $modules = [];

	public function __construct(string $namespace, string $url)
	{
		parent::__construct($url);
		$this->namespace = $namespace;
	}

	public function createRoutes()
	{
		ksort($this->modules);
		foreach ($this->modules as $module) {
			$routesModule = $this->getRouter(Strings::firstUpper($module));
			$routesModule[] = new Route($this->url . $module . '/<presenter>[/<action>]', 'Homepage:default');
		}

		$routes = $this->getRouter($this->namespace);
		$routes[] = new Route($this->url . '<presenter>[/<action>]', 'Homepage:default');
	}

	/**
	 * Prida modul do routy CMS
	 * @param string $module
	 */
	public function addModule(string $module)
	{
		$this->modules[] = $module;
	}

}
