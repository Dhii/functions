# Dhii - Functions

[![Build Status](https://travis-ci.org/dhii/functions.svg?branch=master)](https://travis-ci.org/dhii/functions)
[![Code Climate](https://codeclimate.com/github/dhii/functions/badges/gpa.svg)](https://codeclimate.com/github/dhii/functions)
[![Test Coverage](https://codeclimate.com/github/dhii/functions/badges/coverage.svg)](https://codeclimate.com/github/dhii/functions/coverage)
[![Latest Stable Version](https://poser.pugx.org/dhii/functions/version)](https://packagist.org/packages/dhii/functions)
[![This package complies with Dhii standards](https://img.shields.io/badge/Dhii-Compliant-green.svg?style=flat-square)][Dhii]

Utilities for working with functions

## Usage

Output a return  value

```php
use Dhii\Functions\Func;

$fn = Func::output(function () {
    return "Hello world";
});

$fn(); // Outputs "Hello world"
```

Return captured output

```php
use Dhii\Functions\Func;

$fn = Func::capture(function () {
    echo "Hello world";
});

$fn(); // Returns "Hello world"
```

One-liner for quick value returning functions

```php
use Dhii\Functions\Func;

$fn = Func::thatReturns(pi());

$fn(); // Returns 3.14159...
```

Partially apply functions

```php
use Dhii\Functions\Func;

$createUser = function($name, $role) {
    // ...
};

$createAdmin = Func::apply($createUser, [Func::SKIP, 'admin']);

$createAdmin("JC Denton"); // Calls $createUser("JC Denton", "admin")
```

Merge callbacks into a single function

```php
use Dhii\Functions\Func;

$listener1 = function ($event) {
    // ... 
};

$listener2 = function ($event) {
    // ...
};

$listener = Func::merge([$listener1, $listener2]);

$listener(['name' => 'some_event']);
```

Pipe functions together

```php
use Dhii\Functions\Func;

$getFile = Func::thatReturns('path/to/template.php');

$createTemplate = function (string $path) {
    return new Template($path);
};

$render = function(Template $template) {
    $template->render([
        'first_name' => 'Bob',
        'last_name' => 'Page',
    ]);
};

$fn = Func::pipe([$getFile, $createTemplate, $render]);

$fn(); // Calls $render($createTemplate($getFile()));
```

Re-order arguments

```php
use Dhii\Functions\Func;

$divide = function ($a, $b) {
    return $a / $b;
};

$fn = Func::reorderArgs($divide, [0 => 1]);

$fn(3, 6); // Returns 6 / 3 = 2
```

Map arguments

```php
use Dhii\Functions\Func;

$sum = function (...$nums) {
    return array_sum($nums);
};

$fn = Func::mapArgs($sum, function ($num) {
    return $num * 2;
});

$fn(2, 4, 6); // Returns 4 + 8 + 12 = 24
```

Call methods on arguments

```php
use Dhii\Functions\Func;

$dateTimes = [
    new DateTime(),
    new DateTime(),
    // ...
];

$timestamps = array_map(Func::method('getTimestamp'), $dateTimes);
```

Catch exceptions using functions.

```php
use Dhii\Functions\Func;

$thrower = function () {
    throw new OutOfBoundsException();
};

$fn = Func::catch($thrower, [OutOfBoundsException::class], function ($exception) {
    echo $exception->getMessage();
});

$fn(2, 4, 6); // Returns 4 + 8 + 12 = 24
```

[Dhii]: https://github.com/Dhii/dhii
