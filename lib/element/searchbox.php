<?php

/*
 * This file is part of the BrickRouge package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BrickRouge;

class Searchbox extends Element
{
	private $elements=array();

	public function __construct($tags)
	{
		parent::__construct
		(
			'div', wd_array_merge_recursive
			(
				array
				(
					self::T_CHILDREN => array
					(
						'q' => $this->elements['q'] = new Text(),

						$this->elements['trigger'] = new Button
						(
							'Search', array
							(
								'type' => 'submit'
							)
						)
					),

					'class' => 'widget-searchbox'
				),

				$tags
			)
		);
	}

	public function set($property, $value=null)
	{
		if (is_string($property))
		{
			if (in_array($property, array('name', 'value', 'placeholder')))
			{
				$this->elements['q']->set($property, $value);
			}
		}

		return parent::set($property, $value);
	}
}