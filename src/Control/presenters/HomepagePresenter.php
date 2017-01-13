<?php

namespace NAttreid\Crm\Control;

use NAttreid\AppManager\AppManager;
use NAttreid\Tracking\Tracking;

/**
 * Domovska stranka
 *
 * @author Attreid <attreid@gmail.com>
 */
class HomepagePresenter extends CrmPresenter
{

	/** @var AppManager */
	private $app;

	/** @var Tracking */
	private $tracking;

	public function __construct(AppManager $app, Tracking $tracking = null)
	{
		parent::__construct();
		$this->app = $app;
		$this->tracking = $tracking;
	}

	public function renderDefault()
	{
		$template = $this->template;

		if ($this->user->isAllowed('crm.homepage.info', 'view')) {
			$template->viewInfo = true;

			$template->ip = $this->app->info->ip;

			if (($load = $this->app->info->load)) {
				$template->load = $load;
			}

			if ($this->tracking !== null) {
				$template->onlineUsers = $this->tracking->onlineUsers();
			}
		} else {
			$template->viewInfo = false;
		}
	}

}
