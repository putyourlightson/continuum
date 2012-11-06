<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine Continuum Extension
 *
 * @package			Continuum
 * @category		Extension
 * @description		Activity stream logger
 * @author			Ben Croker
 * @link			http://www.putyourlightson.net/continuum/	
 */
 
 
// get config
require_once PATH_THIRD.'continuum/config'.EXT;


class Continuum_ext
{
	var $name			= CONTINUUM_NAME;
	var $version		= CONTINUUM_VERSION;
	var $description	= CONTINUUM_DESCRIPTION;
	var $settings_exist	= CONTINUUM_SETTINGS_EXIST;
	var $docs_url		= CONTINUUM_URL;
	
	var $settings		= array();
	
	// --------------------------------------------------------------------
	
	/**
	 * Constructor
	 */
	function __construct($settings = '')
	{
		$this->EE =& get_instance();
		
		$this->settings = $settings;
	} 
	
	// --------------------------------------------------------------------
	
	/**
	 * Log activity
	 */
	function log_activity($action='', $notes='')
	{	
		// load library
		$this->EE->load->library('continuum_lib');
		
		// log action in library
		$this->EE->continuum_lib->log_activity($action, $notes);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Log visit
	 */
	function log_visit($template)
	{
		// if template is not of type webpage or if this is not the first template to be parsed then exit
		if ($template['template_type'] != 'webpage' OR $this->EE->session->cache('continuum', 'template_parsed'))
		{
			return;
		}
		
		// save this template in EE's session cache
		$this->EE->session->set_cache('continuum', 'template_parsed', $template['template_id']);
		
		// log activity
		$this->log_activity('Visit', $template['group_name'].'/'.$template['template_name']);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Log comment
	 */
	function log_comment($data, $comment_moderate, $comment_id)
	{	
		// log activity
		$this->log_activity('Comment', '<a href="'.BASE.AMP.'C=addons_modules&M=show_module_cp&module=comment&method=edit_comment_form&comment_id='.$comment_id.'" target="_blank">View Comment</a>');
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Log safecracker
	 */
	function log_safecracker($obj)
	{	
		// log activity
		$this->log_activity('Safecracker', '<a href="'.BASE.AMP.'C=content_publish&M=entry_form&channel_id='.$obj->entry('channel_id').'&entry_id='.$obj->entry('entry_id').'" target="_blank">View Entry</a>');
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Log freeform
	 */
	function log_freeform($fields, $entry_id)
	{	
		// log activity
		$this->log_activity('Freeform', '<a href="'.BASE.AMP.'C=addons_modules&M=show_module_cp&module=freeform&method=edit_entry_form&entry_id='.$entry_id.'" target="_blank">View Entry</a>');
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Update Extension
	 */
	function update_extension($current='')
	{
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
		
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->update(
					'extensions',
					array('version' => $this->version)
		);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Activate Extension
	 */
	function activate_extension()
	{
		// add template hook
		$data = array(
			'class'	 	=> __CLASS__,
			'method'	=> 'log_visit',
			'hook'	  	=> 'template_fetch_template',
			'settings'  => "",
			'priority'  => 10,
			'version'   => $this->version,
			'enabled'   => 'y'
		);	
		$this->EE->db->insert('extensions', $data);
		
		
		// add comment submission hook
		$data = array(
			'class'	 	=> __CLASS__,
			'method'	=> 'log_comment',
			'hook'	  	=> 'insert_comment_end',
			'settings'  => "",
			'priority'  => 10,
			'version'   => $this->version,
			'enabled'   => 'y'
		);	
		$this->EE->db->insert('extensions', $data);
		
		
		// add safecracker submission hook
		$data = array(
			'class'	 	=> __CLASS__,
			'method'	=> 'log_safecracker',
			'hook'	  	=> 'safecracker_submit_entry_end',
			'settings'  => "",
			'priority'  => 10,
			'version'   => $this->version,
			'enabled'   => 'y'
		);	
		$this->EE->db->insert('extensions', $data);
		
		
		// add freeform submission hook
		$data = array(
			'class'	 	=> __CLASS__,
			'method'	=> 'log_freeform',
			'hook'	  	=> 'freeform_module_insert_end',
			'settings'  => "",
			'priority'  => 10,
			'version'   => $this->version,
			'enabled'   => 'y'
		);	
		$this->EE->db->insert('extensions', $data);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Disable Extension
	 */
	function disable_extension()
	{
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->delete('extensions');
	}
	
}
// END CLASS

/* End of file ext.continuum.php */
/* Location: ./system/expressionengine/third_party/continuum/ext.continuum.php */