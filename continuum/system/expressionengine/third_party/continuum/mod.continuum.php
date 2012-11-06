<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine Continuum Module
 *
 * @package			Continuum
 * @category		Modules
 * @description		Activity stream logger
 * @author			Ben Croker
 * @link			http://www.putyourlightson.net/continuum/
 */
 
 
class Continuum 
{

	/**
	  *  Constructor
	  */
	function __construct()
	{
		// make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
		
		// load library
		$this->EE->load->library('continuum_lib');
	}

	// --------------------------------------------------------------------

	/**
	  *  Log function for use in templates - {exp:continuum:log activity="" notes=""}
	  */
	function log()
	{
		// log action in library
		$this->EE->continuum_lib->log_activity($this->EE->TMPL->fetch_param('action'), $this->EE->TMPL->fetch_param('notes'));
	}
	
	// --------------------------------------------------------------------

	/**
	  *  Log activity function for use with actions
	  */
	function log_activity()
	{
		// log action in library
		$this->EE->continuum_lib->log_activity($this->EE->input->post('action'), $this->EE->input->post('notes'));
	}
		
}
// END CLASS

/* End of file mod.continuum.php */
/* Location: ./system/expressionengine/third_party/continuum/mod.continuum.php */