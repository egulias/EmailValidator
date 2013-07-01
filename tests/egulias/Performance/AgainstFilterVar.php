<?php

use Egulias\EmailValidator\EmailValidator;

require __DIR__ . '/../../bootstrap.php';

$iterations = 10000;

$testingMail = 'fabien@symfony.com';
echo 'Testing ' . $iterations . ' iterations with ' . $testingMail . PHP_EOL;

$a = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $isValid = filter_var($testingMail, FILTER_VALIDATE_EMAIL);
}
$b = microtime(true);
echo ($b - $a) . ' seconds with filter_var' . PHP_EOL;

$a = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $validator = new EmailValidator();
    $isValid = $validator->isValid($testingMail);
}
$b = microtime(true);
echo ($b - $a) . ' seconds with EmailValidator + instantiation' . PHP_EOL;

$a = microtime(true);
$validator = new EmailValidator();
for ($i = 0; $i < $iterations; $i++) {
    $isValid = $validator->isValid($testingMail);
}
$b = microtime(true);
echo ($b - $a) . ' seconds with EmailValidator once instanced' . PHP_EOL;
