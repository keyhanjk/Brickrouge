<?php

/*
 * This file is part of the Brickrouge package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brickrouge\Validate;

use Brickrouge\Element;
use Brickrouge\Form;
use Brickrouge\Group;
use Brickrouge\Validate\FormValidator\ValidateValues;

use function Brickrouge\array_flatten;
use function Brickrouge\t;

class FormValidator
{
	/**
	 * @var Form
	 */
	private $form;

	/**
	 * @var ValidateValues|callable|null
	 */
	private $validate_values;

	/**
	 * @param Form $form
	 * @param ValidateValues|callable|null $validation
	 */
	public function __construct(Form $form, callable $validation = null)
	{
		$this->form = $form;
		$this->validate_values = $validation;
	}

	/**
	 * Validate values.
	 *
	 * @param array $values
	 * @param ErrorCollection|null $errors
	 *
	 * @return ErrorCollection
	 */
	public function validate(array $values, ErrorCollection $errors = null)
	{
		#
		# we flatten the array so that we can easily get values
		# for keys such as `cars[1][color]`
		#

		$values = array_flatten($values);

		if (!$errors)
		{
			$errors = new ErrorCollection;
		}

		#

		$elements = $this->collect_elements();

		$required = $this->filter_required_elements($elements);
		$this->validate_required($required, $values, $errors);

		$rules = $this->collect_rules($elements, $required, $values, $errors);
		$this->validate_values($values, $rules, $errors);

		return $errors;
	}

	/**
	 * Collect required or with validation elements.
	 *
	 * @return Element[]
	 */
	protected function collect_elements()
	{
		$elements = [];

		foreach ($this->form as $element)
		{
			if ($element[Element::REQUIRED] || $element[Element::VALIDATION])
			{
				$elements[] = $element;
			}
		}

		return $elements;
	}

	/**
	 * Filter required elements.
	 *
	 * @param array $elements
	 *
	 * @return array
	 */
	protected function filter_required_elements(array $elements)
	{
		$required = [];

		foreach ($elements as $element)
		{
			if ($element[Element::REQUIRED])
			{
				$required[$element['name']] = $element;
			}
		}

		return $required;
	}

	/**
	 * Validates required elements.
	 *
	 * @param array $required
	 * @param array $values
	 * @param ErrorCollection $errors
	 */
	protected function validate_required(array $required, array $values, ErrorCollection $errors)
	{
		$missing = [];

		foreach ($required as $name => $element)
		{
			if (!isset($values[$name])
			|| (isset($values[$name]) && is_string($values[$name]) && !strlen(trim($values[$name]))))
			{
				$missing[$name] = $this->resolve_label($element);
			}
		}

		if (!$missing)
		{
			return;
		}

		if (count($missing) == 1)
		{
			$errors->add(key($missing), "The field %field is required!", [

				'%field' => current($missing)

			]);

			return;
		}

		foreach ($missing as $name => $label)
		{
			$errors->add($name, true);
		}

		$last = array_pop($missing);

		$errors->add($errors::BASE, "The fields %list and %last are required!", [

			'%list' => implode(', ', $missing),
			'%last' => $last

		]);
	}

	/**
	 * Collect validation rules from elements.
	 *
	 * @param Element[] $elements
	 * @param Element[] $required
	 * @param array $values
	 * @param ErrorCollection $errors
	 *
	 * @return array
	 */
	protected function collect_rules(array $elements, array $required, array $values, ErrorCollection $errors)
	{
		$rules = [];

		foreach ($elements as $element)
		{
			$name = $element['name'];

			if (!$element[Element::VALIDATION] || isset($errors[$name]))
			{
				continue;
			}

			$value = isset($values[$name]) ? $values[$name] : null;

			if (($value === null || $value === '') && empty($required[$name]))
			{
				continue;
			}

			$rules[$name] = $element[Element::VALIDATION];
		}

		return $rules;
	}

	/**
	 * @param Element $element
	 *
	 * @return string|null
	 */
	protected function resolve_label(Element $element)
	{
		$label = $element[Element::LABEL_MISSING]
			?: $element[Group::LABEL]
			?: $element[Element::LABEL]
			?: $element[Element::LEGEND]
			?: null;

		if (!$label)
		{
			return null;
		}

		#
		# remove HTML markups from the label
		#

		$label = t($label, [], [ 'scope' => 'element.label' ]);
		$label = strip_tags($label);

		return $label;
	}

	/**
	 * Validate values against a set of rules.
	 *
	 * @param array $values
	 * @param array $rules
	 * @param ErrorCollection $errors
	 */
	protected function validate_values(array $values, array $rules, ErrorCollection $errors)
	{
		$validate_values = $this->validate_values;
		$validate_values($values, $rules, $errors);
	}
}
