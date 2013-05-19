EmailValidator
=============================

##Installation##
Install via composer.

###Build Status###
[![Build Status](https://travis-ci.org/egulias/EmailValidator.png?branch=master)](https://travis-ci.org/egulias/EmailValidator)

##Usage##

Simple example:

```php
<?php

use egulias\EmailValidator\EmailValidator;

$validator = new EmailValidator;
if ($validator->isValid($email)) {
	echo $email . ' is a valid email address';
}
```

More advanced example (returns detailed diagnostic error codes):

```php
<?php

use egulias\EmailValidator\EmailValidator;

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

##Contributors##
As this is a port from another library and work, here are other people related to the previous:

*Ricard Clau @ricardclau :      Performance against PHP built-in filter_var
*Josepf Bielawski @stloyd:      For its first re-work of Dominic's lib
*Domini Sayers @dominicsayers:  The original isemail function

##Licence##
Released under the MIT Licence attached with this code.

