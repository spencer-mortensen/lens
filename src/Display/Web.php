<?php

/**
 * Copyright (C) 2017 Spencer Mortensen
 *
 * This file is part of TestPHP.
 *
 * TestPHP is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TestPHP is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with TestPHP. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Spencer Mortensen <spencer@spencermortensen.com>
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL-3.0
 * @copyright 2017 Spencer Mortensen
 */

namespace TestPhp\Display;

use TestPhp\Filesystem;

class Web
{
	/** @var Filesystem */
	private $filesystem;

	/** @var string */
	private $codeDirectory;

	/** @var string */
	private $coverageDirectory;

	/** @var array */
	private $code;

	/** @var array */
	private $status;

	public function __construct(Filesystem $filesystem)
	{
		$this->filesystem = $filesystem;
	}

	public function coverage($codeDirectory, $coverageDirectory, $coverage)
	{
		// TODO: generate HTML containing instructions for enabling code coverage
		if ($coverage === null) {
			/*
			extension_loaded('xdebug') &&
			version_compare(phpversion('xdebug'), '2.2', '>=') &&
			(boolean)ini_get('xdebug.coverage_enable');
			*/
			return;
		}

		$this->codeDirectory = $codeDirectory;
		$this->coverageDirectory = $coverageDirectory;
		$this->code = $coverage['code'];
		$this->status = $coverage['status'];

		$filePaths = array_keys($this->code);
		$hierarchy = self::getRelativeHierarchy($filePaths);

		exec("rm -rf {$this->coverageDirectory}/*");
		$this->writeDirectory('coverage', $hierarchy, '');
		$this->writeCssFiles();
	}

	private static function getRelativeHierarchy($filePaths)
	{
		$files = array();
		foreach ($filePaths as $filePath) {
			$trail = self::getTrail($filePath);

			$p = &$files;

			foreach ($trail as $name) {
				$p = &$p[$name];
			}

			$p = null;
		}

		return $files;
	}

	private static function getTrail($filePath)
	{
		$filePath = trim($filePath, '/');

		if (($filePath === '.') || ($filePath === '')) {
			return array();
		}

		return explode('/', $filePath);
	}

	private function writeDirectory($name, array $contents, $path, &$pass = null, &$fail = null)
	{
		$children = array();
		$pass = 0;
		$fail = 0;

		foreach ($contents as $childName => $childContents) {
			$isChildDirectory = is_array($childContents);
			$childPath = self::mergePaths($path, $childName);

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
		$relativeFilePath = self::mergePaths($path, 'index.html');

		$depth = substr_count($relativeFilePath, '/');

		$cssFiles = array(
			str_repeat('../', $depth) . 'style/style.css',
			str_repeat('../', $depth) . 'style/directory.css'
		);

		$urls = self::getMenuUrls($path, 1);
		$menuHtml = self::getMenuHtml($urls);

		$titleHtml = self::html5TextEncode($name);

		$bodyHtml = self::getIndexBodyHtml($children);

		$html = self::getHtml($cssFiles, $menuHtml, $titleHtml, $bodyHtml);

		$absoluteFilePath = $this->coverageDirectory . '/' . $relativeFilePath;

		$this->filesystem->write($absoluteFilePath, $html);
	}

	private static function getIndexBodyHtml(array $children)
	{
		usort($children, array('self', 'sortIndexItems'));

		$tableItems = array();

		foreach ($children as $child) {
			list($childName, $childIsDirectory, $childPass, $childFail) = $child;

			if ($childIsDirectory) {
				$childUrl = "{$childName}/index.html";
				$childClass = 'directory';
				$childLabel = "{$childName}/";
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
		$depth = substr_count($path, '/');

		$cssFiles = array(
			str_repeat('../', $depth) . 'style/style.css',
			str_repeat('../', $depth) . 'style/file.css'
		);

		$urls = self::getMenuUrls($path, 0);
		$menuHtml = self::getMenuHtml($urls);

		$titleHtml = self::html5TextEncode($name);

		$code = $this->code[$path];
		$status = $this->status[$path];

		$pass = count(array_filter($status));
		$fail = count($status) - $pass;

		$bodyHtml = $this->getFileBodyHtml($code, $status);

		$html = self::getHtml($cssFiles, $menuHtml, $titleHtml, $bodyHtml);

		$relativeFilePath ="{$path}.html";
		$absoluteFilePath = $this->coverageDirectory . '/' . $relativeFilePath;

		$this->filesystem->write($absoluteFilePath, $html);
	}

	private static function getMenuUrls($path, $depth)
	{
		$trail = self::getTrail($path);
		array_unshift($trail, 'coverage');
		array_pop($trail);

		$urls = array();

		$depth += count($trail) - 1;

		foreach ($trail as $name) {
			$url = str_repeat('../', $depth--) . 'index.html';
			$urls[$url] = $name;
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

			$rows[] = "<tr{$attribute}><th>{$i}</th><td>{$lineHtml}</td></tr>";
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

		return <<<EOS
<!DOCTYPE html>
		
<html lang="en">
		
<head>
{$headHtml}
</head>

<body>

{$menuHtml}

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

	private static function mergePaths($a, $b)
	{
		if ($a === '') {
			return $b;
		}

		return "{$a}/{$b}";
	}

	private function writeCssFiles()
	{
		$inputDirectory = dirname(dirname(__DIR__)) . '/files/style';
		$outputDirectory = "{$this->coverageDirectory}/style";

		$files = array(
			'style.css',
			'directory.css',
			'file.css',
			'filesystem.png'
		);

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
