<?php

namespace NAttreid\Crm\Routing;

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

	public function __construct($namespace, $url = null)
	{
		parent::__construct($url);
		$this->namespace = $namespace;
	}

	public function createRoutes()
	{
		$routesExt = $this->getRouter($this->namespace . 'Ext');
		$routesExt[] = new Route($this->getUrl() . 'ext/<presenter>[/<action>]', 'Homepage:default');

		ksort($this->modules);
		foreach ($this->modules as $module) {
			$routesModule = $this->getRouter(Strings::firstUpper($module));
			$routesModule[] = new Route($this->getUrl() . $module . '/<presenter>[/<action>]', 'Homepage:default');
		}

		$routes = $this->getRouter($this->namespace);
		$routes[] = new Route($this->getUrl() . '<presenter>[/<action>]', 'Homepage:default');
	}

	/**
	 * Prida modul do routy CRM
	 * @param string $module
	 */
	public function addModule($module)
	{
		$this->modules[] = $module;
	}

}
