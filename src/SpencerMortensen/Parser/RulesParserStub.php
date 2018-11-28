<?php

namespace _Lens\SpencerMortensen\Parser;

use _Lens\SpencerMortensen\Parser\Input\StringInput;

class RulesParserStub
{
	public $rules = <<<'EOS'
rules: and rule rulesChain
rule: and ruleName ruleDefinition
ruleName: get \h*([a-zA-Z_]+)\h*:\h*
ruleDefinition: or ruleGet ruleAnd ruleOr ruleAny ruleNot
ruleGet: get get\h+([^\v]+)
ruleAnd: and ruleAndKeyword words
ruleAndKeyword: get and\h+
word: get ([a-zA-Z_]+)\h*
words: any word 1+
ruleOr: and ruleOrKeyword words
ruleOrKeyword: get or\h+
ruleAny: and ruleAnyKeyword word ruleAnyBounds
ruleAnyKeyword: get any\h+
ruleAnyBounds: get (?<min>0|[1-9][0-9]*)\h*(?:[+]|[-]\h*(?<max>0|[1-9][0-9]*))\h*
rulesChain: any ruleLink 0+
ruleLink: and ruleSeparator rule
ruleSeparator: get \v\s*
EOS;

	public $startRule = 'rules';

	public function rules($rule, array $rules)
	{
		array_unshift($rules, $rule);
		return call_user_func_array('array_merge', $rules);
	}

	public function rule($name, $definition)
	{
		return [$name => $definition];
	}

	public function ruleName($name)
	{
		return $name;
	}

	public function ruleGet($expression)
	{
		return ['get', rtrim($expression)];
	}

	public function ruleAnd(array $words)
	{
		return ['and', $words];
	}

	public function word($word)
	{
		return $word;
	}

	public function ruleOr(array $words)
	{
		return ['or', $words];
	}

	public function ruleAny($word, array $bounds)
	{
		$definition = $bounds;
		array_unshift($definition, $word);
		return ['any', $definition];
	}

	public function ruleAnyBounds(array $matches)
	{
		$min = (int)$matches['min'];
		$max = isset($matches['max']) ? max($min, (int)$matches['max']) : null;

		return [$min, $max];
	}

	public function ruleLink($rule)
	{
		return $rule;
	}

	public function __invoke(StringInput $input)
	{
	}

	public function __get($identifier)
	{
		return var_export($identifier, true);
	}
}
