EmailValidator
=============================

#Installation#
Install via composer.

#Usage#

Simple example:

```php
<?php

use IsEmail\EmailValidator;

$validator = new EmailValidator;
if ($validator->isValid($email)) {
	echo $email . ' is a valid email address';
}
```

More advanced example (returns detailed diagnostic error codes):

```php
<?php

require_once 'EmailValidator.php';

$validator = new EmailValidator;
$email = 'dominic@sayers.cc';
$result = $validator->isValid($email);

if ($result) {
	echo $email . ' is a valid email address';
} else if ($validator->hasWarnings()) {
	echo 'Warning! ' . $email . ' has unusual/deprecated features (result code ' . var_export($validator->getWarnings(), true) . ')';
} else {
	echo $email . ' is not a valid email address (result code ' . $validator->getError() . ')';
}
```

#Licence#
This code is released under the MIT Licence attached with this code.

