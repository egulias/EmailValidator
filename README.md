#EmailValidator
[![Build Status](https://travis-ci.org/egulias/EmailValidator.png?branch=master)](https://travis-ci.org/egulias/EmailValidator) [![Coverage Status](https://coveralls.io/repos/egulias/EmailValidator/badge.png?branch=master)](https://coveralls.io/r/egulias/EmailValidator?branch=master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/egulias/EmailValidator/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/egulias/EmailValidator/?branch=master) [![SensioLabsInsight](https://insight.sensiolabs.com/projects/22ba6692-9c02-42e5-a65d-1c5696bfffc6/small.png)](https://insight.sensiolabs.com/projects/22ba6692-9c02-42e5-a65d-1c5696bfffc6)
=============================
With the help of

![Powered by PhpStorm](https://www.jetbrains.com/phpstorm/documentation/docs/logo_phpstorm.png)
##Installation##

Run the command below to install via Composer

```shell
composer require egulias/email-validator "~2.0"
```

##Getting Started##
`EmailValidator`requires you to decide which (or combination of them) validation/s strategy/ies you'd like to follow for each [validation](#Available validations).

A basic example with the RFC validation
```php
<?php

use Egulias\EmailValidator\EmailValidator;

$validator = new EmailValidator();
$validator->isValid("example@example.com", new RFCValidation()) //true
```


###Available validations###

1. [RFCValidation](https://github.com/egulias/EmailValidator/blob/master/EmailValidator/Validation/RFCValidation.php)
2. [NoWarningsRFCValidation](https://github.com/egulias/EmailValidator/blob/master/EmailValidator/Validation/NoRFCWarningsValidation.php)
3. [DNSCheckValidation](https://github.com/egulias/EmailValidator/blob/master/EmailValidator/Validation/DNSCheckValidation.php)
4. [SpoofCheckValidation](https://github.com/egulias/EmailValidator/blob/master/EmailValidator/Validation/SpoofCheckValidation.php)
5. [MultipleValidationsWithAnd](https://github.com/egulias/EmailValidator/blob/master/EmailValidator/Validation/MultipleValidationWithAnd.php)
6. [Your own validation](#How to extend)

`MultipleValidationsWithAnd`
It is a validation that operates over other validations performing a logical and (&&) over the result of each validation.
```php
<?php

use Egulias\EmailValidator\EmailValidator;

$validator = new EmailValidator();
$multipleValidations = new MultipleValidationsWithAnd([
    new RFCValidation(),
    new DNSCheckValidation
])
$validator->isValid("example@example.com", $multipleValidations) //true
```

###How to extend###

Is easy! You just need to extend [EmailValidation](https://github.com/egulias/EmailValidator/blob/master/EmailValidator/Validation/EmailValidation.php) and you can use your own validation.


##Other Contributors##
(You can find current contributors [here](https://github.com/egulias/EmailValidator/graphs/contributors))
As this is a port from another library and work, here are other people related to the previous one:

* Ricard Clau [@ricardclau](http://github.com/ricardclau):      	Performance against PHP built-in filter_var
* Josepf Bielawski [@stloyd](http://github.com/stloyd):      		For its first re-work of Dominic's lib
* Dominic Sayers [@dominicsayers](http://github.com/dominicsayers):  	The original isemail function

##License##
Released under the MIT License attached with this code.

