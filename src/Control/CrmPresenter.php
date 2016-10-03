<?php

namespace NAttreid\Crm\Control;

/**
 * Crm presenter
 *
 * @author Attreid <attreid@gmail.com>
 */
abstract class CrmPresenter extends Presenter
{
	protected function startup()
	{
		parent::startup();
		if (
			!$this->isLinkCurrent(":{$this->module}:Homepage:")
			&& !$this->isLinkCurrent(":{$this->module}:Profile:")
			&& !$this['dockbar']->isLinkAllowed($this->getAction(true))
		) {
			$this->flashNotifier->error('main.permissions.accessDenied');
			$this->redirect(":{$this->module}:Homepage:");
		}
	}
}
