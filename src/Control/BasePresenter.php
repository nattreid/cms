<?php

declare(strict_types=1);

namespace NAttreid\Cms\Control;

use IPub\FlashMessages\Components\Control;
use IPub\FlashMessages\Components\IControl;
use IPub\FlashMessages\Entities\IMessage;
use IPub\FlashMessages\Entities\Message;
use IPub\FlashMessages\FlashNotifier;
use IPub\FlashMessages\Storage\IStorage;
use Kdyby\Translation\Translator;
use NAttreid\Cms\Configurator\IConfigurator;
use NAttreid\Cms\Factories\DataGridFactory;
use NAttreid\Cms\Factories\FormFactory;
use NAttreid\Cms\Factories\LoaderFactory;
use NAttreid\Form\Control\ImageUpload\Preview;
use NAttreid\Form\Form;
use NAttreid\Form\Rules;
use NAttreid\Latte\TemplateTrait;
use NAttreid\Security\Control\ITryUserFactory;
use NAttreid\Security\Control\TryUser;
use NAttreid\Security\User;
use NAttreid\Utils\Date;
use NAttreid\Utils\Number;
use Nette\Application\AbortException;
use Nette\Application\UI\Presenter;
use Nette\Forms\Controls\CsrfProtection;
use Nette\Forms\Controls\SelectBox;
use Nette\Forms\Controls\UploadControl;
use Nette\Forms\Validator;
use WebLoader\InvalidArgumentException;
use WebLoader\Nette\CssLoader;
use WebLoader\Nette\JavaScriptLoader;

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

	use TemplateTrait;

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
	protected function getNamespace(): string
	{
		return $this->namespace;
	}

	/**
	 * Vrati nazev modulu CMS
	 * @return string
	 */
	protected function getModule(): string
	{
		return $this->module;
	}

	/**
	 * @throws AbortException
	 */
	protected function startup(): void
	{
		parent::startup();

		$this['tryUser']->init();

		// lokalizace
		$this->initLocale();

		$this->template->configurator = $this->configurator;
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
			Rules::IMAGE => 'form.imageAndSvg',
			Preview::DELETE => 'form.delete',
		];
	}

	/**
	 * Nastavi modul
	 * @param string $module
	 * @param string $namespace
	 */
	public function setModule(string $module, string $namespace): void
	{
		$this->module = $module;
		$this->namespace = $namespace;
	}

	/**
	 * @param $element
	 * @throws \Nette\Application\ForbiddenRequestException
	 */
	public function checkRequirements($element): void
	{
		$this->user->setNamespace($this->namespace);
		parent::checkRequirements($element);
	}

	protected function beforeRender(): void
	{
		parent::beforeRender();
		$this->redrawFlashMessages();
	}

	/**
	 * Presmerovani ajaxoveho pozadavku
	 * @param string $destination
	 * @param array $args
	 * @throws AbortException
	 * @throws \Nette\Application\UI\InvalidLinkException
	 */
	public function ajaxRedirect(string $destination, array $args = []): void
	{
		$this->payload->forceRedirect = $this->link($destination, $args);
		$this->sendPayload();
	}

	/* ###################################################################### */
	/*                               Configurator                             */

	/** @var IConfigurator */
	protected $configurator;

	public function injectConfigurator(IConfigurator $configurator): void
	{
		$this->configurator = $configurator;
	}

	/* ###################################################################### */
	/*                                TryUser                                 */

	/** @var ITryUserFactory */
	private $tryUserFactory;

	public function injectTryUserFactory(ITryUserFactory $tryUserFactory): void
	{
		$this->tryUserFactory = $tryUserFactory;
	}

	/**
	 * Prihlaseni za uzivatele
	 * @return TryUser
	 */
	protected function createComponentTryUser(): TryUser
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

	public function injectTranslator(Translator $translator): void
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
	protected function translate(string $message, int $count = null, array $parameters = [], string $domain = null, string $locale = null)
	{
		return $this->translator->translate($message, $count, $parameters, $domain, $locale);
	}

	/**
	 * @throws AbortException
	 */
	private function initLocale(): void
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
	/*                             FlashMessages                              */

	/** @var IControl */
	private $flashMessagesFactory;

	/** @var FlashNotifier */
	protected $flashNotifier;

	/** @var IStorage */
	private $flashStorage;

	/**
	 * @param IControl $flashMessagesFactory
	 * @param FlashNotifier $flashNotifier
	 * @param IStorage $flashStorage
	 */
	public function injectFlashMessages(IControl $flashMessagesFactory, FlashNotifier $flashNotifier, IStorage $flashStorage): void
	{
		$this->flashMessagesFactory = $flashMessagesFactory;
		$this->flashNotifier = $flashNotifier;
		$this->flashStorage = $flashStorage;
	}

	/**
	 * Store flash message
	 *
	 * @param string $message
	 * @param string $level
	 * @param string|null $title
	 * @param bool $overlay
	 * @param int|null $count
	 * @param array|null $parameters
	 *
	 * @return IMessage
	 */
	public function flashMessage($message, $level = 'info', $title = null, $overlay = false, $count = null, $parameters = []): IMessage
	{
		return $this->flashNotifier->message($message, $level, $title, $overlay, $count, $parameters);
	}

	/**
	 * Flash messages component
	 *
	 * @return Control
	 */
	protected function createComponentFlashMessages(): Control
	{
		return $this->flashMessagesFactory->create('bootstrap');
	}

	private function redrawFlashMessages(): void
	{
		if ($this->isAjax()) {
			/* @var $messages Message[] */
			$messages = $this->flashStorage->get(IStorage::KEY_MESSAGES, []);
			foreach ($messages as $message) {
				if (!$message->isDisplayed()) {
					$this['flashMessages']->redrawControl();
					break;
				}
			}
		}
	}

	/* ###################################################################### */
	/*                             LoaderFactory                              */

	/** @var LoaderFactory */
	private $loaderFactory;

	public function injectLoaderFactory(LoaderFactory $loaderFactory): void
	{
		$this->loaderFactory = $loaderFactory;
	}

	/** @return CssLoader
	 * @throws InvalidArgumentException
	 */
	protected function createComponentLoadCss(): CssLoader
	{
		return $this->loaderFactory->createCssLoader();
	}

	/** @return JavaScriptLoader
	 * @throws InvalidArgumentException
	 */
	protected function createComponentLoadJs(): JavaScriptLoader
	{
		return $this->loaderFactory->createJavaScriptLoader($this->locale);
	}

	/* ###################################################################### */
	/*                                  Form                                  */

	/** @var FormFactory */
	protected $formFactory;

	public function injectFormFactory(FormFactory $formFactory): void
	{
		$this->formFactory = $formFactory;
	}

	/* ###################################################################### */
	/*                                DataGrid                                  */

	/** @var DataGridFactory */
	protected $dataGridFactory;

	public function injectDataGridFactory(DataGridFactory $dataGridFactory): void
	{
		$this->dataGridFactory = $dataGridFactory;
	}

}
