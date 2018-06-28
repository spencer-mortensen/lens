<?php

namespace _Lens\SpencerMortensen\ParallelProcessor;

use Error;
use Exception;
use _Lens\SpencerMortensen\Exceptions\Exceptions;

class Message
{
	const TYPE_ERROR = 0;
	const TYPE_RESULT = 1;

	public static function serialize($type, $value)
	{
		$data = [
			$type => $value
		];

		return serialize($data);
	}

	public static function deserialize($serialized)
	{
		try {
			Exceptions::on();
			$data = unserialize($serialized);
		} catch (Exception $exception) {
			throw ProcessorException::invalidMessage($serialized);
		} catch (Error $error) {
			throw ProcessorException::invalidMessage($serialized);
		} finally {
			Exceptions::off();
		}

		if (!is_array($data) || (count($data) === 0)) {
			throw ProcessorException::invalidMessage($serialized);
		}

		list($type, $value) = each($data);

		if ($type === self::TYPE_RESULT) {
			return $value;
		}

		if (($value instanceof Exception) || ($value instanceof Error)) {
			throw $value;
		}

		throw ProcessorException::invalidMessage($serialized);
	}
}
