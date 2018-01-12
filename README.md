# php-simple-lint

php-simple-lint, extends phpcs easily.

## Usage

php `lint.php` `rule/rule.sample.json` `phpcs`

> php lint.php rule/rule.sample.json vendor/bin/phpcs

## Matching  process

```php
namespace N;
use U;
class ABC extends A implements B, C
{
  const THE_CONST=1;
  public $property=1;
  public function someNethod($a, $b){
    $property = 2;
  }
}
```

Serialize to

```php
['type' => 'use', 'clause' => 'namespace N use U'],
['type' => 'class', 'clause' => 'namespace N class ABC extends A implements B implements C'],
['type' => 'const', 'clause' => 'namespace N class ABC extends A implements B implements C { public THE_CONST'],
['type' => 'property', 'clause' => 'namespace N class ABC extends A implements B implements C { public $property'],
['type' => 'param', 'clause' => 'namespace N class ABC extends A implements B implements C { public function someMethod ( int $a'],
['type' => 'param', 'clause' => 'namespace N class ABC extends A implements B implements C { public function someMethod ( bool $b'],
['type' => 'function', 'clause' => 'namespace N class ABC extends A implements B implements C { public function someMethod ( )'],
['type' => 'var', 'clause' => 'namespace N function someMethod ( ) { $property'],
```

Match with

```json
[
  {
    "type": "class",
    "must": "class [\\w]+Model",
    "if": "extends\\s+PlatformBaseModel",
    "reason": "Extends PlatformBaseModel must postfix Model"
  },
  {
    "type": "var",
    "must": "\\$[a-z0-9_]+",
    "reason": "var must lowercase with underbar"
  },
  {
    "type": "param",
    "must": "\\$[a-z0-9_]+",
    "reason": "param must lowercase with underbar"
  },
]

```

Matched result

```
"<error line='{$line_pos}' column='{$column_pos}'  source='RIDI.LINT' severity='5' fixable='1'>{$reason}</error>"
```

## rule.json

- type (required)
  -  `use`, `class`, `const`, `property`, `param`, `function`, `var`
- must (or `must not`) (required)
  - regexes
- if (optional) (string or string[])
  - regexes
- if not (optional) (string or string[])
  - regexes

## Rule case study

````
[
  {
    "type": "use",
    "must": "use .+External.+",
    "if": [
      "namespace Calsule"
    ],
    "if not" : [
      "namespace SafeZoneA",
      "namespace SafeZoneB"
    ]
    "reason": "`Capsuled namspace` must not use `External` namespace"
  },
  {
    "type": "class",
    "must": "class [\\w]+Model",
    "if": "extends\\s+BaseModel",
    "reason": "`Extends BaseModel` must postfix `Model`"
  },
  {
    "type": "var",
    "must": "\\$[a-z0-9_]+",
    "reason": "var must lowercase with underbar"
  },
  {
    "type": "param",
    "must": "\\$[a-z0-9_]+",
    "reason": "param must lowercase with underbar"
  },
  {
    "type": "property",
    "must": "\\$[a-z0-9_]+",
    "reason": "property must lowercase with underbar"
  },
  {
    "type": "const",
    "must": "[A-Z0-9_]+",
    "reason": "const must uppercase"
  },
  {
    "type": "class",
    "must": "class [A-Z][a-zA-Z0-9]+",
    "reason": "class must camel cap"
  },
  {
    "type": "class",
    "must not": "class .+Object",
    "reason": "class must not ends with `Object`"
  },
  {
    "type": "function",
    "must": "function [a-z][a-zA-Z0-9]+",
    "reason": "function must camel"
  }
]
````