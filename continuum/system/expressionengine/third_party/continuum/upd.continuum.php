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


// get config
require_once PATH_THIRD.'continuum/config'.EXT;
	
	
class Continuum_upd {

	var $version = CONTINUUM_VERSION;


	/**
	 * Constructor
	 */
	function __construct()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
		$this->EE->load->dbforge();
	}

	// --------------------------------------------------------------------

	/**
	 * Module Installer
	 *
	 * @access	public
	 * @return	bool
	 */

	function install()
	{
		// add log table
		$fields = array(
						'log_id'	=> array(
											'type'			=> 'int',
											'constraint'	=> 7,
											'unsigned'		=> TRUE,
											'null'			=> FALSE,
											'auto_increment'=> TRUE
										),
						'user_id'	=> array(
											'type'			=> 'int',
											'constraint'	=> 7,
											'unsigned'		=> TRUE,
											'null'			=> FALSE,
											'default'		=> 0
										),
						'url_id'	=> array(
											'type'			=> 'int',
											'constraint'	=> 7,
											'unsigned'		=> TRUE,
											'null'			=> FALSE,
											'default'		=> 0
										),
						'action'  => array(
											'type' 			=> 'varchar',
											'constraint'	=> '100',
											'null'			=> FALSE,
											'default'		=> ''
										),
						'timestamp'  => array(
											'type' 			=> 'varchar',
											'constraint'	=> '16',
											'null'			=> FALSE,
											'default'			=> ''
										),
						'time_on_page'  => array(
											'type' 			=> 'varchar',
											'constraint'		=> '16',
											'null'			=> FALSE,
											'default'			=> ''
										),
						'notes'  => array(
											'type' 			=> 'text',
											'null'			=> TRUE
										),
						'site_id'	=> array(
											'type'			=> 'int',
											'constraint'	=> 7,
											'unsigned'		=> TRUE,
											'null'			=> FALSE,
											'default'		=> 0
										)
		);		

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('log_id', TRUE);
		$this->EE->dbforge->create_table('continuum_log', TRUE);
		
		
		// add user table
		$fields = array(
						'user_id'	=> array(
											'type'			=> 'int',
											'constraint'		=> 7,
											'unsigned'		=> TRUE,
											'null'			=> FALSE,
											'auto_increment'	=> TRUE
										),
						'member_id'	=> array(
											'type'			=> 'int',
											'constraint'		=> 7,
											'unsigned'		=> TRUE,
											'null'			=> FALSE,
											'default'			=> 0
										),
						'unique_id'  => array(
											'type' 			=> 'varchar',
											'constraint'		=> '32',
											'null'			=> FALSE,
											'default'			=> ''
										),
						'tag'  => array(
											'type' 			=> 'varchar',
											'constraint'		=> '32',
											'null'			=> FALSE,
											'default'			=> ''
										),
						'first_visit'  => array(
											'type' 			=> 'varchar',
											'constraint'		=> '16',
											'null'			=> FALSE,
											'default'			=> ''
										),
						'last_visit'  => array(
											'type' 			=> 'varchar',
											'constraint'		=> '16',
											'null'			=> FALSE,
											'default'			=> ''
										),
						'ip_address'  => array(
											'type' 			=> 'varchar',
											'constraint'		=> '16',
											'null'			=> FALSE,
											'default'			=> ''
										),
						'user_agent'  => array(
											'type' 			=> 'varchar',
											'constraint'		=> '300',
											'null'			=> FALSE,
											'default'			=> ''
										),
						'site_id'	=> array(
											'type'			=> 'int',
											'constraint'		=> 7,
											'unsigned'		=> TRUE,
											'null'			=> FALSE,
											'default'			=> 0
										)
		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('user_id', TRUE);
		$this->EE->dbforge->create_table('continuum_users', TRUE);


		// add urls table
		$fields = array(
						'url_id'	=> array(
											'type'			=> 'int',
											'constraint'		=> 7,
											'unsigned'		=> TRUE,
											'null'			=> FALSE,
											'auto_increment'	=> TRUE
										),
						'url'  => array(
											'type' 			=> 'varchar',
											'constraint'		=> '150',
											'null'			=> FALSE,
											'default'			=> ''
										),
						'first_visit'  => array(
											'type' 			=> 'varchar',
											'constraint'		=> '16',
											'null'			=> FALSE,
											'default'			=> ''
										),
						'site_id'	=> array(
											'type'			=> 'int',
											'constraint'		=> 7,
											'unsigned'		=> TRUE,
											'null'			=> FALSE,
											'default'			=> 0
										)
		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('url_id', TRUE);
		$this->EE->dbforge->create_table('continuum_urls', TRUE);


		// add settings table
		$fields = array(
						'setting_id'	=> array(
											'type'			=> 'int',
											'constraint'	=> 7,
											'unsigned'		=> TRUE,
											'null'			=> FALSE,
											'auto_increment'=> TRUE
										),
						'setting_name'	=> array(
											'type' 			=> 'varchar',
											'constraint'	=> '30',
											'null'			=> FALSE,
											'default'		=> ''
										),
						'setting_value'  => array(
											'type' 			=> 'varchar',
											'constraint'	=> '100',
											'null'			=> FALSE,
											'default'		=> ''
										),
						'site_id'	=> array(
											'type'			=> 'int',
											'constraint'		=> 7,
											'unsigned'		=> TRUE,
											'null'			=> FALSE,
											'default'			=> 0
										)
		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('setting_id', TRUE);
		$this->EE->dbforge->create_table('continuum_settings', TRUE);
		
		
		// add module
		$data = array(
			'module_name' 	=> 'Continuum',
			'module_version' 	=> $this->version,
			'has_cp_backend' 	=> 'y'
		);
		$this->EE->db->insert('modules', $data);
		
		
		// add action
		$data = array(
			'class' => 'Continuum',
			'method' => 'log_activity'
		);
		$this->EE->db->insert('actions', $data); 


		return TRUE;
	}



	// --------------------------------------------------------------------

	/**
	 * Module Uninstaller
	 *
	 * @access	public
	 * @return	bool
	 */
	function uninstall()
	{
		$this->EE->db->select('module_id');
		$query = $this->EE->db->get_where('modules', array('module_name' => 'Continuum'));

		$this->EE->db->where('module_id', $query->row('module_id'));
		$this->EE->db->delete('module_member_groups');

		$this->EE->db->where('module_name', 'Continuum');
		$this->EE->db->delete('modules');
	
		$this->EE->db->where('class', 'Continuum');
		$this->EE->db->delete('actions');

		$this->EE->dbforge->drop_table('continuum_log');
		$this->EE->dbforge->drop_table('continuum_users');
		$this->EE->dbforge->drop_table('continuum_urls');
		$this->EE->dbforge->drop_table('continuum_settings');

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Module Updater
	 *
	 * @access	public
	 * @return	bool
	 */
	function update($current='')
	{
		if ($current == $this->version)
		{
			return FALSE;
		}		
		
		return TRUE;
	}
}
// END CLASS

/* End of file upd.continuum.php */
/* Location: ./system/expressionengine/third_party/continuum/upd.continuum.php */