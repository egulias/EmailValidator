#EmailValidator
[![Build Status](https://travis-ci.org/egulias/EmailValidator.png?branch=master)](https://travis-ci.org/egulias/EmailValidator) [![Coverage Status](https://coveralls.io/repos/egulias/EmailValidator/badge.png?branch=master)](https://coveralls.io/r/egulias/EmailValidator?branch=master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/egulias/EmailValidator/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/egulias/EmailValidator/?branch=master) [![SensioLabsInsight](https://insight.sensiolabs.com/projects/22ba6692-9c02-42e5-a65d-1c5696bfffc6/small.png)](https://insight.sensiolabs.com/projects/22ba6692-9c02-42e5-a65d-1c5696bfffc6)
=============================
With the help of

![Powered by PhpStorm](https://www.jetbrains.com/phpstorm/documentation/docs/logo_phpstorm.png)
##Installation##

Run the command below to install via Composer

```shell
composer require egulias/email-validator
```

##Usage##

Simple example:

```php
<?php

use Egulias\EmailValidator\EmailValidator;

$validator = new EmailValidator;
if ($validator->isValid($email)) {
	echo $email . ' is a valid email address';
}
```

More advanced example (returns detailed diagnostic error codes):

```php
<?php

use Egulias\EmailValidator\EmailValidator;

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

* Ricard Clau [@ricardclau](http://github.com/ricardclau):      	Performance against PHP built-in filter_var
* Josepf Bielawski [@stloyd](http://github.com/stloyd):      		For its first re-work of Dominic's lib
* Dominic Sayers [@dominicsayers](http://github.com/dominicsayers):  	The original isemail function

##License##
Released under the MIT License attached with this code.

