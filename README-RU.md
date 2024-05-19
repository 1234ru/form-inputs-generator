**[IN ENGLISH](README.md)**


# Генерация HTML-кода полей форм со вспомогательными тегами и автоматической подстановкой значений из массива

Данный класс является обёрткой для [`HTMLinputGenerator`](/one234ru/html-input-generator) и решает две важных задачи:

1. Автоматическое получение значения для подстановки в поле на основании его имени из массива данных.
Таким массивом обычно служат данные редактируемой сущности или параметры HTTP-запроса.

2. Генерация вспомогательных HTML-элементов.
`HTMLinputGenerator` *всегда генерирует поле в виде одиночного HTML-тега*, тогда как в ряде случаев удобней иметь поле в виде более сложной структуры. К таким случаям относятся:  
   * `<input type="checkbox/radio">`, обёрнутые в `<label>`
   * группа `<input type="checkbox/radio">` с одинаковым `name`
   * явное значение при выключенном `<input type="checkbox">` 


## Установка

```shell
composer require one234ru/form-inputs-generator
```


## Использование

Для получения HTML-кода нужно создать объект класса на основе конфигурации и массива для подстановки, после чего привести объект к строке.

Конфигурация является *расширением* таковой для `HTMLinputGenerator` и в общем случае дает идентичный HTML:

```php
$config = [
    'type' => 'text',
    'name' => 'something',
];
$obj_1 = new One234ru\HTMLinputGenerator($config, $_GET['something'] ?? '');
$obj_2 = new One234ru\FormInputsGenerator($config, $_GET); 
var_dump( strval($obj_1) === strval($obj_2) ); // true
```

*Специальные режимы включаются только при наличии в конфигурации определенных ключей.* Все такие случаи перечислены ниже. 


### Оборачивание `<input type = "checkbox/radio">` в `<label>`

Возможности стилизации внешнего вида чекбоксов и радиокнопок с помощью CSS очень скудны и зачастую недостаточны для получения желаемого внешнего вида. Из-за этого при вёрстке применяется такой приём: поле оборачивают в тег `<label>` и скрывают, а рядом размещают текст внутри `<span>` или `<div>`, которому в стилях указывают фоновое изображение, выровненное по левому краю — оно и служит визуальной имитацией поля (CSS позволяет менять стили в зависимости от состояния поля - отмечено или снято; пример см. в [pseudocheckbox.html](pseudocheckbox.html)).

Кроме того, оборачивание текста в `<label>` делает его кликабельным для снятия/установки флажка на поле, что повышает удобство пользователя.

В общем, нужна примерно такая структура:

```html
<label>
	<input type="checkbox" name="something" value="1">
	<span>Что-то</span>
</label>
```

Получить её легко: в конфигурацию поля нужно просто добавить элемент `label`:

```php
$config = [
	'type' => 'checkbox',
	'name' => 'something',
	'value' => 1,
	'label' => 'Что-то'
];
```

Возможна и более тонкая настройка. Для этого в качестве `label` нужно передать массив:

```php
$config = [
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
		'text' => '<b>Что-то</b>',
	]
];
```

Результат (отформатирован для удобочитаемости):

```html
<label class="label-for-something">
	<input type="checkbox" name="something" value="1">
	<div class="text-for-something">
		<b>Что-то</b>
	</div>
</label>
```

Две следующие конфигурации равносильны:

```php
	'label' => 'Что-то'
```
```php
	'label' => [
		'text' => 'Что-то'
	]
```

Аналогично можно действовать и с `<input type="radio">`.

При значениях `type`, отличных от `'checkbox'` и `'radio'`, ключ `label` не окажет на результат никакого влияния. 


### Группа `<input type="checkbox/radio">` с одинаковым `name`

Режим генерации группы однотипных полей включается при наличии ключа `values`:

```php
$config = [
	'type' => 'radio',
	'name' => 'something',
	'values' => [
		1 => 'Раз', 
		[
			'value' => 2,
			'label' => 'Два', 
		],
		[
			'value' => 3,
			'label' => [
				'attr' => [
					'class' => 'special-label',
				],
				'text' => 'Три', 
			],
		],
		[
			'value' => 4,
		]
	]
];
```

Результат (отформатирован для удобочитаемости):

```html
<label>
	<input type="radio" name="something" value="1">
	<span>Раз</span>
</label>
<label>
	<input type="radio" name="something" value="2">
	<span>Два</span>
</label>
<label class="special-label">
	<input type="radio" name="something" value="3">
	<span>Три</span>
</label>
<input type="radio" name="something" value="4">
```

Все поля, кроме последнего, обёрнуты в `<label>`. Ключи массива `label` каждого из элемента `values` действуют ровно так, как и в случае одиночного поля.

У первого поля конфигурацией является не массив, а пара ключ-значение — `1 => 'Раз'`. Такая запись аналогична массиву

```php
[
	'value' => 1,
	'label' => 'Раз'
]
```

У четвертого поля обёртка `<label>` отсутствует. Добиться этого можно, если конфигурацию поля указать в виде массива, опустив при этом атрибут `label`.

Стандартные ключи (в частности, `attr` и `label`) можно указать на верхнем уровне, и они унаследуются всеми членами списка. При этом для каждого конкретного поля их можно переопределить:

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
		1 => 'Раз', 
		[
			'value' => 2,
			'label' => 'Два', 
		],
		[
			'value' => 3,
			'label' => [
				'attr' => [
					'class' => 'special-label', // Переопределит настройку верхнего уровня
				],
				'text' => 'Три', 
			],
		],
		[
			'attr' => [
				'class' => 'special-input', // Переопределит настройку верхнего уровня
			],
			'value' => 4,
		]
	]
];
```
```html
<label class="standard-label">
	<input type="radio" name="something" value="1" class="standard-input">
	<span>Раз</span>
</label>
<label class="standard-label">
	<input type="radio" name="something" value="2" class="standard-input">
	<span>Два</span>
</label>
<label class="special-label">
	<input type="radio" name="something" value="3" class="standard-input">
	<span>Три</span>
</label>
<input type="radio" name="something" value="4" class="special-input">
```

В случае `type="checkbox"` атрибуту `name` в конец дописываются пустые квадратные скобки:

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
	<span>Раз</span>
</label>
<label>
	<input type="checkbox" name="something[]" value="2">
	<span>Два</span>
</label>
<label class="special-label">
	<input type="checkbox" name="something[]" value="3">
	<span>Три</span>
</label>
<input type="checkbox" name="something[]" value="4">
```

Это необходимо для передачи в HTTP-запрос нескольких значений из одного поля. Дописывать скобки в `name` самостоятельно не следует. 

При `type`, не равном `'checkbox'` или `'radio'`, ключ `values` игнорируется.


### Явное значение при выключенном `<input type="checkbox">`

Чекбоксы проявляются в содержимом HTTP-запроса, только если они отмечены. В противном случае соответствующий ключ в запросе отсутствует, и на сервере нужно реализовывать логику типа «если не получили "Да", рассматриваем это как "Нет"». 

Бывает удобней получить "Нет" в явном виде. Для этого применяется следующий трюк: чекбокс дополняется скрытым полем с таким же `name`, а его `value` содержит значение для случая "Нет". В HTML-коде это поле должно следовать строго *до* чекбокса:

```html
<input type="hidden" name="something" value="0">
<input type="checkbox" name="something" value="1">
```

В результате, если чекбокс будет отмечен, его значение перекроет в запросе значение скрытого поля.   

За режим генерации такого скрытого поля отвечает ключ `off_value`, где нужно указать его значение. HTML-коду из примера выше соответствует такая конфигурация:

```php
[
	'type' => 'checkbox',
	'name' => 'something',
	'value' => 1,
	'off_value' => 0
]
```

`off_value` можно сочетать с `label` и другими стандартными ключами:

```php
[
	'type' => 'checkbox',
	'name' => 'something',
	'value' => 1,
	'off_value' => 0,
	'label' => 'Что-то',
	'attr' => [
		'class' => 'some-class',
	]
]
```
```html
<label>
	<input type="hidden" name="something" value="0">
	<input type="checkbox" name="something" value="1" class="some-class">
	<span>Что-то</span>
</label>
```

При `type`, отличном от `'checkbox'`, ключ `off_value` игнорируется.