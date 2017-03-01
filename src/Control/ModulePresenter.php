<?php

declare(strict_types = 1);

namespace NAttreid\Cms\Control;

/**
 * Presenter modulu
 *
 * @author Attreid <attreid@gmail.com>
 */
abstract class ModulePresenter extends Presenter
{
	protected function startup()
	{
		parent::startup();
		$link = $this->getAction(true);
		if (!($this['menu']->isLinkAllowed($link))) {
			$this->flashNotifier->error('cms.permissions.accessDenied');
			$this->redirect(":{$this->module}:Homepage:");
		}
	}
}
