<?php

namespace _Lens\SpencerMortensen\Parser;

interface StubAnalyzerInterface
{
	public function getParserHeader();

	public function getParserClass();

	public function getInputClass();

	public function getRules();

	public function getStartRule();

	public function getInputTypePhp($type);
}
