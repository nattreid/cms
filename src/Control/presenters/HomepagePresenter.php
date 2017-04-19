<?php

declare(strict_types=1);

namespace NAttreid\Cms\Control;

use NAttreid\AppManager\AppManager;
use NAttreid\Tracking\Tracking;

/**
 * Domovska stranka
 *
 * @author Attreid <attreid@gmail.com>
 */
class HomepagePresenter extends CmsPresenter
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

	public function renderDefault(): void
	{
		$template = $this->template;

		if ($this->user->isAllowed('cms.homepage.info', 'view')) {
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
