<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine Continuum Library
 *
 * @package			Continuum
 * @category		Libraries
 * @description		Activity stream logger
 * @author			Ben Croker
 * @link			http://www.putyourlightson.net/continuum/
 */
 
 
class Continuum_lib
{

	var $timeout_length = 180;		// 3 minutes
	var $session_length = 1800;		// 30 minutes
	var $cookie_expire = 31536000;	// 1 year


	/**
	  *  Constructor
	  */
	function __construct()
	{
		// make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
		

		// load model
		$this->EE->load->model('continuum_model');
	}
	
	// --------------------------------------------------------------------

	/**
	  *  Log activity
	  */
	function log_activity($action='', $notes='')
	{
		// check if action is set
		if (!$action)
		{
			return;
		}
		
		
		$userdata = $this->EE->session->userdata;
		$site_id = $this->EE->config->item('site_id');
		
		
		// defaults
		$user = FALSE;
		
		
		// get settings
		$settings = $this->EE->continuum_model->get_settings();
		

		// check if logging is enabled
		if (!$settings['logging'])
		{
			return;
		}
		
		if (!$settings['log_anon_users'] AND !$userdata['member_id'])
		{
			return;
		}
		

		// load user agent library
		$this->EE->load->library('user_agent');

		// check if this is a bot by looking at user agent and uri		
		if ($this->EE->agent->is_robot() OR $this->_get_uri() == '/robots.txt')
		{
			return;
		}


		// check if this is a request for a favicon
		if ($this->_get_uri() == '/favicon.ico')
		{
			return;
		}

		
		// -------------------------------------------
		// 'continuum_log_activity_start' hook
		//  - allows complete rewrite of activity logging routine
		//
			if ($this->EE->extensions->active_hook('continuum_log_activity_start') === TRUE)
			{
				$this->EE->extensions->call('continuum_log_activity_start');
				if ($this->EE->extensions->end_script === TRUE) return;
			}
		//
		// -------------------------------------------
		
		
		// if unique id exists
		if ($unique_id = $this->EE->input->cookie('continuum_id'))
		{
			// get user
			$this->EE->db->select('user_id, unique_id, last_visit');
			$this->EE->db->where(array('unique_id' => $unique_id, 'site_id' => $site_id));
			$query = $this->EE->db->get('continuum_users');
			$user = $query->row_array();
		}
		
		
		// if user was not found
		if (!$user)
		{			
			// if user is logged in then check if they exist in continuum
			if ($userdata['member_id'])
			{
				// check if user already exists in continuum
				$this->EE->db->select('user_id, unique_id, last_visit');
				$this->EE->db->where(array('member_id' => $userdata['member_id'], 'site_id' => $site_id));
				$query = $this->EE->db->get('continuum_users');
				$user = $query->row_array();
			}
		
			// if user is still not found then create one
			if (!$user)
			{
				// create unique id
				$unique_id = $this->EE->functions->random('md5');				
				
				// add user to db
				$user = array(
					'unique_id' => $unique_id, 
					'member_id' => $userdata['member_id'], 
					'first_visit' => time(), 
					'site_id' => $site_id
				);
				
				$this->EE->db->insert('continuum_users', $user);
				
				// get user id
				$user['user_id'] = $this->EE->db->insert_id();
			}			
		}
							
		
		// set cookie
		$this->EE->functions->set_cookie('continuum_id', $user['unique_id'], $this->cookie_expire);	
					
		
		// update user
		$data = array(
			'last_visit' => time(), 
			'ip_address' => $this->EE->input->ip_address(),
			'user_agent' => $this->EE->input->user_agent()
		);

		// only update member id if it exists so we don't delete it in the database
		if ($userdata['member_id'])
		{
			$data['member_id'] = $userdata['member_id'];
		}

		$this->EE->db->where('user_id', $user['user_id']);
		$this->EE->db->update('continuum_users', $data);
		
		
		// get url id
		$url_id = 0;
		
		$this->EE->db->select('url_id');
		$this->EE->db->where(array('url' => $this->_get_uri(), 'site_id' => $site_id));
		$query = $this->EE->db->get('continuum_urls');
		
		if ($row = $query->row())
		{
			$url_id = $row->url_id;
		}
		
		// if no url found then create one
		else 
		{	
			$this->EE->db->insert('continuum_urls', array('url' => $this->_get_uri(), 'first_visit' => time(), 'site_id' => $site_id));
			
			$url_id = $this->EE->db->insert_id();
		}
		
		
		// get last action in log
		$query = $this->EE->continuum_model->get_last_log($user['user_id']);
		$row = $query->row();

		// if there is no last visit or if the last visit was not within the last session length
		if (!$row OR (time() - $row->timestamp) > $this->session_length)
		{
			// set action to landing 
			$action = 'Landing';
			
			// add referrer to notes
			$notes = 'Direct';
			
			if (isset($_SERVER['HTTP_REFERER']))
			{
				$referrer = $this->EE->security->xss_clean($_SERVER['HTTP_REFERER']);
				$notes = '<a href="'.$referrer.'" target="_blank">Referrer</a>';
			}
		}
		
		
		// if user was here before then update previous visit
		if ($row)
		{
			$data = array();

			// if the current visit is a landing page
			if ($action == 'Landing')
			{
				// set previous action to exit
				$data['action'] = 'Exit';
			}
			
			// else if the last action was a visit or landing
			else if ($row->action == 'Visit' OR $row->action == 'Landing')
			{
				$data['time_on_page'] = time() - $row->timestamp;
			}
			
			// if there is something to update
			if (count($data))
			{
				$this->EE->db->where('log_id', $row->log_id);
				$this->EE->db->update('continuum_log', $data);
			}
		}
			
		
		// limit length of notes 
		//$notes = $notes ? substr($notes, 0, 300) : '';
		
		
		// collect all necessary data
		$data = array(
			'user_id' => $user['user_id'], 
			'url_id' => $url_id, 
			'action' => $action, 
			'timestamp' => time(), 
			'notes' => $notes, 
			'site_id' => $site_id
		);
		
		
		// -------------------------------------------
		// 'continuum_log_activity' hook
		//  - modify data before inserting in database
		//
			if ($this->EE->extensions->active_hook('continuum_log_activity') === TRUE)
			{
				$data = $this->EE->extensions->call('continuum_log_activity', $data);
				if ($this->EE->extensions->end_script === TRUE) return;
			}
		//
		// -------------------------------------------
		
		
		// log action
		$this->EE->db->insert('continuum_log', $data);
		
		
		// clean up
		$this->clean_up();
		

		// -------------------------------------------
		// 'continuum_log_activity_end' hook
		//  - allows additional processing at end of logging routine
		//
			if ($this->EE->extensions->active_hook('continuum_log_activity_end') === TRUE)
			{
				$this->EE->extensions->call('continuum_log_activity_end');
				if ($this->EE->extensions->end_script === TRUE) return;
			}
		//
		// -------------------------------------------
	}

	// --------------------------------------------------------------------

	/**
	  *  Clean up
	  */
	function clean_up()
	{
		// -------------------------------------------
		// 'continuum_clean_up_start' hook
		//  - allows complete rewrite of clean up routine
		//
			if ($this->EE->extensions->active_hook('continuum_clean_up_start') === TRUE)
			{
				$this->EE->extensions->call('continuum_clean_up_start');
				if ($this->EE->extensions->end_script === TRUE) return;
			}
		//
		// -------------------------------------------
		

		// get settings
		$settings = $this->EE->continuum_model->get_settings();


		// delete anonymous user bounces - landings that have been pending for more than the timeout length
		$this->EE->db->where('action', 'Landing');
		$this->EE->db->where('time_on_page', '');
		$this->EE->db->where('timestamp <', (time() - $this->timeout_length));
		$this->EE->db->delete('continuum_log');


		// delete anonymous users with no logs
		$users = array();

		$this->EE->db->select('user_id');
		$this->EE->db->group_by('user_id');
		$query = $this->EE->db->get('continuum_log');

		foreach ($query->result() as $row) 
		{
			$users[] = $row->user_id;
		}

		if (count($users))
		{
			$this->EE->db->where('member_id', '');
			$this->EE->db->where_not_in('user_id', $users);
			$this->EE->db->delete('continuum_users');
		}


		// close sessions that are older than the session length
		$this->EE->db->where('action', 'Visit');
		$this->EE->db->where('time_on_page', '');
		$this->EE->db->where('timestamp <', (time() - $this->session_length));
		$this->EE->db->update('continuum_log', array('action' => 'Exit'));


		// clear old logs
		if (is_numeric($settings['max_age_actions']))
		{
			// get expiration time
			$time = time() - ($settings['max_age_actions'] * 86400);
			
			// delete older logs
			$this->EE->db->where('timestamp <', $time);
			$this->EE->db->delete('continuum_log');
		}


		// clear logs that exceed the max number of logs allowed
		if (is_numeric($settings['max_logged_actions']))
		{
			// get min log id to keep
			$this->EE->db->select('log_id');
			$this->EE->db->order_by('log_id', 'desc');
			$this->EE->db->limit(1000, $settings['max_logged_actions']);
			$query = $this->EE->db->get('continuum_log');
			
			// if a min id exists
			if ($row = $query->row())
			{
				// delete logs with a lower log id
				$this->EE->db->where('log_id <=', $row->log_id);
				$this->EE->db->delete('continuum_log');
			}
		}


		// -------------------------------------------
		// 'continuum_clean_up_end' hook
		//  - allows additional processing at end of clean up routine
		//
			if ($this->EE->extensions->active_hook('continuum_clean_up_end') === TRUE)
			{
				$this->EE->extensions->call('continuum_clean_up_end', $settings);
				if ($this->EE->extensions->end_script === TRUE) return;
			}
		//
		// -------------------------------------------
	}

	// --------------------------------------------------------------------

	/**
	  *  Get URI with a leading slash
	  */
	private function _get_uri()
	{
		$uri = trim($this->EE->uri->uri_string());
		$uri = (substr($uri, 0, 1) == '/') ? $uri : '/'.$uri;
		return $uri;
	}
	
}
// END CLASS

/* End of file Continuum.php */
/* Location: ./system/expressionengine/third_party/continuum/libraries/Continuum_lib.php */