<?php

declare(strict_types=1);

namespace NAttreid\Cms\Control;

/**
 * Cms presenter
 *
 * @author Attreid <attreid@gmail.com>
 */
abstract class CmsPresenter extends Presenter
{
	protected function startup()
	{
		parent::startup();
		if (
			!$this->isLinkCurrent(":{$this->module}:Homepage:")
			&& !$this->isLinkCurrent(":{$this->module}:Profile:")
			&& !$this['dockbar']->isLinkAllowed($this->getAction(true))
		) {
			$this->flashNotifier->error('cms.permissions.accessDenied');
			$this->redirect(":{$this->module}:Homepage:");
		}
	}
}
