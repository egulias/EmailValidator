# EmailValidator

[![Build Status](https://travis-ci.org/egulias/EmailValidator.svg?branch=master)](https://travis-ci.org/egulias/EmailValidator)
[![Coverage Status](https://coveralls.io/repos/egulias/EmailValidator/badge.svg?branch=master)](https://coveralls.io/r/egulias/EmailValidator?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/egulias/EmailValidator/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/egulias/EmailValidator/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/22ba6692-9c02-42e5-a65d-1c5696bfffc6/small.png)](https://insight.sensiolabs.com/projects/22ba6692-9c02-42e5-a65d-1c5696bfffc6)

## Supported RFCs ##

This library aims to support:

RFC 5321, 5322, 6530, 6531, 6532.

## Requirements ##

 * [Composer](https://getcomposer.org) is required for installation
 * [Spoofchecking](/src/Validation/SpoofCheckValidation.php) and [DNSCheckValidation](/src/Validation/DNSCheckValidation.php) validation requires that your PHP system has the [PHP Internationalization Libraries](https://php.net/manual/en/book.intl.php) (also known as PHP Intl)

## Installation ##

Run the command below to install via Composer

```shell
composer require egulias/email-validator
```

## Getting Started ##

`EmailValidator`requires you to decide which (or combination of them) validation/s strategy/ies you'd like to follow for each [validation](#available-validations).

A basic example with the RFC validation
```php
<?php

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;

$validator = new EmailValidator();
$validator->isValid("example@example.com", new RFCValidation()); //true
```


### Available validations ###

1. [RFCValidation](/src/Validation/RFCValidation.php): Standard RFC-like email validation.
2. [NoRFCWarningsValidation](/src/Validation/NoRFCWarningsValidation.php): RFC-like validation that will fail when warnings* are found.
3. [DNSCheckValidation](/src/Validation/DNSCheckValidation.php): Will check if there are DNS records that signal that the server accepts emails. This does not entails that the email exists.
4. [SpoofCheckValidation](/src/Validation/SpoofCheckValidation.php): Will check for multi-utf-8 chars that can signal an erroneous email name.
5. [MultipleValidationWithAnd](/src/Validation/MultipleValidationWithAnd.php): It is a validation that operates over other validations performing a logical and (&&) over the result of each validation.
6. [Your own validation](#how-to-extend): You can extend the library behaviour by implementing your own validations.

*warnings: Warnings are deviations from the RFC that in a broader interpretation are acceptded.

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
//ietf.org has MX records signaling a server with email capabilites
$validator->isValid("example@ietf.org", $multipleValidations); //true
```

### How to extend ###

It's easy! You just need to implement [EmailValidation](/src/Validation/EmailValidation.php) and you can use your own validation.


## Other Contributors ##

(You can find current contributors [here](https://github.com/egulias/EmailValidator/graphs/contributors))

As this is a port from another library and work, here are other people related to the previous one:

* Ricard Clau [@ricardclau](https://github.com/ricardclau):      	Performance against PHP built-in filter_var
* Josepf Bielawski [@stloyd](https://github.com/stloyd):      		For its first re-work of Dominic's lib
* Dominic Sayers [@dominicsayers](https://github.com/dominicsayers):  	The original isemail function

## License ##

Released under the MIT License attached with this code.
