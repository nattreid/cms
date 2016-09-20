<?php

namespace NAttreid\Crm;

use NAttreid\Menu\Menu;


interface ICrmMenuFactory
{
	/**  @return Menu */
	public function create();
}