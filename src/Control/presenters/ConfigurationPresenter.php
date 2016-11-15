<?php

namespace NAttreid\Crm\Control;

use NAttreid\Crm\LocaleService;
use NAttreid\Form\Form;
use NAttreid\TracyPlugin\Tracy;
use Nette\Utils\ArrayHash;

/**
 * Konfigurace webu
 *
 * @author Attreid <attreid@gmail.com>
 */
class ConfigurationPresenter extends CrmPresenter
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

	public function handleDebug($on)
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
	protected function createComponentConfigurationForm()
	{
		$form = $this->formFactory->create();
		$form->setAjaxRequest();

		$form->addGroup('crm.settings.basic');
		$form->addImageUpload('crmLogo', 'crm.settings.logo', 'crm.settings.deleteLogo')
			->setNamespace('crm')
			->setPreview('300x100');
		$form->addCheckbox('sendNewUserPassword', 'crm.settings.sendNewUserPassword');
		$form->addCheckbox('sendChangePassword', 'crm.settings.sendChangePassword');
		$form->addCheckbox('dockbarAdvanced', 'crm.settings.dockbarAdvanced');
		$form->addSelectUntranslated('defaultLocale', 'crm.settings.defaultLocale', $this->localeService->fetch())
			->setDefaultValue($this->localeService->getDefaultLocaleId());
		$form->addMultiSelectUntranslated('allowedLocales', 'crm.settings.allowedLocales', $this->localeService->fetch())
			->setDefaultValue($this->localeService->getAllowedLocalesId())
			->setRequired();

		$form->addGroup('crm.settings.development');
		if (!$this->tracy->isEnabled()) {
			$form->addLink('debugOn', 'crm.settings.debugOn')
				->link($this->link('debug!', true))
				->setAjaxRequest()
				->addClass('btn-success')
				->setAttribute('data-ajax-off', 'history');
		} else {
			$form->addLink('debugOff', 'crm.settings.debugOff')
				->link($this->link('debug!', false))
				->setAjaxRequest()
				->addClass('btn-danger')
				->setAttribute('data-ajax-off', 'history');
		}
		$form->addCheckbox('mailPanel', 'crm.settings.mailPanel')
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
	public function configurationFormSucseeded(Form $form, $values)
	{
		$this->localeService->default = $values->defaultLocale;
		$this->localeService->allowed = $values->allowedLocales;
		unset($values->defaultLocale, $values->allowedLocales);

		foreach ($values as $name => $value) {
			$this->configurator->$name = $value;
		}
		$this->flashNotifier->success('crm.settings.settingsSaved');

		if ($this->isAjax()) {
			$this->redrawControl('configuration');
		} else {
			$this->redirect('default');
		}
	}

}
