<?php

declare(strict_types = 1);

namespace NAttreid\Cms\Factories;

use NAttreid\Menu\Menu\Menu;

interface ICmsMenuFactory
{
	public function create(): Menu;
}