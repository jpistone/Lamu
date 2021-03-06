<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Ushahidi API Sets Controller
 *
 * PHP version 5
 * LICENSE: This source file is subject to GPLv3 license
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/gpl.html
 * @author     Ushahidi Team <team@ushahidi.come
 * @package    Ushahidi - http://source.ushahididev.com
 * @subpackage Controllers
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License Version 3 (GPLv3)
 */

class Controller_Api_Sets extends Ushahidi_Api {

	/**
	 * @var string Field to sort results by
	 */
	 protected $record_orderby = 'created';

	/**
	 * @var string Direct to sort results
	 */
	 protected $record_order = 'DESC';
	
	/**
	 * @var int Maximum number of results to return
	 */
	 protected $record_allowed_orderby = array('id', 'created', 'name');

	/**
	 * Create A Set
	 * 
	 * POST /api/sets
	 * 
	 * @return void
	 */
	public function action_post_index_collection()
	{
		$post = $this->_request_payload;
		
		$set = ORM::factory('Set');

		$this->create_or_update_set($set, $post);
	}

	/**
	 * Retrieve All Sets
	 * 
	 * GET /api/sets
	 * 
	 * @return void
	 */
	public function action_get_index_collection()
	{
		$results = array();

		$this->prepare_order_limit_params();

		$sets_query = ORM::factory('Set')
				->order_by($this->record_orderby, $this->record_order)
				->offset($this->record_offset)
				->limit($this->record_limit);

		//Prepare search params
		$q = $this->request->query('q');
		if (! empty($q))
		{
			$sets_query->where('name', 'LIKE', "%$q%");
		}

		$set = $this->request->query('name');
		if (! empty($set))
		{
			$sets_query->where('name', '=', $set);	
		}

		$user = $this->request->query('user');
		if(! empty($user))
		{
			$sets_query->where('user_id', '=', $user);	
		}

		$sets = $sets_query->find_all();

		$count = $sets->count();

		foreach ($sets as $set)
		{
			$results[] = $set->for_api();
	
		}	

		// Current/Next/Prev urls
		$params = array(
				'limit' => $this->record_limit,
				'offset' => $this->record_offset,
		);

		// Only add order/orderby if they're already set 
		if ($this->request->query('orderby') OR $this->request->query('order'))
		{
			$params['orderby'] = $this->record_orderby;
			$params['order'] = $this->record_order;	
		}

		$prev_params = $next_params = $params;
		$next_params['offset'] = $params['offset'] + $params['limit'];
		$prev_params['offset'] = $params['offset'] - $params['limit'];
		$prev_params['offset'] = $prev_params['offset'] > 0 ? $prev_params['offset'] : 0;

		$curr = URL::site($this->request->uri() . URL::query($params), $this->request);
		$next = URL::site($this->request->uri() . URL::query($next_params), $this->request);
		$prev = URL::site($this->request->uri() . URL::query($prev_params), $this->request);

		//Respond with Sets
		$this->_response_payload = array(
				'count' => $count,
				'results' => $results,
				'limit' => $this->record_limit,
				'offset' => $this->record_offset,
				'order' => $this->record_order,
				'orderby' => $this->record_orderby,
				'curr' => $curr,
				'next' => $next,
				'prev' => $prev,
		);

	}	

	/**
	 * Retrieve A Set
	 * 
	 * GET /api/sets/:id
	 * 
	 * @return void
	 */
	public function action_get_index()
	{
		$set_id = $this->request->param('id', 0);

		// Respond with set
		$set = ORM::factory('Set', $set_id);

		if (! $set->loaded() )
		{
			throw new HTTP_Exception_404('Set does not exist. Set ID \':id\'', array(
				':id' => $set_id,
			));
		}

		$this->_response_payload = $set->for_api();
	}


	/**
	 * Update A Set
	 * 
	 * PUT /api/sets/:id
	 * 
	 * @return void
	 */
	public function action_put_index()
	{
		$set_id = $this->request->param('id', 0);
		$post = $this->_request_payload;
		
		$set = ORM::factory('Set', $set_id)->values($post, array(
			'name', 'filter'
			));

		if (! $set->loaded() )
		{
			throw new HTTP_Exception_404('Set does not exist. Set ID \':id\'', array(
				':id' => $set_id,
			));
		}

		$this->create_or_update_set($set, $post);
		
	}

	/**
	 * Delete A Set
	 * 
	 * DELETE /api/sets/:id
	 * 
	 * @return void
	 * @todo Authentication
	 */
	public function action_delete_index()
	{
		$set_id = $this->request->param('id', 0);
		$set = ORM::factory('Set', $set_id);
		$this->_response_payload = array();
		if ( $set->loaded() )
		{
			// Return the set we just deleted (provides some confirmation)
			$this->_response_payload = $set->for_api();
			$set->delete();
		}
		else
		{
			throw new HTTP_Exception_404('Set does not exist. Set ID: \':id\'', array(
				':id' => $set_id,
			));
		}
	}


	/**
	 * Save sets
	 *
	 * @param Set_Model $set
	 * @aparam array $post POST data
	 */
	 protected function create_or_update_set($set, $post)
	 {
		$set->values($post, array(
				'name', 'filter', 'user_id'));

		//Validation - cycle through nested models and perform in-model
		//validation before saving

		try
		{
			// Validate base set data	
			$set->check();
			
			// Validates ... so save
			$set->values($post, array(
					'name','filter', 'user_id'));
			$set->save();

			// Response is the set
			$this->_response_payload = $set->for_api();

		}
		catch(ORM_Validation_Exception $e)
		{
			throw new HTTP_Exception_400('Validation Error: \':errors\'', array(
					':errors' => implode(', ', Arr::flatten($e->errors('models'))),	
			));
		}
						
	
	 }
}
