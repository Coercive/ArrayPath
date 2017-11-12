Coercive ArrayPath
==================

- Cross a table like a file path.

Get
---
```
composer require coercive/arraypath
```

Class
-----
```php
use Coercive\Utility\ArrayPath\ArrayPath;

# EXAMPLE
$aArray = [
	'1' => [
		'2' => [
			'3' => [
				'content'
			]
		]
	]
];

# INIT OBJECT
$oArray = ArrayPath::init($aArray);

# RETRIEVE CONTENT
$aContent = $oArray->get('1.2.3');

# OPTION : custom separator
$oArray->setSeparator('@');
$aContent = $oArray->get('1@2@3');

# VERIFY PATH EXIST
if($oArray->has('1.2.3')) {
	// do something
}

# SET VALUE
$oArray->set('1.2.3', ['new-content']);

# DELETE PATH
$oArray->delete('1.2.3');

# RESET
$oArray->reset();

```
