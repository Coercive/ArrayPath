Coercive ArrayPath
==================

- Cross a table like a file path.

Get
---
```
composer require coercive/arraypath
```

Compatible PHP 7.4 → 8.5

Tests
-----

```
composer install
vendor/bin/phpunit
```

PHPUnit 12 requires PHP 8.3+ to run the tests ; the library itself still runs on PHP 7.4.

Class
-----

```php
use Coercive\Utility\ArrayPath\ArrayPath;

# EXAMPLE
$example_array = [
	'1' => [
		'2' => [
			'3' => [
				'content'
			]
		]
	]
];

# INIT OBJECT
$handler = ArrayPath::init($example_array);

# RETRIEVE CONTENT
$content = $handler->get('1.2.3');
$content = $handler->get('1.2.3.4', '-- null or not exist --');

# VERIFY PATH EXIST
if($handler->has('1.2.3')) {
	// ...
}

# OR get and check in same time
$content = $handler->get('1.2.3.4', null, $exist);
if(!$exist) {
	// ...
}

# SET VALUE
$handler->set('1.2.3', ['new-content']);

# DELETE PATH
$handler->delete('1.2.3');

# RESET
$handler->reset();

# OPTION : custom separator
$handler->setSeparator('@');
$content = $handler->get('1@2@3');

# NESTED ArrayAccess (ArrayObject...) are crossed too
$handler = ArrayPath::init(['a' => new ArrayObject(['b' => 'value'])]);
$content = $handler->get('a.b');

```
