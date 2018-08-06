<?php

declare(strict_types=1);

namespace NAttreid\Cms;

use NAttreid\Cms\Control\AbstractPresenter;
use Nette\Application\UI\ITemplate;

/**
 * Interface ISettings
 *
 * @author Attreid <attreid@gmail.com>
 */
interface ISettings
{
	public function init(ITemplate $template, AbstractPresenter $presenter);
}