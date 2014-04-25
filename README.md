EmailValidator [![Build Status](https://travis-ci.org/egulias/EmailValidator.png?branch=master)](https://travis-ci.org/egulias/EmailValidator) [![Coverage Status](https://coveralls.io/repos/egulias/EmailValidator/badge.png?branch=master)](https://coveralls.io/r/egulias/EmailValidator?branch=master) [![SensioLabsInsight](https://insight.sensiolabs.com/projects/b18d473e-bd5a-4d88-a7b2-7aeaee0ebd7b/small.png)](https://insight.sensiolabs.com/projects/b18d473e-bd5a-4d88-a7b2-7aeaee0ebd7b)
=============================

##Installation##
Install via composer. Add to your current compooser.json ```require``` key: ```"egulias/email-validator":"1.0.x-dev" ```

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

* Ricard Clau [@ricardclau](http://github.com/ricardclau):      	Performance against PHP built-in filter_var
* Josepf Bielawski [@stloyd](http://github.com/stloyd):      		For its first re-work of Dominic's lib
* Dominic Sayers [@dominicsayers](http://github.com/dominicsayers):  	The original isemail function

##Licence##
Released under the MIT Licence attached with this code.

