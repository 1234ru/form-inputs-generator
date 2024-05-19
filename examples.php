<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/FormInputsGenerator.php';

$config = [
	'type' => 'checkbox',
	'name' => 'something',
	'value' => 1,
];

switch ($case = ($_GET['case'] ?? 'label') ) {
	case 'label':
		$config['label'] = 'This is something'; // Что-то
		break;
	case 'label_full':
		$config['label'] = [
			'attr' => [
				'class' => 'label-for-something',
			],
			'text' => '<b>Что-то</b>',
			'text_wrapper' => [
				'tag' => 'div',
				'attr' => [
					'class' => 'text-for-something'
				],
			],
		];
		break;
	case 'mr': case 'mc':
		$config = [
			'type' => ($case == 'mc' ? 'checkbox' : 'radio'),
			'name' => 'something',
			'attr' => [
				'class' => 'special-input',
			],
			'label' => [
				'attr' => [
					'class' => 'special-label',
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
							'class' => 'label-for-three', // Переопределит настройку верхнего уровня
						],
						'text' => 'Три', 
					],
				],
				[
					'attr' => [
						'class' => 'standalone-input', // Переопределит настройку верхнего уровня 
					],
					'value' => 4,
				]
			]
		];
		break;
	case 'off_value_2':
		$config += [
			'label' => 'Something', // Что-то
			'attr' => [
				'class' => 'some-class',
			]
		];
	case 'off_value':
		$config['off_value'] = 0;
		break;
}

$values = $_GET;

$html = new One234ru\FormInputsGenerator($config, $values);

echo "<form style='padding-bottom: 1em; margin-bottom: 1em; border-bottom: 1px solid;'>"
    . '<input type="hidden" name="case" value="' . $case .'">'
    . "<p style='font-weight: bold'>HTML:</p>"
    . "<div style='padding: 1em; background: #F8f8f8'>\n$html\n</div>"
    . '<button>Send</button><input type="reset" value="Reset">'
    . "<p style='font-weight: bold'>HTML source:</p>"
    . "<pre style='padding: 1em; background: #F8f8f8'>\n" . htmlspecialchars($html) ."\n</pre>"
    . "<p>Config:</p>"
    . "<pre style='padding: 1em; background: #F8f8f8'>"
    . htmlspecialchars(var_export($config, true))
    . "</pre>"
    . "</form>";