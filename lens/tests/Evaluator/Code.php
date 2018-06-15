<?php

namespace Lens\Evaluator;

// Test
$code = new Code();
list($contextPhp, $beforePhp, $expectedPhp, $actualPhp, $script) = $code->getPhp($fixturePhp, $inputPhp, $outputPhp, $testPhp);

// Input
$fixturePhp = "namespace Example;";
$inputPhp = "\$terminal = new Terminal();";
$outputPhp = "\$terminal->write();\n\$terminal->read(); // return \"cat\";\nfgets(STDIN); // return \"text\\n\";\n\$output = 'text';";
$testPhp = "\$speller = new Speller(\$terminal);\n\$speller->start();";

// Output
$contextPhp = "namespace Example;";
$beforePhp = "\$terminal = new \\Lens\\Mock\\Example\\Terminal();";
$expectedPhp = "\$terminal->write();\n\$terminal->read();\nfgets(STDIN);\n\$output = 'text';";
$actualPhp = "\$speller = new Speller(\$terminal);\n\$speller->start();";
$script = [null, "return \"cat\";", "return \"text\\n\";"];
