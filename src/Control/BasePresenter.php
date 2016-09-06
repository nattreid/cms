<?php

namespace NAttreid\Crm\Control;

use Kdyby\Translation\Translator;
use NAttreid\Crm\Configurator;
use NAttreid\Crm\Factories\DataGridFactory;
use NAttreid\Crm\Factories\FormFactory;
use NAttreid\Crm\LoaderFactory;
use NAttreid\Form\Form;
use NAttreid\Security\Control\ITryUserFactory;
use NAttreid\Security\Control\TryUser;
use NAttreid\Utils\Date;
use NAttreid\Utils\Number;
use Nette\Forms\Controls\CsrfProtection;
use Nette\Forms\Controls\SelectBox;
use Nette\Forms\Controls\UploadControl;

/**
 * Zakladni presenter pro CRM
 *
 * @property-read string $namespace
 * @property-read string $module
 * @property-read string $crmModule
 *
 * @author Attreid <attreid@gmail.com>
 */
abstract class BasePresenter extends \Nette\Application\UI\Presenter
{

	use \IPub\FlashMessages\TFlashMessages,
		\NAttreid\Latte\TemplateTrait;

	/**
	 * Namespace pro crm
	 * @var string
	 */
	private $namespace;

	/**
	 * Nazev modulu crm
	 * @var string
	 */
	private $module;

	/**
	 * Modul v Crm
	 * @var string
	 */
	private $crmModule;

	/**
	 * Vrati namespace pro crm
	 * @return string
	 */
	protected function getNamespace()
	{
		return $this->namespace;
	}

	/**
	 * Vrati nazev modulu crm
	 * @return string
	 */
	protected function getModule()
	{
		return $this->module;
	}

	/**
	 * Vrati modul v Crm
	 * @return string
	 */
	protected function getCrmModule()
	{
		return $this->crmModule;
	}

	protected function startup()
	{
		parent::startup();

		$this['tryUser']->init();

		// lokalizace
		if (empty($this->locale)) {
			$this->locale = $this->translator->getDefaultLocale();
		}
		Number::setLocale($this->locale);
		Date::setLocale($this->locale);
		$this->template->locale = $this->locale;

		$this->template->baseTitle = $this->configurator->title;
		$this->template->layout = __DIR__ . '/presenters/templates/@layout.latte';

		// Prelozeni defaultnich zprav pro pravidla formulare
		\Nette\Forms\Validator::$messages = [
			CsrfProtection::PROTECTION => 'form.protection',
			Form::EQUAL => 'form.equal',
			Form::NOT_EQUAL => 'form.notEqual',
			Form::FILLED => 'form.filled',
			Form::BLANK => 'form.blank',
			Form::MIN_LENGTH => 'form.minLength',
			Form::MAX_LENGTH => 'form.maxLength',
			Form::LENGTH => 'form.length',
			Form::EMAIL => 'form.email',
			Form::URL => 'form.url',
			Form::INTEGER => 'form.integer',
			Form::FLOAT => 'form.float',
			Form::MIN => 'form.min',
			Form::MAX => 'form.max',
			Form::RANGE => 'form.range',
			Form::MAX_FILE_SIZE => 'form.max_file_size',
			Form::MAX_POST_SIZE => 'form.max_post_size',
			Form::MIME_TYPE => 'form.mime_type',
			Form::IMAGE => 'form.image',
			SelectBox::VALID => 'form.option',
			UploadControl::VALID => 'form.fileUpload',
		];
	}

	/**
	 * Nastavi modul
	 * @param string $module
	 * @param string $namespace
	 */
	public function setModule($module, $namespace)
	{
		$this->module = $module;
		$this->namespace = $namespace;
	}

	/**
	 * Nastavi modul v CRM
	 * @param string $crmModule
	 */
	public function setCrmModule($crmModule)
	{
		$this->crmModule = $crmModule;
	}

	public function checkRequirements($element)
	{
		$this->user->setNamespace($this->namespace);
		parent::checkRequirements($element);
	}

	/* ###################################################################### */
	/*                               Configurator                             */

	/** @var Configurator */
	protected $configurator;

	public function injectConfigurator(Configurator $configurator)
	{
		$this->configurator = $configurator;
	}

	/* ###################################################################### */
	/*                                TryUser                                 */

	/** @var ITryUserFactory */
	private $tryUserFactory;

	public function injectTryUserFactory(ITryUserFactory $tryUserFactory)
	{
		$this->tryUserFactory = $tryUserFactory;
	}

	/**
	 * Prihlaseni za uzivatele
	 * @return TryUser
	 */
	protected function createComponentTryUser()
	{
		return $this->tryUserFactory->create(":{$this->module}:Homepage:");
	}

	/**
	 * Vrati komponentu Try User
	 * @return TryUser
	 */
	public function getTryUser()
	{
		return $this['tryUser'];
	}

	/* ###################################################################### */
	/*                               Translator                               */

	/** @persistent */
	public $locale;

	/** @var Translator */
	protected $translator;

	public function injectTranslator(Translator $translator)
	{
		$this->translator = $translator;
	}

	/**
	 * Translates the given string.
	 *
	 * @param string $message The message id
	 * @param integer $count The number to use to find the indice of the message
	 * @param array $parameters An array of parameters for the message
	 * @param string $domain The domain for the message
	 * @param string $locale The locale
	 *
	 * @return string
	 */
	protected function translate($message, $count = NULL, array $parameters = [], $domain = NULL, $locale = NULL)
	{
		return $this->translator->translate($message, $count, $parameters, $domain, $locale);
	}

	/* ###################################################################### */
	/*                             LoaderFactory                              */

	/** @var LoaderFactory */
	private $loaderFactory;

	public function injectLoaderFactory(LoaderFactory $loaderFactory)
	{
		$this->loaderFactory = $loaderFactory;
	}

	/** @return \WebLoader\Nette\CssLoader */
	protected function createComponentLoadCss()
	{
		return $this->loaderFactory->createCssLoader();
	}

	/** @return \WebLoader\Nette\JavaScriptLoader */
	protected function createComponentLoadJs()
	{
		return $this->loaderFactory->createJavaScriptLoader($this->locale);
	}

	/* ###################################################################### */
	/*                                  Form                                  */

	/** @var FormFactory */
	protected $formFactory;

	public function injectFormFactory(FormFactory $formFactory)
	{
		$this->formFactory = $formFactory;
	}

	/* ###################################################################### */
	/*                                DataGrid                                  */

	/** @var DataGridFactory */
	protected $dataGridFactory;

	public function injectDataGridFactory(DataGridFactory $dataGridFactory)
	{
		$this->dataGridFactory = $dataGridFactory;
	}

}
