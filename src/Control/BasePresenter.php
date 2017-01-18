<?php

namespace NAttreid\Cms\Control;

use IPub\FlashMessages\TFlashMessages;
use Kdyby\Translation\Translator;
use NAttreid\Cms\Configurator\IConfigurator;
use NAttreid\Cms\Factories\DataGridFactory;
use NAttreid\Cms\Factories\FormFactory;
use NAttreid\Cms\LoaderFactory;
use NAttreid\Form\Form;
use NAttreid\Form\Rules;
use NAttreid\Latte\TemplateTrait;
use NAttreid\Security\Control\ITryUserFactory;
use NAttreid\Security\Control\TryUser;
use NAttreid\Security\User;
use NAttreid\Utils\Date;
use NAttreid\Utils\Number;
use Nette\Application\UI\Presenter;
use Nette\Forms\Controls\CsrfProtection;
use Nette\Forms\Controls\SelectBox;
use Nette\Forms\Controls\UploadControl;
use Nette\Forms\Validator;

/**
 * Zakladni presenter pro CMS
 *
 * @property-read string $namespace
 * @property-read string $module
 * @property-read User $user
 *
 * @persistent(tryUser)
 *
 * @author Attreid <attreid@gmail.com>
 */
abstract class BasePresenter extends Presenter
{

	use TFlashMessages,
		TemplateTrait;

	/**
	 * Namespace pro CMS
	 * @var string
	 */
	private $namespace;

	/**
	 * Nazev modulu CMS
	 * @var string
	 */
	private $module;

	/**
	 * Vrati namespace pro CMS
	 * @return string
	 */
	protected function getNamespace()
	{
		return $this->namespace;
	}

	/**
	 * Vrati nazev modulu CMS
	 * @return string
	 */
	protected function getModule()
	{
		return $this->module;
	}

	protected function startup()
	{
		parent::startup();

		$this['tryUser']->init();

		// lokalizace
		$this->initLocale();

		$this->template->baseTitle = $this->configurator->title;
		$this->template->layout = __DIR__ . '/presenters/templates/@layout.latte';

		// Prelozeni defaultnich zprav pro pravidla formulare
		Validator::$messages = [
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
			Rules::PHONE => 'form.phone',
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

	public function checkRequirements($element)
	{
		$this->user->setNamespace($this->namespace);
		parent::checkRequirements($element);
	}

	/* ###################################################################### */
	/*                               Configurator                             */

	/** @var IConfigurator */
	protected $configurator;

	public function injectConfigurator(IConfigurator $configurator)
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
		$control = $this->tryUserFactory->create(":{$this->module}:Homepage:");
		$control->permission = 'dockbar.settings.users.tryUser';
		return $control;
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
	protected function translate($message, $count = null, array $parameters = [], $domain = null, $locale = null)
	{
		return $this->translator->translate($message, $count, $parameters, $domain, $locale);
	}

	private function initLocale()
	{
		if (empty($this->locale)) {
			$this->locale = $this->translator->getDefaultLocale();
		}

		if ($this->user->loggedIn) {
			$locale = $this->user->identity->language;
			if ($locale !== null && $locale != $this->locale) {
				$this->redirect('this', ['locale' => $locale]);
			}
		}

		Number::setLocale($this->locale);
		Date::setLocale($this->locale);
		$this->template->locale = $this->locale;
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
