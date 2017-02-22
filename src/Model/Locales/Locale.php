<?php

namespace NAttreid\Cms\Model\Locale;

use Nextras\Orm\Entity\Entity;

/**
 * Locale
 *
 * @property int $id {primary}
 * @property string $name
 * @property boolean $default {default false}
 * @property boolean $allowed {default true}
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
			$locale->default = false;
			$repo->persist($locale);
		}
		$this->default = true;
		$repo->persist($this);
		$repo->flush();
	}
}
