<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Model for Form_Attributes
 * 
 * @author     Ushahidi Team <team@ushahidi.com>
 * @package    Ushahidi\Application\Models
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License Version 3 (GPLv3)
 */

class Model_Form_Attribute extends ORM {
	/**
	 * An attribute has and belongs to many forms
	 * An attribute has and belongs to many form_groups
	 *
	 * @var array Relationships
	 */
	protected $_has_many = array(
		'form_groups' => array('through' => 'form_groups_form_attributes'),
		);
		
	protected $_serialize_columns = array('options');
		
	/**
	 * Reserved attribute keys to avoid confusion with Posts table columns
	 * 
	 * @var array key names
	 */
	protected $_reserved_keys = array(
		'slug',
		'type',
		'title',
		'content',
		'created',
		'updated',
		'email',
		'author',
		'form_id',
		'parent_id',
		'user_id',
		'status',
		'id',
		'tags',
		'values'
	);

	/**
	 * Rules for the form_attribute model
	 *
	 * @return array Rules
	 */
	public function rules()
	{
		return array(
			'form_id' => array(
				array('numeric'),
			),
			'form_group_id' => array(
				array('numeric'),
			),
			'key' => array(
				array('not_empty'),
				array('max_length', array(':value', 150)),
				array(array($this, 'unique'), array(':field', ':value')),
				array(array($this, 'not_reserved'), array(':field', ':value'))
			),
			'label' => array(
				array('not_empty'),
				array('max_length', array(':value', 150))
			),
			'input' => array(
				array('not_empty'),
				array('in_array', array(':value', array(
					'text',
					'textarea',
					'select',
					'radio',
					'checkbox',
					'file',
					'date',
					'location'
				)) )
			),
			'type' => array(
				array('not_empty'),
				array('in_array', array(':value', array(
					'decimal',
					'int',
					'geometry',
					'text',
					'varchar',
					'point',
					'datetime',
					'link'
				)) )
			),
			'required' => array(
				array('in_array', array(':value', array(true,false)))
			),
			'priority' => array(
				array('numeric')
			),
			'cardinality' => array(
				array('numeric')
			)
		);
	}

	/**
	 * Callback function to check if field key is reserved
	 */
	public function not_reserved($field, $value)
	{
		return ! in_array($field, $this->_reserved_keys);
	}

	/**
	 * Prepare attribute data for API
	 * 
	 * @return array $response - array to be returned by API (as json)
	 */
	public function for_api()
	{
		$response = array();
		if ( $this->loaded() )
		{
			$response = array(
				'id' => $this->id,
				'url' => URL::site('api/v'.Ushahidi_Api::version().'/attributes/'.$this->id, Request::current()),
				'key' => $this->key,
				'label' => $this->label,
				'input' => $this->input,
				'type' => $this->type,
				'required' => ($this->required) ? TRUE : FALSE,
				'default' => $this->default,
				'priority' => $this->priority,
				'options' => $this->options,
				'cardinality' => $this->cardinality
			);
		}
		else
		{
			$response = array(
				'errors' => array(
					'Attribute does not exist'
					)
				);
		}

		return $response;
	}
}