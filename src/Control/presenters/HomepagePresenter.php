<?php

namespace NAttreid\Crm\Control;

use NAttreid\AppManager\Info;
use NAttreid\Tracking\Tracking;

/**
 * Domovska stranka
 *
 * @author Attreid <attreid@gmail.com>
 */
class HomepagePresenter extends CrmPresenter
{

	/** @var Info */
	private $info;

	/** @var Tracking */
	private $tracking;

	public function __construct(Info $info, Tracking $tracking = null)
	{
		parent::__construct();
		$this->info = $info;
		$this->tracking = $tracking;
	}

	public function renderDefault()
	{
		$template = $this->template;

		if ($this->user->isAllowed('crm.homepage.info', 'view')) {
			$template->viewInfo = true;

			$template->ip = $this->info->ip;

			if (($load = $this->info->load)) {
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
