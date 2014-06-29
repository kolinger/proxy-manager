<?php

namespace App;

use Nette\Application\IRouter;
use Nette\Application\Routers\CliRouter;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;
use Nette\DI\Container;

/**
 * @author Tomáš Kolinger <tomas@kolinger.me>
 */
class RouterFactory
{

	/** @var Container */
	private $container;


	public function __construct(Container $container)
	{
		$this->container = $container;
	}


	/**
	 * @return IRouter
	 */
	public function createRouter()
	{
		$router = new RouteList();

		if ($this->container->parameters['consoleMode']) {
			$router[] = new CliRouter(array('action' => 'Cron:default'));
			return $router;
		}

		$flags = 0;
		if (isset($this->container->parameters['https']) && $this->container->parameters['https']) {
			$flags = Route::SECURED;
		}
		$router[] = new Route('<presenter>[/<action>][/<id>]', 'Dashboard:default', $flags);
		return $router;
	}
}
