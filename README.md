**[ПО-РУССКИ](README-RU.md)**


# Generation of HTML form fields with supplementary tags 

This class is a wrapper for [`HTMLinputGenerator`](/one234ru/html-input-generator). It solves two important problems:

1. Looks for field's value using it's name in an array.
This array may store, for example, data of some entity being edited or parameters of an HTTP query.

2. Generation of supplementary HTML tags.
`HTMLinputGenerator` *always generates single HTML element*, while in some cases it is handily to have a field as more complicated structure. These cases are:
   * `<input type="checkbox/radio">` wrapped in `<label>`
   * a group of `<input type="checkbox/radio">` with the same `name`
   * explicit value when `<input type="checkbox">` is unchecked


## Installation

```shell
composer require one234ru/form-inputs-generator
```


## Usage

To obtain an HTML code, you need to create an object based on configuration and data array and then convert it to string.

Configuration is   *extension* of the one for `HTMLinputGenerator` and commonly yields identical HTML: 

```php
$config = [
    'type' => 'text',
    'name' => 'something',
];
$obj_1 = new One234ru\HTMLinputGenerator($config, $_GET['something'] ?? '');
$obj_2 = new One234ru\FormInputsGenerator($config, $_GET); 
var_dump( strval($obj_1) === strval($obj_2) ); // true
```

*Special modes are only activated if certain keys are present in the configuration.* All such cases are listed below.


### Wrapping `<input type = "checkbox/radio">` in `<label>`

CSS capabilities for checkboxes and radiobuttons are very poor and often insufficient to get desired appearance.  
The workaround is to wrap a field with `<label>` tag, hide the field itself and add it's description right after, wrapped in `<span>` or `<div>` with background image. That image serves as a visual equvalent of the field (CSS allows to adjust styles depending on whether the field is checked or not; see an example in [pseudocheckbox.html](pseudocheckbox.html)).  

Moreso, wrapping text in a `<label>` makes it clickable for checking/unchecking the field, which improves user experience.

All of the above leads to a structure like:

```html
<label>
	<input type="checkbox" name="something" value="1">
	<span>Something</span>
</label>
```

This structure is easy to obtain — just add `label` element to the configuration:

```php
$config = [
	'type' => 'checkbox',
	'name' => 'something',
	'value' => 1,
	'label' => 'Something'
];
```

Fine tuning is done through passing an array as `label`:

```php
[
	...
	'label' => [
		'attr' => [
			'class' => 'label-for-something',
		],
		'text_wrapper' => [
			'tag' => 'div',
			'attr' => [
				'class' => 'text-for-something'
			],
		],
		'text' => '<b>Something</b>',
	]
]
```

Result (formatted for readability):

```html
<label class="label-for-something">
	<input type="checkbox" name="something" value="1">
	<div class="text-for-something">
		<b>Something</b>
	</div>
</label>
```

Two following configurations yield the same result:

```php
	'label' => 'Something'
```
```php
	'label' => [
		'text' => 'Something'
	]
```

Same techique is applicable to `<input type="radio">`.

For `type`, different than `'checkbox'` or `'radio'`, specifying `label` does not affect anything.


### A group of `<input type="checkbox/radio">` with the same `name`

Group generation mode is turned on by `value` parameter:

```php
$config = [
	'type' => 'radio',
	'name' => 'something',
	'values' => [
		1 => 'One', 
		[
			'value' => 2,
			'label' => 'Two', 
		],
		[
			'value' => 3,
			'label' => [
				'attr' => [
					'class' => 'special-label',
				],
				'text' => 'Three', 
			],
		],
		[
			'value' => 4,
		]
	]
];
```

Result (formatted for readability):

```html
<label>
	<input type="radio" name="something" value="1">
	<span>One</span>
</label>
<label>
	<input type="radio" name="something" value="2">
	<span>Two</span>
</label>
<label class="special-label">
	<input type="radio" name="something" value="3">
	<span>Three</span>
</label>
<input type="radio" name="something" value="4">
```

All fields, except last, are wrapped in `<label>`. Keys of every element in `label` array has same effect as in the case of single field described above.

First field's configuration is a key-value pair — `1 => 'One'`. This is equivalent of the array:

```php
[
	'value' => 1,
	'label' => 'One'
]
```

The fourth field doesn't have any wrapper. This may be achieved by specifying configuration as an array without `label` attribute.

Standard keys (like `attr` and `label`) may be specified at the top level — they will be inherited by all `values` members and may be redefined for every one in particular:

```php
$config = [
	'type' => 'radio',
	'name' => 'something',
	'attr' => [
		'class' => 'standard-input',
	],
	'label' => [
		'attr' => [
			'class' => 'standard-label',
		],
	], 
	'values' => [
		1 => 'One', 
		[
			'value' => 2,
			'label' => 'Two', 
		],
		[
			'value' => 3,
			'label' => [
				'attr' => [
					'class' => 'special-label', // Will override top-level value
				],
				'text' => 'Three', 
			],
		],
		[
			'attr' => [
				'class' => 'special-input', // Will override top-level value
			],
			'value' => 4,
		]
	]
];
```
```html
<label class="standard-label">
	<input type="radio" name="something" value="1" class="standard-input">
	<span>One</span>
</label>
<label class="standard-label">
	<input type="radio" name="something" value="2" class="standard-input">
	<span>Two</span>
</label>
<label class="special-label">
	<input type="radio" name="something" value="3" class="standard-input">
	<span>Three</span>
</label>
<input type="radio" name="something" value="4" class="special-input">
```

In the case of `type="checkbox"` empty square brackets are appended to `name` attribute's value:

```php
$config = [
	'type' => 'checkbox',
	'name' => 'something',
	'values' => ...
];
```
```html
<label>
	<input type="checkbox" name="something[]" value="1">
	<span>One</span>
</label>
<label>
	<input type="checkbox" name="something[]" value="2">
	<span>Two</span>
</label>
<label class="special-label">
	<input type="checkbox" name="something[]" value="3">
	<span>Three</span>
</label>
<input type="checkbox" name="something[]" value="4">
```

For any `type`, other than `'checkbox'` or `'radio'`, `values` parameter is ignored.


### Explicit value when `<input type="checkbox">` is unchecked

Checkboxes affect HTTP query only when checked. Otherwise corresponding key is just absent, and on the receiving end suggestions like "if there is no explicit 'Yes' — treat this as 'No'" have to be made.

It may be more convenient to have 'No' in explicit form. This is achieved with the trick: the checkbox is *prepended by* a hidden field with the same `name` and a `value` corresponding to the 'No' variant:

```html
<input type="hidden" name="something" value="0">
<input type="checkbox" name="something" value="1">
```

If the checkbox is checked, it's value will override hidden field's value in the query.

Generation of such hidden field is turned on by `off_value` parameter, which holds the actual value. HTML code in the example above corresponds to the following configuration: 

```php
[
	'type' => 'checkbox',
	'name' => 'something',
	'value' => 1,
	'off_value' => 0
]
```

`off_value` works fine with `label` and other stanard parameters:

```php
[
	'type' => 'checkbox',
	'name' => 'something',
	'value' => 1,
	'off_value' => 0,
	'label' => 'Something', 
	'attr' => [
		'class' => 'some-class',
	]
]
```
```html
<label>
	<input type="hidden" name="something" value="0">
	<input type="checkbox" name="something" value="1" class="some-class">
	<span>Something</span>
</label>
```

If `type` is not equal to `'checkbox'`, `off_value` has no effect.