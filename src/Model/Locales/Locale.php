<?php

namespace NAttreid\Crm\Model;

use Nextras\Orm\Entity\Entity;

/**
 * Locale
 *
 * @property int $id {primary-proxy}
 * @property string $name {primary}
 * @property boolean $default {default=FALSE}
 * @property boolean $allowed {default=TRUE}
 *
 * @author Attreid <attreid@gmail.com>
 */
class Locale extends Entity
{
	/**
	 * Nastavi na vychozi
	 */
	public function setDefault()
	{
		$repo = $this->getRepository();
		$locales = $repo->findAll();
		foreach ($locales as $locale) {
			/* @var $locale self */
			$locale->default = FALSE;
			$repo->persist($locale);
		}
		$this->default = TRUE;
		$repo->persist($this);
		$repo->flush();
	}
}
