E-conomic API
=============
Lightweight wrapper for the E-conomic REST API. Heavily inspired by the excellent [drewm/mailchimp-api](https://github.com/drewm/mailchimp-api) wrapper.

Installation
------------

You can install economic-api using Composer:

```
composer require rbech/economic-api
```

Examples
--------
Get a list of customers
```php
use \RBech\Economic\Economic;

$economic = new Economic('your-app-secret', 'your-grant-token');
```

Then, get a list of all customers (by issuing `get` request to the `customers` endpoint)

```php
$customers = $economic->get('customers');

print_r($customers);
```

Createa customer in e-conomic

```php
$result = $economic->post('customers', [
    'address' => 'My fancy address',
    'city' => 'Fancyville',
    'zip' => '9999',
    'customernumber' => 900000000,
    'email' => 'test@example.com',
    'name' => 'John Doe',
    'telephoneAndFaxNumber' => '11223344',
    'currency' => 'DKK',
    'customerGroup' => [
        'customerGroupNumber' => 1,
    ],
    'vatZone' => [
        'vatZoneNumber' => 1,
    ],
    'paymentTerms' => [
        'paymentTermsNumber' => 1
    ]
]);
```

Test for a successful request with the `success()` method:

```php
if ($economic->success()) {
    print_r($result);
} else {
    //getLastError will return a simple string
    echo $economic->getLastError();
    
    //getErrors returns an array of errors returned
    print_r($economic->getErrors());
}
```

Contributing
------------

Improvements and bug fixes are more than welcome, if come up with an improvement, please create an issue to discuss it before making a pull request.

If you find a bug please explain the bug throughly and the steps needed to reproduce it.