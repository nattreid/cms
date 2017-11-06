<?php

declare(strict_types=1);

namespace NAttreid\Cms\DI;

use Kdyby\Translation\Translator;
use Nette\DI\MissingServiceException;
use Nette\DI\Statement;

trait ExtensionTranslatorTrait
{
	private function setTranslation(string $dir, array $domains): void
	{
		$builder = $this->getContainerBuilder();
		try {
			$translator = $builder->getByType(Translator::class);
			$def = $builder->getDefinition($translator);
			$setup = [];
			$languages = [
				'cs_CZ',
				'en_US',
				'de_DE',
				'pl_PL',
				'sk_SK'
			];
			foreach ($domains as $domain) {
				foreach ($languages as $lang) {
					$file = "$dir/$domain.$lang.neon";
					if (file_exists($file)) {
						$setup[] = new Statement('addResource', ['neon', $file, $lang, $domain]);
					}
				}
			}
			$def->setSetup(array_merge($def->getSetup(), $setup));
		} catch (MissingServiceException $ex) {
			throw new MissingServiceException("Missing extension 'kdyby/translation'");
		}
	}
}