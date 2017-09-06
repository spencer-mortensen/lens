<?php

namespace Lens\Engine;

class CoverageExtractor
{
	/** @var boolean */
	private $isCoverageEnabled;

	/** @var string */
	private $srcDirectory;

	/** @var null|array */
	private $coverage;

	public function __construct($srcDirectory)
	{
		$this->isCoverageEnabled = function_exists('xdebug_start_code_coverage');
		$this->srcDirectory = $srcDirectory;
	}

	public function start()
	{
		if (!$this->isCoverageEnabled) {
			return;
		}

		xdebug_start_code_coverage();
	}

	public function stop()
	{
		if (!$this->isCoverageEnabled) {
			return;
		}

		$coverage = xdebug_get_code_coverage();
		xdebug_stop_code_coverage();

		$this->coverage = $this->getCleanCoverage($coverage);
	}

	public function getCoverage()
	{
		return $this->coverage;
	}

	private function getCleanCoverage(array $coverage)
	{
		$output = array();

		foreach ($coverage as $path => $lines) {
			if (!$this->isRelevant($path)) {
				continue;
			}

			$output[$path] = self::getCleanLines($lines);
		}

		return $output;
	}

	private function isRelevant(&$path)
	{
		return $this->isSourceFile($path) && !self::isEvaluated($path);
	}

	private function isSourceFile(&$path)
	{
		$prefix = $this->srcDirectory . '/';
		$prefixLength = strlen($prefix);

		if (strncmp($path, $prefix, $prefixLength) !== 0) {
			return false;
		}

		$path = substr($path, $prefixLength);
		return true;
	}

	private static function isEvaluated($path)
	{
		$pattern = self::getPattern('\\([0-9]+\\) : eval\\(\\)\'d code$');

		return preg_match($pattern, $path) === 1;
	}

	private static function getPattern($expression, $flags = '')
	{
		$delimiter = "\x03";

		return "{$delimiter}{$expression}{$delimiter}{$flags}";
	}

	private static function getCleanLines(array $input)
	{
		$output = array();

		foreach ($input as $lineNumber => $status) {
			$output[] = $lineNumber - 1;
		}

		return $output;
	}
}
