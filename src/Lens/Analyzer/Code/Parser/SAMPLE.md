=== EXTRACT ===

namespace
uses

functions
classes
interfaces
traits

calls:
	\C::f();
	 C::f();
	\f();
	 f();

instantiations:
	new \C();
	new C();


=== PHP ===
<?php

declare(encoding = 'UTF-8');

namespace A\B {
	use A\{A};
	use function Example\f;

	if (!function_exists('time')) {
		function time()
		{
			echo "time\n";
		}
	}

	$example = function () use (&$message) {
		var_dump($message);
	};

	trait Hello {
	    public function sayHello() {
	        echo 'Hello ';
	    }
	}

	class C extends X implements I
	{
		use Hello, World;

		use A, B {
			B::smallTalk insteadof A;
			A::bigTalk insteadof B;
			B::bigTalk as talk;
		}

		private function f()
		{
			self::f();

			function g()
			{
				return "g";
			}
		}

		public $bar;

		public function __construct() {
			$this->bar = function() {
				return 42;
			};
		}
	}

	$object = new C();
	echo ($object->bar)(); // Call an anonymous function

	$f = $object->bar;
	echo $f(); // Call an anonymous function

	interface I extends A, B
	{
	    public function setVariable($name, $var);
	    public function getHtml($template);
	}
}
