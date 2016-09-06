<?php

namespace NAttreid\Crm;

use Nette\Http\IRequest;
use WebLoader\Compiler;
use WebLoader\FileCollection;
use WebLoader\Nette\CssLoader;
use WebLoader\Nette\JavaScriptLoader;

/**
 */
class LoaderFactory
{

	use \Nette\SmartObject;

	/** @var string * */
	private $wwwDir;

	/** @var IRequest * */
	private $httpRequest;

	/** @var string * */
	private $outputDir = 'webtemp';

	/** @var string * */
	private $root = __DIR__ . '/../assets';

	/** @var array */
	private $files = [];

	/** @var array */
	private $filesLocale, $jsFilters, $cssFilters = [];

	function __construct($wwwDir, $jsFilters, $cssFilters, IRequest $httpRequest, \WebLoader\Nette\LoaderFactory $loader = NULL)
	{
		$this->wwwDir = $wwwDir;
		$this->jsFilters = $jsFilters;
		$this->cssFilters = $cssFilters;
		$this->httpRequest = $httpRequest;
		if ($loader !== NULL) {
			foreach ($loader->getTempPaths() as $path) {
				$this->outputDir = $path;
				break;
			}
		}
	}

	/**
	 * Prida soubor
	 * @param string $file
	 * @param string $locale
	 * @return self
	 */
	public function addFile($file, $locale = NULL)
	{
		if ($locale !== NULL) {
			if (!isset($this->filesLocale[$locale])) {
				$this->filesLocale[$locale] = [];
			}
			$this->filesLocale[$locale][] = $file;
		} else {
			$this->files[$file] = $file;
		}
		return $this;
	}

	/**
	 * Odebere soubor
	 * @param string $file
	 * @param string $locale
	 * @return self
	 */
	public function removeFile($file, $locale = NULL)
	{
		if ($locale !== NULL) {
			unset($this->filesLocale[$locale][$file]);
		} else {
			unset($this->files[$file]);
		}
		return $this;
	}

	/**
	 * Vytvori komponentu css
	 * @return CssLoader
	 */
	public function createCssLoader()
	{
		$fileCollection = $this->createFileCollection(array_filter($this->files, [$this, 'isCss']));
		$compiler = Compiler::createCssCompiler($fileCollection, $this->wwwDir . '/' . $this->outputDir);
		foreach ($this->cssFilters as $filter) {
			$compiler->addFileFilter($filter);
		}
		return new CssLoader($compiler, $this->httpRequest->url->basePath . $this->outputDir);
	}

	/**
	 * Vytvori komponentu js
	 * @param string $locale
	 * @return JavaScriptLoader
	 */
	public function createJavaScriptLoader($locale = NULL)
	{
		$fileCollection = $this->createFileCollection(array_filter($this->files, [$this, 'isJs']));
		$compiler = Compiler::createJsCompiler($fileCollection, $this->wwwDir . '/' . $this->outputDir);
		$compiler->setAsync(TRUE);
		foreach ($this->jsFilters as $filter) {
			$compiler->addFileFilter($filter);
		}
		$compilers = [$compiler];
		if ($locale !== NULL) {
			if (isset($this->filesLocale[$locale])) {
				$fileCollection = $this->createFileCollection(array_filter($this->filesLocale[$locale], [$this, 'isJs']));
				$compilers[] = Compiler::createJsCompiler($fileCollection, $this->wwwDir . '/' . $this->outputDir);
			}
		}

		return new JavaScriptLoader($this->httpRequest->url->basePath . $this->outputDir, ...$compilers);
	}

	/**
	 * Vytvori kolekci
	 * @param array $files
	 * @return FileCollection
	 */
	private function createFileCollection(array $files)
	{
		$fileCollection = new FileCollection($this->root);
		foreach ($files as $file) {
			if ($this->isRemoteFile($file)) {
				$fileCollection->addRemoteFile($file);
			} else {
				$fileCollection->addFile($file);
			}
		}
		return $fileCollection;
	}

	/**
	 * Je vzdaleny soubor
	 * @param string $file
	 * @return boolean
	 */
	private function isRemoteFile($file)
	{
		return (filter_var($file, FILTER_VALIDATE_URL) or strpos($file, '//') === 0);
	}

	/**
	 * Je soubor css
	 * @param string $file
	 * @return boolean
	 */
	private function isCss($file)
	{
		return preg_match('~\.(css|less)$~', $file);
	}

	/**
	 * Je soubor js
	 * @param string $file
	 * @return boolean
	 */
	private function isJs($file)
	{
		return preg_match('~\.js$~', $file);
	}

}
