<?php

namespace One234ru;

class FormInputsGenerator
{
    private $fieldValue;

    private $HTML;

    /** @var = [
     *  'type' => self::$typeDeclaration,
     *  'name' => string,
     *  'value' => '',
     *  'attr' => self::$attrDeclaration,
     *  'label' => self::$labelDeclaration,
     *  'values' => [ self::$valuesItemDeclaration ],
     *  'off_value' => 0,
     *  'multiple' => bool, // for select
     *  'options' => [ self::$optionDeclaration ],
     *  'optgroups' => [ self::$optgroupDeclaration ],
     * ]
     */
    private $config;

    /** @var = 'text checkbox radio textarea select file hidden submit reset'
     */
    private $typeDeclaration;

    /** @var = [
     * 'class' => '',
     * 'placeholder' => 'A text hint in empty field',
     * 'style' => 'CSS rules separated by ;',
     * 'rows' => 'Rows in textarea',
     * 'any-html-attribute' => '',
     * ]
     */
    private $attrDeclaration;

    /** @var = [
     *  'text' => 'Text or HTML code',
     *  'attr' => self::$attrDeclaration,
     *  'text_wrapper' => [
     *      'tag' => 'div',
     *      'attr' => [
     *      'class' => 'text-for-something'
     *  ],
     * ],
     * ]
     */
    private $labelDeclaration;

    /** @var = [
     *  'value' => '',
     *  'label' => self::$labelDeclaration,
     * ]
     */
    private $valuesItemDeclaration;

    /** @var = [
     *  'value' => '',
     *  'text' => 'Option text',
     *  'attr' => self::$attrDeclaration
     * ]
     */
    private $optionDeclaration;

    /** @var = [
     *  'attr' => [
     *      'label' => 'Group header',
     *  ],
     *  'options' => [ self::$optionDeclaration ],
     * ]
     */
    private $optgroupDeclaration;

    public function __construct(array $cfg, array $data_to_search_field_value_in)
    {
        $this->config = $cfg;

        if (isset($cfg['name'])) {
            $this->fieldValue = self::getFieldValueByName(
                $cfg['name'],
                $data_to_search_field_value_in
            );
        }
    }

    public function __toString() {
        if (is_null($this->HTML)) {
            $this->HTML = $this->generate();
        }
        return $this->HTML;
    }

    private static function getFieldValueByName(string $name, array $data_to_search_in)
    {
        if (strpos($name, '[]') !== false) {
            $msg = "Field name '$name' was given as an argument.\n"
                . "Names containing empty brackets — [] — cannot be processed, "
                . "because they do not point to a distinct value in a query.\n";
            trigger_error($msg, E_USER_WARNING);
            return null;
        }
        parse_str($name, $keys_tree);
        $values_tree = $data_to_search_in;
        while (is_array($keys_tree)) {
            $key = key($keys_tree);
            if (isset($values_tree[$key])) {
                $values_tree = $values_tree[$key];
                $keys_tree = $keys_tree[$key];
            } else {
                // $data and $name structures do not match,
                // terminating the search.
                $values_tree = null;
                break;
            }
        }
        return $values_tree;
    }

    private function generate() {
        $cfg = $this->config;
        if (
            isset($cfg['values'])
            AND
            in_array($cfg['type'] ?? '', ['checkbox', 'radio'])
        ) {
            // Multiple checkbox/radio
            $html = '';
            foreach ($cfg['values'] ?? [] as $key => $item) {
                $html .= self::createAndWrapInputHTML(
                    self::createConfigForSingleCheckboxOrRadio($cfg, $key, $item),
                    $this->fieldValue
                );
            }
        } else {
            $html = self::createAndWrapInputHTML($this->config, $this->fieldValue);
        }
        return $html;
    }

    /**
     * @param array $cfg = [
     *     'type' => 'checkbox radio',
     *     'name' => string,
     *     'off_value' => string int,
     *     // 'label' => 'Text for span near checkbox/radio inside the label',
     *     'label' => [
     *      'attr' => [],
     *      'text_wrapper' => [,
     *          'tag' => string,
     *          'attr' => [],
     *      ],
     *     ],
     * ]
     * @return array mixed
     */
    private static function createAndWrapInputHTML($cfg, $value) :string {
        $html = strval(new HTMLinputGenerator($cfg, $value));
        switch ($cfg['type'] ?? '') {
            case 'checkbox':
                if (isset($cfg['off_value'])) {
                    // Here is a trick to include an explicit value
                    // to the query when the checkbox is off:
                    // you just need to put a hidden input with the same name
                    // before the checkbox (it has to be BEFORE, not after,
                    // so the checkbox, when set, will reassign the parameter).
                    $hidden = [
                        'type' => 'hidden',
                        'value' => $cfg['off_value']
                    ];
                    if (isset($cfg['name'])) {
                        $hidden['name'] = $cfg['name'];
                    }
                    $html = (new HTMLinputGenerator($hidden)) . $html;
                }
            case 'radio':
                if (isset($cfg['label'])) {
                    $label = self::mergeLabelConfig($cfg['label']);
                    $html = new HTMLtagGenerator([
                        'tag' => $label['tag'],
                        'attr' => $label['attr'] ?? [],
                        'children' => [
                            $html,
                            $label['text_wrapper']
                        ]
                    ]);
                }
                break;
        }

        return $html;
    }

    /**
     * @return array // see @param of createAndWrapInputHTML()
     */
    private static function createConfigForSingleCheckboxOrRadio(
        $common_part,
        $item_key,
        $item_value
    ) {
    	// Merging with common part is not documented,
    	// because there are some doubts in it's necessity.
        $config = [
            'type' => $common_part['type'],
        ];
		if (isset($common_part['name'])) {
			$config['name'] = $common_part['name'];
		}
        if ($common_part['type'] == 'checkbox') {
            // For checkbox we need to add '[]' to names
            // and modify values' matching.
            $config['multiple'] = true;
        }
        if (is_array($item_value)) {
            foreach (['value', 'label'] as $key) {
                if (isset($item_value[$key])) {
                    $config[$key] = $item_value[$key];
                }
            }
            foreach (['attr'] as $key) {
				$config[$key] = ($item_value[$key] ?? [])
					+ ($common_part[$key] ?? []);
            }
        } else {
            $config += [
                'value' => $item_key,
                'label' => $item_value
            ];
        }
        if (isset($config['label'])) {
        	// There may be an explicit config 
        	// WITHOUT wrapping with <label>,
        	// so we need to check whether 'label' key is present.
			$config['label'] = self::mergeLabelConfig(
				$config['label'] ?? [],
				$common_part['label'] ?? []
			);
		}
        return $config;
    }

    private static function mergeLabelConfig($custom, $common = []) {
    	$default = [
			'tag' => 'label',
			'text_wrapper' => [
				'tag' => 'span',
			]
		];
    	if (!is_array($custom)) {
    		$custom = [
				'text' => $custom
    		];
    	}
		$config = [];
		foreach (['text', 'tag'] as $key) {
			// These elements may store only strings,
			// so everything is simple here.
			$config[$key] = $custom[$key] 
				?? $common[$key]
				?? $default[$key]
				?? false;
		}
		foreach (['text_wrapper', 'attr'] as $key) {
			// These elements contain arrays
			// which we add to keep values from all of them
			$config[$key] = ($custom[$key] ?? []) 
				+ ($common[$key] ?? [])
				+ ($default[$key] ?? []);
			// Later add support for callback function
			// which accepts common and custom values
			// and returns something so it is possible, for example,
			// merge two style strings into one (currently
			// custom 'style' attr will replace common one).
		}
		$config = array_filter($config);
		if (isset($config['text'])) {
			// Attention! Overriding default behaviour 
			// of 'text' key for HTMLtagGenerator.
			$config['text_wrapper']['text'] = $config['text'];
			unset($config['text']);
		}
		return $config;
		// if (is_array($custom)) { ...
        // } elseif (is_callable($custom)) {
        //     return $custom;
        // } else {
        //     $msg = "Incorrect config for label given: " . print_r($custom, 1);
        //     trigger_error($msg, E_USER_WARNING);
        //     return null;
        // }
    }

}