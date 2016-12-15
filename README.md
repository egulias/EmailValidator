#EmailValidator
[![Build Status](https://travis-ci.org/egulias/EmailValidator.png?branch=master)](https://travis-ci.org/egulias/EmailValidator) [![Coverage Status](https://coveralls.io/repos/egulias/EmailValidator/badge.png?branch=master)](https://coveralls.io/r/egulias/EmailValidator?branch=master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/egulias/EmailValidator/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/egulias/EmailValidator/?branch=master) [![SensioLabsInsight](https://insight.sensiolabs.com/projects/22ba6692-9c02-42e5-a65d-1c5696bfffc6/small.png)](https://insight.sensiolabs.com/projects/22ba6692-9c02-42e5-a65d-1c5696bfffc6)
=============================
With the help of [PHPStorm](https://www.jetbrains.com/phpstorm/)

##Requirements##

 * [Composer](https://getcomposer.org) is required for installation
 * [Spoofchecking](https://github.com/egulias/EmailValidator/blob/master/EmailValidator/Validation/SpoofCheckValidation.php) validation requires that your PHP system have the [PHP Internationalization Libraries](http://php.net/manual/en/book.intl.php) (also known as PHP Intl)

##Installation##

Run the command below to install via Composer

```shell
composer require egulias/email-validator "~2.1"
```

##Getting Started##
`EmailValidator`requires you to decide which (or combination of them) validation/s strategy/ies you'd like to follow for each [validation](#available-validations).

A basic example with the RFC validation
```php
<?php

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;

$validator = new EmailValidator();
$validator->isValid("example@example.com", new RFCValidation()); //true
```


###Available validations###

1. [RFCValidation](https://github.com/egulias/EmailValidator/blob/master/EmailValidator/Validation/RFCValidation.php)
2. [NoRFCWarningsValidation](https://github.com/egulias/EmailValidator/blob/master/EmailValidator/Validation/NoRFCWarningsValidation.php)
3. [DNSCheckValidation](https://github.com/egulias/EmailValidator/blob/master/EmailValidator/Validation/DNSCheckValidation.php)
4. [SpoofCheckValidation](https://github.com/egulias/EmailValidator/blob/master/EmailValidator/Validation/SpoofCheckValidation.php)
5. [MultipleValidationWithAnd](https://github.com/egulias/EmailValidator/blob/master/EmailValidator/Validation/MultipleValidationWithAnd.php)
6. [Your own validation](#how-to-extend)

`MultipleValidationWithAnd`

It is a validation that operates over other validations performing a logical and (&&) over the result of each validation.

```php
<?php

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;

$validator = new EmailValidator();
$multipleValidations = new MultipleValidationWithAnd([
    new RFCValidation(),
    new DNSCheckValidation()
]);
$validator->isValid("example@example.com", $multipleValidations); //true
```

###How to extend###

It's easy! You just need to extend [EmailValidation](https://github.com/egulias/EmailValidator/blob/master/EmailValidator/Validation/EmailValidation.php) and you can use your own validation.


##Other Contributors##
(You can find current contributors [here](https://github.com/egulias/EmailValidator/graphs/contributors))

As this is a port from another library and work, here are other people related to the previous one:

* Ricard Clau [@ricardclau](http://github.com/ricardclau):      	Performance against PHP built-in filter_var
* Josepf Bielawski [@stloyd](http://github.com/stloyd):      		For its first re-work of Dominic's lib
* Dominic Sayers [@dominicsayers](http://github.com/dominicsayers):  	The original isemail function

##License##
Released under the MIT License attached with this code.

