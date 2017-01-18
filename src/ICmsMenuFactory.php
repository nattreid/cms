<?php

namespace NAttreid\Cms;

use NAttreid\Menu\Menu\Menu;


interface ICmsMenuFactory
{
	/**  @return Menu */
	public function create();
}