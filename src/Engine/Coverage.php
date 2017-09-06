<?php

namespace Lens\Engine;

use Lens\Filesystem;

class Coverage
{
	/** @var Filesystem */
	private $filesystem;

	/** @var string */
	private $code;

	public function __construct()
	{
		// TODO: dependency injection:
		$this->filesystem = new Filesystem();
	}

	public function run($srcDirectory, array $relativePaths)
	{
		$results = $this->getCodeCoverage($srcDirectory, $relativePaths);

		echo serialize($results);
	}

	private function getCodeCoverage($srcDirectory, array $relativePaths)
	{
		if (!function_exists('xdebug_start_code_coverage')) {
			return null;
		}

		$code = $this->getCode($srcDirectory, $relativePaths);
		$rawCoverage = $this->getRawCoverage($srcDirectory, $relativePaths);
		$coverage = self::getCleanCoverage($srcDirectory, $code, $rawCoverage);

		return array($code, $coverage);
	}

	private function getCode($srcDirectory, array $relativePaths)
	{
		$code = array();

		foreach ($relativePaths as $relativePath) {
			$absolutePath = "{$srcDirectory}/{$relativePath}";
			$contents = $this->filesystem->read($absolutePath);
			$code[$relativePath] = self::getLines($contents);
		}

		return $code;
	}

	private static function getLines($text)
	{
		$pattern = self::getPattern('\\r?\\n');

		return preg_split($pattern, $text);
	}

	private function getRawCoverage($srcDirectory, array $relativePaths)
	{
		$absolutePaths = self::getAbsolutePaths($srcDirectory, $relativePaths);
		$statements = self::getRequireStatements($absolutePaths);
		$this->code = implode("\n", $statements);

		ini_set('display_errors', 'Off');
		// TODO: handle a fatal error caused by requiring a file:
		// register_shutdown_function($callable);
		xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);

		$this->evaluate();

		$coverage = xdebug_get_code_coverage();
		xdebug_stop_code_coverage();

		return $coverage;
	}

	private static function getAbsolutePaths($baseDirectory, array $relativePaths)
	{
		$absolutePaths = array();

		foreach ($relativePaths as $relativePath) {
			$absolutePaths[] = "{$baseDirectory}/{$relativePath}";
		}

		return $absolutePaths;
	}

	private static function getRequireStatements(array $paths)
	{
		$statements = array();

		foreach ($paths as $path) {
			$pathString = var_export($path, true);
			$statements[] = "require {$pathString};";
		}

		return $statements;
	}

	private function evaluate()
	{
		eval($this->code);
	}

	private static function getCleanCoverage($srcDirectory, array $code, array $coverage)
	{
		$output = array();

		foreach ($code as $relativePath => $fileCode) {
			$absolutePath = "{$srcDirectory}/{$relativePath}";
			$fileCoverage = &$coverage[$absolutePath];

			if (isset($fileCoverage)) {
				$output[$relativePath] = self::getCleanFileCoverage($fileCoverage, $fileCode);
			}
		}

		return $output;
	}

	private static function getCleanFileCoverage(array $coverage, array $code)
	{
		$output = array();

		foreach ($coverage as $lineNumber => $lineCoverage) {
			$lineCode = &$code[--$lineNumber];

			if (self::isTestableCode($lineCode)) {
				$output[] = $lineNumber;
			}
		}

		return $output;
	}

	private static function isTestableCode($text)
	{
		$text = trim($text);

		if (strlen(trim($text, '{}')) === 0) {
			return false;
		}

		if (substr($text, 0, 6) === 'class ') {
			return false;
		}

		return true;
	}

	private static function getPattern($expression, $flags = '')
	{
		$delimiter = "\x03";

		return "{$delimiter}{$expression}{$delimiter}{$flags}";
	}
}
