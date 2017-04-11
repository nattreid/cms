<?php

declare(strict_types=1);

namespace NAttreid\Cms\Configurator;

/**
 * Interface IConfigurator
 *
 * @property bool $sendNewUserPassword zaslat novemu uzivateli heslo mailem
 * @property bool $sendChangePassword zaslat uzivateli zmenene heslo mailem
 * @property bool $dockbarAdvanced povolit rozsirene moznosti v dockbaru (mazani databaze, atd )
 * @property string $cmsLogo logo CMS
 * @property string $title nazev stranek (napr Netta.cz)
 * @property bool $mailPanel Mail panel misto zasilani mailu
 *
 * @author Attreid <attreid@gmail.com>
 */
interface IConfigurator
{

	/**
	 * Prida vychozi hodnotu
	 * @param string $property
	 * @param mixed $value
	 */
	public function addDefault(string $property, $value);

	/**
	 * Smaze cache
	 */
	public function cleanCache();

	/**
	 * Vrati nastaveni
	 * @return array
	 */
	public function fetchConfigurations(): array;
}
