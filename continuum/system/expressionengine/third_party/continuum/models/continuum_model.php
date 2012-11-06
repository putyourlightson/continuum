<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine Continuum Model
 *
 * @package			Continuum
 * @category		Models
 * @author			Ben Croker
 * @description		Activity stream logger
 * @link			http://www.putyourlightson.net/continuum/
 */


class Continuum_model  {

	
	/**
	 * Constructor
	 */
	function __construct()
	{
		// make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------

	/**
	  *  Get last log
	  */
	function get_last_log($user_id='')
	{
		$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
		
		if ($user_id)
		{
			$this->EE->db->where('user_id', $user_id);
		}
		
		$this->EE->db->order_by('log_id', 'desc'); 
		$this->EE->db->limit(1);
		$query = $this->EE->db->get('continuum_log');
		
		return $query;
	}

	// --------------------------------------------------------------------

	/**
	  *  Get settings
	  */
	function get_settings()
	{
		// defaults
		$settings = array(
			'logging' => 0,
			'log_anon_users' => 0,
			'max_logged_actions' => '100000',
			'max_age_actions' => '30'
		);
		
		$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
		$query = $this->EE->db->get('continuum_settings');
		
		foreach ($query->result() as $row)
		{
			$settings[$row->setting_name] = $row->setting_value;
		}
		
		return $settings;
	}
	
	// --------------------------------------------------------------------

	/**
	  *  Update Settings
	  */
	function update_settings($settings)
	{
		foreach ($settings as $key => $val)
		{
			$data = array(
				'setting_name' => $key,
				'setting_value' => $val,
				'site_id' => $this->EE->config->item('site_id')
			);
			
			// check if setting exists in db
			$this->EE->db->where('setting_name', $key);
			$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
			$query = $this->EE->db->get('continuum_settings');
				
			if ($query->num_rows)
			{	
				// update
				$this->EE->db->where('setting_name', $key);
				$this->EE->db->update('continuum_settings', $data);
			}
			
			else
			{
				// insert
				$this->EE->db->insert('continuum_settings', $data);
			}
		}
	}
		
}
// END CLASS

/* End of file continuum_model.php */
/* Location: ./system/expressionengine/third_party/continuum/models/continuum_model.php */