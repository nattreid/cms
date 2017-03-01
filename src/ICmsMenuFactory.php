<?php

declare(strict_types = 1);

namespace NAttreid\Cms;

use NAttreid\Menu\Menu\Menu;


interface ICmsMenuFactory
{
	public function create(): Menu;
}