<?php

declare(strict_types=1);

namespace NAttreid\Cms\Factories;

use InvalidArgumentException;
use Nette\Http\IRequest;
use Nette\SmartObject;
use WebLoader\Compiler;
use WebLoader\FileCollection;
use WebLoader\Nette\CssLoader;
use WebLoader\Nette\JavaScriptLoader;

/**
 * CSS, JS Loader Factory
 *
 * @author Attreid <attreid@gmail.com>
 */
class LoaderFactory
{
	use SmartObject;

	const
		JS = 'js',
		CSS = 'css';

	/** @var string * */
	private $wwwDir;

	/** @var IRequest * */
	private $httpRequest;

	/** @var string * */
	private $outputDir = 'webtemp';

	/** @var string * */
	private $root = __DIR__ . '/../../assets';

	/** @var FileCollection[][] */
	private $files;

	/** @var string[][] */
	private $filters = [];

	function __construct(string $wwwDir, array $jsFilters, array $cssFilters, IRequest $httpRequest, \WebLoader\Nette\LoaderFactory $loader = null)
	{
		$this->wwwDir = $wwwDir;
		$this->filters[self::JS] = $jsFilters;
		$this->filters[self::CSS] = $cssFilters;
		$this->httpRequest = $httpRequest;
		if ($loader !== null) {
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
	public function addFile(string $file, string $locale = null): self
	{
		$collection = $this->getCollection($this->getType($file), $locale);
		if ($this->isRemoteFile($file)) {
			$collection->addRemoteFile($file);
		} else {
			$collection->addFile($file);
		}
		return $this;
	}

	/**
	 * Prida remote soubor
	 * @param string $file
	 * @param string $locale
	 * @return self
	 */
	public function addRemoteFile(string $file, string $locale = null): self
	{
		$collection = $this->getCollection($this->getType($file), $locale);
		$collection->addRemoteFile($file);
		return $this;
	}

	/**
	 * Vytvori komponentu css
	 * @return CssLoader
	 */
	public function createCssLoader(): CssLoader
	{
		$compiler = Compiler::createCssCompiler($this->files[self::CSS][null], $this->wwwDir . '/' . $this->outputDir);
		foreach ($this->filters[self::CSS] as $filter) {
			$compiler->addFileFilter($filter);
		}
		return new CssLoader($compiler, $this->httpRequest->getUrl()->basePath . $this->outputDir);
	}

	/**
	 * Vytvori komponentu js
	 * @param string $locale
	 * @return JavaScriptLoader
	 */
	public function createJavaScriptLoader(string $locale = null): JavaScriptLoader
	{
		$compilers[] = $this->createJSCompiler($this->files[self::JS][null]);
		if ($locale !== null && isset($this->files[self::JS][$locale])) {
			$compilers[] = $this->createJSCompiler($this->files[self::JS][$locale]);
		}

		return new JavaScriptLoader($this->httpRequest->getUrl()->basePath . $this->outputDir, ...$compilers);
	}

	/**
	 * @param FileCollection $collection
	 * @return Compiler
	 */
	private function createJSCompiler(FileCollection $collection): Compiler
	{
		$compiler = Compiler::createJsCompiler($collection, $this->wwwDir . '/' . $this->outputDir);
		$compiler->setAsync(true);
		foreach ($this->filters[self::JS] as $filter) {
			$compiler->addFileFilter($filter);
		}
		return $compiler;
	}

	/**
	 * Je vzdaleny soubor
	 * @param string $file
	 * @return bool
	 */
	private function isRemoteFile(string $file): bool
	{
		return (filter_var($file, FILTER_VALIDATE_URL) or strpos($file, '//') === 0);
	}

	/**
	 * Vrati kolekci souboru
	 * @param string $type
	 * @param string $locale
	 * @return FileCollection
	 */
	private function getCollection(string $type, string $locale = null): FileCollection
	{
		if (!isset($this->files[$type][$locale])) {
			$this->files[$type][$locale] = new FileCollection($this->root);
		}
		return $this->files[$type][$locale];
	}

	/**
	 * Vrati typ souboru
	 * @param string $file
	 * @return string
	 */
	private function getType(string $file): string
	{
		$css = '/\.(css|less)$/';
		$js = '/\.js$/';

		if (preg_match($css, $file)) {
			return self::CSS;
		} elseif (preg_match($js, $file)) {
			return self::JS;
		}
		throw new InvalidArgumentException("Unknown assets file '$file'");
	}

}
