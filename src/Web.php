<?php

/**
 * Copyright (C) 2017 Spencer Mortensen
 *
 * This file is part of Lens.
 *
 * Lens is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Lens is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Lens. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Spencer Mortensen <spencer@lens.guide>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL-3.0
 * @copyright 2017 Spencer Mortensen
 */

namespace Lens;

use SpencerMortensen\Paths\Paths;

class Web
{
	/** @var Filesystem */
	private $filesystem;

	/** Paths */
	private $paths;

	/** @var string */
	private $codeDirectory;

	/** @var string */
	private $coverageDirectory;

	/** @var array */
	private $code;

	/** @var array */
	private $coverage;

	public function __construct(Filesystem $filesystem)
	{
		$this->filesystem = $filesystem;
		$this->paths = Paths::getPlatformPaths();
	}

	public function coverage($codeDirectory, $coverageDirectory, $code, $coverage)
	{
		if (!isset($codeDirectory, $coverageDirectory)) {
			// TODO: generate a notice?
			return;
		}

		$this->codeDirectory = $codeDirectory;
		$this->coverageDirectory = $coverageDirectory;

		if ($coverage === null) {
			$this->writeInstructions();
		} else {
			$this->writeCodeCoverage($code, $coverage);
		}
	}

	private function writeInstructions()
	{
		$this->filesystem->delete($this->coverageDirectory);
		$this->writeCssFiles('style.css', 'instructions.css');
		$this->writeInstructionsIndex();
	}
	private function writeInstructionsIndex()
	{
		$cssFiles = array(
			$this->paths->join('style', 'style.css'),
			$this->paths->join('style', 'instructions.css')
		);

		$menuHtml = null;
		$titleHtml = 'coverage';
		$bodyHtml = $this->getInstructionsHtml();

		$absoluteFilePath = $this->paths->join($this->coverageDirectory, 'index.html');
		$html = self::getHtml($cssFiles, $menuHtml, $titleHtml, $bodyHtml);

		$this->filesystem->write($absoluteFilePath, $html);
	}

	private function getInstructionsHtml()
	{
		$followupMessage = "When you’re finished making changes, run Lens again and refresh this page to see the results.";

		if (!extension_loaded('xdebug')) {
			$basicMessage = "You’ll be able to see your code coverage here after you’ve enabled the “xdebug” extension for PHP.";

			if ($this->isLinux($os, $version) && ($os === 'ubuntu')) {
				$package = self::getUbuntuPackage($version);
				$command = "sudo apt-get install {$package}";
				$commandHtml = self::getCodeHtml($command);

				return "<p>{$basicMessage} Here’s the command:</p>\n\n{$commandHtml}\n\n<p>{$followupMessage}</p>";
			}

			return "<p>{$basicMessage}</p>\n\n<p>{$followupMessage}</p>";
		}

		$xdebugInstalledVersion = phpversion('xdebug');
		$xdebugMinimumVersion = '2.2';

		if (!version_compare($xdebugInstalledVersion, $xdebugMinimumVersion, '>=')) {
			return "<p>You’ll be able to see your code coverage here after you’ve upgraded to xdebug {$xdebugMinimumVersion} or greater. {$followupMessage}</p>";
		}

		if (!ini_get('xdebug.coverage_enable')) {
			return "<p>You’ll be able to see your code coverage here after you’ve set your “xdebug.coverage_enable” property to “On” in your PHP configuration settings. {$followupMessage}</p>";
		}

		return "<p>You’ll be able to see your code coverage here after you’ve enabled the “xdebug” extension for PHP. {$followupMessage}</p>";
	}

	private function isLinux(&$os, &$version)
	{
		$osReleaseText = $this->filesystem->read('/etc/os-release');

		if ($osReleaseText === null) {
			return false;
		}

		if (preg_match('~^ID=(.*)$~m', $osReleaseText, $match) === 1) {
			$os = $match[1];
		}

		if (preg_match('~^VERSION_ID="(.*)"$~m', $osReleaseText, $match) === 1) {
			$version = $match[1];
		}

		return true;
	}

	private static function getUbuntuPackage($version)
	{
		if ((float)$version < 16.04) {
			return 'php5-xdebug';
		}

		return 'php-xdebug';
	}

	private static function getCodeHtml($code)
	{
		$codeHtml = self::html5TextEncode($code);

		return "<pre><code>{$codeHtml}</code></pre>";
	}

	private function writeCodeCoverage(array $code, array $coverage)
	{
		$this->code = $code;
		$this->coverage = $coverage;

		$filePaths = array_keys($this->code);
		$hierarchy = $this->getRelativeHierarchy($filePaths);

		$this->filesystem->delete($this->coverageDirectory);
		$this->writeCssFiles('style.css', 'directory.css', 'file.css', 'filesystem.png');
		$this->writeDirectory('coverage', $hierarchy, '');
	}

	private function getRelativeHierarchy($filePaths)
	{
		$files = array();

		foreach ($filePaths as $filePath) {
			$data = $this->paths->deserialize($filePath);
			$atoms = $data->getAtoms();

			$p = &$files;

			foreach ($atoms as $atom) {
				$p = &$p[$atom];
			}

			$p = null;
		}

		return $files;
	}

	private function writeDirectory($name, array $contents, $path, &$pass = null, &$fail = null)
	{
		$children = array();
		$pass = 0;
		$fail = 0;

		foreach ($contents as $childName => $childContents) {
			$isChildDirectory = is_array($childContents);
			$childPath = $this->paths->join($path, $childName);

			if ($isChildDirectory) {
				$this->writeDirectory($childName, $childContents, $childPath, $childPass, $childFail);
			} else {
				$this->writeFile($childName, $childPath, $childPass, $childFail);
			}

			$children[] = array($childName, $isChildDirectory, $childPass, $childFail);
			$pass += $childPass;
			$fail += $childFail;
		}

		$this->writeIndex($name, $path, $children);
	}

	private function writeIndex($name, $path, array $children)
	{
		$filePath = $this->paths->join($this->coverageDirectory, $path, 'index.html');

		$data = $this->paths->deserialize($path);
		$atoms = $data->getAtoms();
		$depth = count($atoms);

		$cssFiles = $this->getCssFilePaths($depth, array('style.css', 'directory.css'));

		$urls = $this->getMenuUrls($path, 1);
		$menuHtml = self::getMenuHtml($urls);

		$titleHtml = self::html5TextEncode($name);

		$bodyHtml = $this->getIndexBodyHtml($children);

		$html = self::getHtml($cssFiles, $menuHtml, $titleHtml, $bodyHtml);

		$this->filesystem->write($filePath, $html);
	}

	private function getCssFilePaths($depth, array $files)
	{
		$atoms = array_fill(0, $depth, '..');
		$atoms[] = 'style';

		$cssDirectory = $this->paths->join($atoms);

		foreach ($files as &$file) {
			$file = $this->paths->join($cssDirectory, $file);
		}

		return $files;
	}

	private function getIndexBodyHtml(array $children)
	{
		usort($children, array('self', 'sortIndexItems'));

		$tableItems = array();

		foreach ($children as $child) {
			list($childName, $childIsDirectory, $childPass, $childFail) = $child;

			if ($childIsDirectory) {
				$childUrl = $this->paths->join($childName, 'index.html');
				$childClass = 'directory';
				$childLabel = $childName . DIRECTORY_SEPARATOR;
			} else {
				$childUrl = "{$childName}.html";
				$childClass = 'file';
				$childLabel = $childName;
			}

			$childNameHtml = '<span></span>' . self::html5TextEncode($childLabel);
			$childNameHtml = self::getLinkHtml($childNameHtml, $childUrl);

			$tableItems[] = self::getIndexItemHtml($childClass, $childNameHtml, $childPass, $childFail);
		}

		$tableBodyHtml = implode("\n", array_map(array('self', 'indent'), $tableItems));
		$tableHtml = "<table>\n{$tableBodyHtml}\n</table>";

		return $tableHtml;
	}

	private static function sortIndexItems($a, $b)
	{
		list($aName, $aIsDirectory) = $a;
		list($bName, $bIsDirectory) = $b;

		if ($aIsDirectory === $bIsDirectory) {
			return strcmp($aName, $bName);
		}

		if ($aIsDirectory) {
			return -1;
		}

		return 1;
	}

	private static function getIndexItemHtml($class, $nameHtml, $pass, $fail)
	{
		$quality = self::getQuality($pass, $fail);
		$percentage = self::getPercentage($quality);
		$percentageHtml = self::html5TextEncode($percentage);

		if ($class === null) {
			$attributes = '';
		} else {
			$classHtml = self::html5AttributeEncode($class);
			$attributes = " class=\"{$classHtml}\"";
		}

		return "<tr{$attributes}><td>{$percentageHtml}</td><th>{$nameHtml}</th></tr>";
	}

	private static function getQuality($pass, $fail)
	{
		$total = $pass + $fail;

		if ($total === 0) {
			$quality = 1.0;
		} else {
			$quality = (float)($pass / $total);
		}

		return $quality;
	}

	private static function getPercentage($ratio)
	{
		return (string)(int)floor($ratio * 100) . '%';
	}

	private function writeFile($name, $path, &$pass = null, &$fail = null)
	{
		$filePath = $this->paths->join($this->coverageDirectory, "{$path}.html");

		$data = $this->paths->deserialize($path);
		$atoms = $data->getAtoms();
		$depth = count($atoms) - 1;

		$cssFiles = $this->getCssFilePaths($depth, array('style.css', 'file.css'));

		$urls = $this->getMenuUrls($path, 0);
		$menuHtml = self::getMenuHtml($urls);

		$titleHtml = self::html5TextEncode($name);

		$code = $this->code[$path];
		$status = $this->coverage[$path];

		$pass = count(array_filter($status));
		$fail = count($status) - $pass;

		$bodyHtml = $this->getFileBodyHtml($code, $status);

		$html = self::getHtml($cssFiles, $menuHtml, $titleHtml, $bodyHtml);

		$this->filesystem->write($filePath, $html);
	}

	private function getMenuUrls($path, $depth)
	{
		$data = $this->paths->deserialize($path);
		$atoms = $data->getAtoms();

		array_pop($atoms);
		array_unshift($atoms, 'coverage');

		$depth += count($atoms) - 1;

		$urls = array();

		foreach ($atoms as $atom) {
			$urlAtoms = array_fill(0, $depth--, '..');
			$urlAtoms[] = 'index.html';

			$url = $this->paths->join($urlAtoms);

			$urls[$url] = $atom;
		}

		return $urls;
	}

	private static function getMenuHtml(array $urls)
	{
		$html = '<ul>';

		foreach ($urls as $url => $name) {
			$nameHtml = '<span></span>' . self::html5TextEncode($name);
			$nameHtml = self::getLinkHtml($nameHtml, $url);
			$html .= "<li>{$nameHtml}</li>";
		}

		$html .= '</ul>';

		return "<div id=\"menu\">\n\t{$html}\n</div>\n";
	}

	private static function getLinkHtml($nameHtml, $url)
	{
		$urlHtml = self::html5AttributeEncode($url);

		return "<a href=\"{$urlHtml}\">{$nameHtml}</a>";
	}

	private function getFileBodyHtml(array $code, array $coverage)
	{
		$rows = array();

		foreach ($code as $i => $line) {
			$lineHtml = self::html5TextEncode($line);

			$isCovered = &$coverage[$i];

			if ($isCovered === null) {
				$attribute = '';
			} elseif ($isCovered === true) {
				$attribute = ' class="pass"';
			} else {
				$attribute = ' class="fail"';
			}

			$lineNumber = $i + 1;
			$rows[] = "<tr{$attribute}><th>{$lineNumber}</th><td>{$lineHtml}</td></tr>";
		}

		$rowHtml = implode("\n", array_map(array('self', 'indent'), $rows));

		$tableHtml = "<table>\n{$rowHtml}\n</table>";

		return $tableHtml;
	}

	private static function getHtml($cssPaths, $menuHtml, $titleHtml, $bodyHtml)
	{
		$head = array(
			'<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">',
			"<title>{$titleHtml}</title>"
		);

		foreach ($cssPaths as $url) {
			$hrefAttribute = self::html5AttributeEncode($url);
			$head[] = "<link href=\"{$hrefAttribute}\" rel=\"stylesheet\" type=\"text/css\">";
		}

		$headHtml = implode("\n", array_map(array('self', 'indent'), $head));

		if (isset($menuHtml)) {
			$menuHtml = "\n\n{$menuHtml}";
		}

		return <<<EOS
<!DOCTYPE html>
		
<html lang="en">
		
<head>
{$headHtml}
</head>

<body>{$menuHtml}

<h1>{$titleHtml}</h1>

{$bodyHtml}

</body>

</html>
EOS;
	}

	private static function indent($text)
	{
		return "\t{$text}";
	}

	private static function html5TextEncode($text)
	{
		$text = htmlspecialchars($text, ENT_HTML5 | ENT_COMPAT | ENT_DISALLOWED | ENT_NOQUOTES, 'UTF-8');
		$text = str_replace("\t", '     ', $text);
		$text = str_replace(' ', '&nbsp;', $text);

		return $text;
	}

	private static function html5AttributeEncode($text)
	{
		return htmlspecialchars($text, ENT_HTML5 | ENT_COMPAT | ENT_DISALLOWED | ENT_QUOTES, 'UTF-8');
	}

	private function writeCssFiles()
	{
		$files = func_get_args();

		$inputDirectory = dirname(__DIR__) . '/files/style';
		$outputDirectory = "{$this->coverageDirectory}/style";

		foreach ($files as $file) {
			$inputFilePath = "{$inputDirectory}/{$file}";
			$outputFilePath = "{$outputDirectory}/{$file}";

			$this->copyFile($inputFilePath, $outputFilePath);
		}
	}

	private function copyFile($inputFilePath, $outputFilePath)
	{
		$contents = $this->filesystem->read($inputFilePath);
		$this->filesystem->write($outputFilePath, $contents);
	}
}
