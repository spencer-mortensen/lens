<?php

namespace Example;

class Speller
{
	private $terminal;

	public function __construct(Terminal $terminal)
	{
		$this->terminal = $terminal;
	}

	public function start()
	{
		$this->terminal->write("Type a word:");

		$word = $this->terminal->read();
		$spelling = $this->spell($word);

		$this->terminal->write("The word \"{$word}\" is spelled: {$spelling}!");
	}

	private function spell($word)
	{
		return implode('-', str_split(strtoupper($word)));
	}
}
