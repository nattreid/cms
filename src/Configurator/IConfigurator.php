<?php

namespace NAttreid\Crm\Configurator;

/**
 * Interface IConfigurator
 *
 * @property boolean $sendNewUserPassword zaslat novemu uzivateli heslo mailem
 * @property boolean $sendChangePassword zaslat uzivateli zmenene heslo mailem
 * @property boolean $dockbarAdvanced povolit rozsirene moznosti v dockbaru (mazani databaze, atd )
 * @property string $logo logo
 * @property string $defaultLocale nastaveni defaultniho jazyka
 * @property array $allowedLocales povolene jazyky
 * @property boolean $mailPanel Mail panel misto zasilani mailu
 *
 * @author Attreid <attreid@gmail.com>
 */
interface IConfigurator
{

	/**
	 * Prida vychozi hodnotu
	 * @param $property
	 * @param $value
	 */
	public function addDefault($property, $value);

	/**
	 * Smaze cache
	 */
	public function cleanCache();

	/**
	 * Vrati nastaveni
	 * @return array
	 */
	public function fetchConfigurations();

	/**
	 * Vrati jazyky
	 * @return array
	 */
	public function fetchLocales();
}
