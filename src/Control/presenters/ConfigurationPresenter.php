<?php

declare(strict_types=1);

namespace NAttreid\Cms\Control;

use NAttreid\Cms\LocaleService;
use NAttreid\Form\Form;
use NAttreid\TracyPlugin\Tracy;
use Nette\Utils\ArrayHash;

/**
 * Konfigurace webu
 *
 * @author Attreid <attreid@gmail.com>
 */
class ConfigurationPresenter extends CmsPresenter
{

	/** @var Tracy */
	private $tracy;

	/** @var LocaleService */
	private $localeService;

	public function __construct(Tracy $tracy, LocaleService $localeService)
	{
		parent::__construct();
		$this->tracy = $tracy;
		$this->localeService = $localeService;
	}

	/**
	 * Zobrazeni nastaveni
	 */
	public function renderDefault()
	{
		$this->addBreadcrumbLink('dockbar.settings.configuration');
		$this['configurationForm']->setDefaults($this->configurator->fetchConfigurations());
	}

	public function handleDebug(bool $on)
	{
		if ($this->isAjax()) {
			if ($on) {
				$this->tracy->enable();
			} else {
				$this->tracy->disable();
			}
			$this->redrawControl('configuration');
		} else {
			$this->terminate();
		}
	}

	/**
	 * Komponenta formulare nastaveni
	 * @return Form
	 */
	protected function createComponentConfigurationForm(): Form
	{
		$form = $this->formFactory->create();
		$form->setAjaxRequest();

		$form->addGroup('cms.settings.basic');
		$form->addImageUpload('cmsLogo', 'cms.settings.logo', 'cms.settings.deleteLogo')
			->setNamespace('cms')
			->setPreview('300x100');
		$form->addCheckbox('sendNewUserPassword', 'cms.settings.sendNewUserPassword');
		$form->addCheckbox('sendChangePassword', 'cms.settings.sendChangePassword');
		$form->addCheckbox('dockbarAdvanced', 'cms.settings.dockbarAdvanced');
		$form->addSelectUntranslated('defaultLocale', 'cms.settings.defaultLocale', $this->localeService->fetch())
			->setDefaultValue($this->localeService->defaultLocaleId);
		$form->addMultiSelectUntranslated('allowedLocales', 'cms.settings.allowedLocales', $this->localeService->fetch())
			->setDefaultValue($this->localeService->allowedLocaleIds)
			->setRequired();

		$form->addGroup('cms.settings.development');
		if (!$this->tracy->isEnabled()) {
			$form->addLink('debugOn', 'cms.settings.debugOn')
				->link($this->link('debug!', true))
				->setAjaxRequest()
				->addClass('btn-success')
				->setAttribute('data-ajax-off', 'history');
		} else {
			$form->addLink('debugOff', 'cms.settings.debugOff')
				->link($this->link('debug!', false))
				->setAjaxRequest()
				->addClass('btn-danger')
				->setAttribute('data-ajax-off', 'history');
		}
		$form->addCheckbox('mailPanel', 'cms.settings.mailPanel')
			->setDefaultValue($this->configurator->mailPanel);

		$form->getRenderer()->primaryButton = $form->addSubmit('save', 'form.save');

		$form->onSuccess[] = [$this, 'configurationFormSucseeded'];

		return $form;
	}

	/**
	 * Ulozeni nastaveni
	 * @param Form $form
	 * @param ArrayHash $values
	 */
	public function configurationFormSucseeded(Form $form, ArrayHash $values)
	{
		$this->localeService->default = $values->defaultLocale;
		$this->localeService->allowed = $values->allowedLocales;
		unset($values->defaultLocale, $values->allowedLocales);

		foreach ($values as $name => $value) {
			$this->configurator->$name = $value;
		}
		$this->flashNotifier->success('cms.settings.settingsSaved');

		if ($this->isAjax()) {
			$this->redrawControl('configuration');
		} else {
			$this->redirect('default');
		}
	}

}
