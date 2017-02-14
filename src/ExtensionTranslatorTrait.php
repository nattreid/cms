<?php

namespace NAttreid\Cms;

use Kdyby\Translation\Translator;
use Nette\DI\MissingServiceException;
use Nette\DI\Statement;

trait ExtensionTranslatorTrait
{
	private function setTranslation($dir, array $domains)
	{
		$builder = $this->getContainerBuilder();
		try {
			$translator = $builder->getByType(Translator::class);
			$def = $builder->getDefinition($translator);
			$setup = [];
			$languages = [
				'cs_CZ',
				'en_US'
			];
			foreach ($domains as $domain) {
				foreach ($languages as $lang) {
					$setup[] = new Statement('addResource', ['neon', "$dir/$domain.$lang.neon", $lang, $domain]);
				}
			}
			$def->setSetup(array_merge($def->getSetup(), $setup));
		} catch (MissingServiceException $ex) {
			throw new MissingServiceException("Missing extension 'kdyby/translation'");
		}
	}
}