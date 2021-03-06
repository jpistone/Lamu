<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Model for Sets
 *
 * PHP version 5
 * LICENSE: This source file is subject to GPLv3 license
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/gpl.html
 * @author     Ushahidi Team <team@ushahidi.com>
 * @package    Ushahidi - http://source.ushahididev.com
 * @subpackage Models
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License Version 3 (GPLv3)
 */

class Model_Set extends ORM {
	/**
	 * A set has and belongs to many posts
	 *
	 * @var array Relationships
	 */
	protected $_has_many = array(
		'posts' => array('through' => 'posts_sets'),
		);

	/**
	 * A set belongs to a user
	 *
	 * @var array Relationships
	 */
	protected $_belongs_to = array(
		'user' => array()
		);

	/**
	 * Rules for the set model
	 *
	 * @return array Rules
	 */
	public function rules()
	{
		return array(
			'user_id' => array(
				array('numeric'),
				array(array($this, 'user_exists'), array(':field', ':value'))
			),
			
			//Name of set
			'name' => array(
				array('not_empty'),
				array('min_length', array(':value', 3)),
				array('max_length', array(':value', 255))
			)
		);
			
	}

	/**
	 * Callback function to check if user exists
	 */
	public function user_exists($field, $value)
	{
		$user = ORM::factory('User')
				->where('id', '=', $value)
				->find();

		return $user->loaded();
	}


	// Insert/Update Timestamps
	protected $_created_column = array('column' => 'created', 'format' => TRUE);
	protected $_updated_column = array('column' => 'updated', 'format' => TRUE);

	/**
	 * Prepare set data for API, along with all its 
	 * groups and attributes
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
				'url' => URL::site('api/v'.Ushahidi_Api::version().'/sets/'.$this->id, Request::current()),
				'name' => $this->name,
				'filter' => $this->filter,
				'user' => empty($this->user_id) ? NULL : array(
					'id' => $this->user_id,
					'url' => URL::site('api/v'.Ushahidi_Api::version().'/users/'.$this->user_id, Request::current())
				),

				'created' => ($created = DateTime::createFromFormat('U', $this->created))
					? $created->format(DateTime::W3C)
					: $this->created,
				'updated' => ($updated = DateTime::createFromFormat('U', $this->updated))
					? $updated->format(DateTime::W3C)
					: $this->updated,
				);

		}
		else
		{
			$response = array(
				'errors' => array(
					'Set does not exist'
					)
				);
		}

		return $response;
	}

}
