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


class Continuum_mcp {

	
	/**
	  * Constructor
	  */
	function __construct()
	{
		// make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

		// load library
		$this->EE->load->library('continuum_lib');

		// backwards compabitility for EE < 2.4
		if (!defined('URL_THIRD_THEMES'))
		{
			define('URL_THIRD_THEMES', $this->EE->config->item('theme_folder_url').'third_party/');
		}
	}

	// --------------------------------------------------------------------

	/**
	  *  Home Page
	  */
	function index()
	{
		$this->EE->load->library('table');
		$this->EE->load->library('javascript');
		
		$this->EE->jquery->plugin(BASE.AMP.'C=javascript'.AMP.'M=load'.AMP.'plugin=tablesorter', TRUE);
		$this->EE->jquery->tablesorter('.mainTable', '{widgets: ["zebra"]}');
		
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('continuum_module_name'));
		
		$this->EE->cp->set_right_nav(array(				
				'clear_anonymous_log' => BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=continuum'.AMP.'method=clear_log_confirm'.AMP.'entire=0',
				'clear_entire_log' => BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=continuum'.AMP.'method=clear_log_confirm'.AMP.'entire=1',
				
				'settings' => BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=continuum'.AMP.'method=settings'
		));
				
		$vars = array(
					'logs' => array(),
					'users' => array(),
					'actions' => array('Landing' => 'Landing', 'Visit' => 'Visit', 'Exit' => 'Exit'),
					'urls' => array(),
					'total_logs' => $this->EE->db->count_all('continuum_log'),
					'current_user_id' => 0,
					'current_action' => '',
					'current_url_id' => 0,
					'current_limit' => 20,	
					'last_log_id' => 0,	
					'pending_log_ids' => array(),		
					'theme_folder_url' => URL_THIRD_THEMES.'/continuum/'
				);


		// clean up
		$this->EE->continuum_lib->clean_up();


		// get logs	
		$this->EE->db->select('continuum_log.*, continuum_users.unique_id, continuum_urls.url, members.member_id, members.screen_name');
		$this->EE->db->from('continuum_log');
		$this->EE->db->join('continuum_users', 'continuum_log.user_id = continuum_users.user_id');
		$this->EE->db->join('members', 'members.member_id = continuum_users.member_id', 'left');
		$this->EE->db->join('continuum_urls', 'continuum_log.url_id = continuum_urls.url_id');
		
		if ($user_id = $this->EE->input->get('user_id'))
		{
			$this->EE->db->where('continuum_log.user_id', $user_id);
			$vars['current_user_id'] = $user_id;
		}
		
		if ($action = $this->EE->input->get('action'))
		{
			$this->EE->db->where('continuum_log.action', $action);
			$vars['current_action'] = $action;
		}
		
		if ($url_id = $this->EE->input->get('url_id'))
		{
			$this->EE->db->where('continuum_log.url_id', $url_id);
			$vars['current_url_id'] = $url_id;
		}
		
		if ($limit = $this->EE->input->get('limit'))
		{
			$vars['current_limit'] = $limit;
		}
		
		$this->EE->db->order_by('log_id', 'desc');
		$this->EE->db->limit($vars['current_limit']);
		$query = $this->EE->db->get();

		foreach ($query->result() as $row)
		{
			// define action class
			$row->action_class = strtolower(str_replace(' ', '_', $row->action));
			
			// get absolute url
			$row->absolute_url = $this->EE->functions->create_url($row->url);
			
			$vars['logs'][] = $row;
			
			$vars['last_log_id'] = ($row->log_id > $vars['last_log_id']) ? $row->log_id : $vars['last_log_id'];
			
			if ($row->action == 'Visit' AND !$row->time_on_page)
			{
				$vars['pending_log_ids'][] = $row->log_id;
			}
		}
		
		$vars['pending_log_ids'] = count($vars['pending_log_ids']) ? implode('|', $vars['pending_log_ids']) : '';
		
		
		// get non-native actions
		$this->EE->db->select('action');
		$this->EE->db->from('continuum_log');
		$this->EE->db->where_not_in($vars['actions']);
		$this->EE->db->group_by('action'); 
		$query = $this->EE->db->get();
		
		foreach ($query->result() as $row)
		{
			$vars['actions'][$row->action] = $row->action;
		}
		
		
		// get all users
		$this->EE->db->from('continuum_users');
		$this->EE->db->join('members', 'members.member_id = continuum_users.member_id', 'left');
		$query = $this->EE->db->get();
		
		foreach ($query->result() as $row)
		{
			$vars['users'][$row->user_id] = ($row->member_id ? $row->screen_name : 'User '.$row->user_id);
		}
		
		
		// get all urls
		$this->EE->db->order_by('url', 'asc');
		$query = $this->EE->db->get('continuum_urls');
		
		foreach ($query->result() as $row)
		{
			$vars['urls'][$row->url_id] = $row->url;
		}		
		
		
		// get base and ajax urls
		$vars['base_url'] = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=continuum'.AMP.'user_id='.$vars['current_user_id'].AMP.'action='.$vars['current_action'].AMP.'url_id='.$vars['current_url_id'];
		$vars['ajax_url'] = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=continuum'.AMP.'method=ajax_logs'.AMP.'user_id='.$vars['current_user_id'].AMP.'action='.$vars['current_action'].AMP.'url_id='.$vars['current_url_id'];
		
		
		// link css files
		$this->EE->cp->add_to_head('<link rel="stylesheet" type="text/css" href="'.$vars['theme_folder_url'].'lib/chosen/chosen.css" media="screen" />');
		$this->EE->cp->add_to_head('<link rel="stylesheet" type="text/css" href="'.$vars['theme_folder_url'].'css/continuum.css" media="screen" />');
		$this->EE->cp->add_to_head('<link rel="stylesheet" type="text/css" href="'.$vars['theme_folder_url'].'css/continuum_d3.css" media="screen" />');
		
		// link javascript files
		$this->EE->cp->add_to_foot('<script type="text/javascript" src="'.$vars['theme_folder_url'].'lib/chosen/chosen.jquery.min.js"></script>');
		
		// load javascript packages
		$this->EE->cp->load_package_js('continuum');
		$this->EE->cp->load_package_js('d3.v2');
		$this->EE->cp->load_package_js('continuum_d3');
		
		
		return $this->EE->load->view('index', $vars, TRUE);
	}
	
	// --------------------------------------------------------------------

	/**
	  *  Ajax Logs
	  */
	function ajax_logs()
	{	
		$logs = array();
		$pending_logs = array();
		$last_log_id = $this->EE->input->get('last_log_id');
		$pending_log_ids = explode('|', $this->EE->input->get('pending_log_ids'));
		
		
		if ($last_log_id !== '')
		{
			$theme_folder_url = URL_THIRD_THEMES.'/continuum/';
			$base_url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=continuum'.AMP.'user_id='.$this->EE->input->get('current_user_id').AMP.'url_id='.$this->EE->input->get('current_url_id');
			
			// get logs	
			$this->EE->db->select('continuum_log.*, continuum_users.unique_id, continuum_urls.url, members.member_id, members.screen_name');
			$this->EE->db->from('continuum_log');
			$this->EE->db->join('continuum_users', 'continuum_log.user_id = continuum_users.user_id');
			$this->EE->db->join('members', 'members.member_id = continuum_users.member_id', 'left');
			$this->EE->db->join('continuum_urls', 'continuum_log.url_id = continuum_urls.url_id');
			
			if ($user_id = $this->EE->input->get('user_id'))
			{
				$this->EE->db->where('continuum_log.user_id', $user_id);
			}
			
			if ($action = $this->EE->input->get('action'))
			{
				$this->EE->db->where('continuum_log.action', $action);
			}
			
			if ($url_id = $this->EE->input->get('url_id'))
			{
				$this->EE->db->where('continuum_log.url_id', $url_id);
			}		
			
			$this->EE->db->where('log_id > '.$last_log_id);
			$this->EE->db->order_by('log_id', 'asc');
			
			if ($limit = $this->EE->input->get('limit'))
			{
				$this->EE->db->limit($limit);
			}
			
			$query = $this->EE->db->get();
	
			foreach ($query->result() as $log)
			{
				$action_class = strtolower(str_replace(' ', '_', $log->action));
				
				$absolute_url = $this->EE->functions->create_url($log->url);
				
				$logs[] = array(
					($log->member_id ? '<a href="'.BASE.AMP.'C=myaccount'.AMP.'id='.$log->member_id.'" target="_blank">' : '').($log->member_id ? $log->screen_name : 'User '.$log->user_id).($log->member_id ? '</a>' : '').' <a href="'.$base_url.'&user_id='.$log->user_id.'"><img src="'.$theme_folder_url.'images/filter.png" /></a>',
					'<span class="action '.$action_class.'">'.$log->action.'</span> '.'<a href="'.$base_url.'&action='.$log->action.'"><img src="'.$theme_folder_url.'images/filter.png" /></a>',					
					'<a href="'.$absolute_url.'" target="_blank">'.$log->url.'</a>'.'<a href="'.$base_url.'&url_id='.$log->url_id.'"><img src="'.$theme_folder_url.'images/filter.png" /></a>',
					'<span id="log_'.$log->log_id.'">'.(($log->time_on_page != '') ? gmdate("H:i:s", $log->time_on_page) : (($log->action == 'Visit' OR $log->action == 'Landing') ? '<img src="'.$theme_folder_url.'images/loading.gif" />' : '')).'</span>',
					date("Y-m-d H:i:s T", $log->timestamp),
					str_replace('BASE', BASE, $log->notes)
				);
				
				$last_log_id = ($log->log_id > $last_log_id) ? $log->log_id : $last_log_id;
				
				if (!$log->time_on_page AND ($log->action == 'Landing' OR $log->action == 'Visit'))
				{
					$pending_log_ids[] = $log->log_id;
				}
			}
		}
			
			
		if (count($pending_log_ids))
		{
			// get pending logs	with a time set
			$this->EE->db->select('log_id, time_on_page');
			$this->EE->db->from('continuum_log');
			$this->EE->db->where('time_on_page !=', '');
			$this->EE->db->where_in('log_id', $pending_log_ids);
			$query = $this->EE->db->get();
			
			foreach ($query->result() as $log)
			{
				$pending_logs[] = array($log->log_id, gmdate("H:i:s", $log->time_on_page));
				
				// remove id from array
				unset($pending_log_ids[array_search($log->log_id, $pending_log_ids)]);
			}
		}
		
		$pending_log_ids = count($pending_log_ids) ? implode('|', $pending_log_ids) : '';
		
		
		// output json encoded data		
		echo json_encode(array('logs' => $logs, 'pending_logs' => $pending_logs, 'last_log_id' => $last_log_id, 'pending_log_ids' => $pending_log_ids));
			
		// kill script so the CP is not shown		
		die();
	}
	
	// --------------------------------------------------------------------

	/**
	  *  Settings
	  */
	function settings()
	{
		$this->EE->load->helper('form');
		$this->EE->load->library('table');
		                
		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=continuum', $this->EE->lang->line('continuum_module_name'));
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('settings'));
		
		// get template groups
		$this->EE->db->select('group_id, group_name');
		$this->EE->db->where('site_id', $this->EE->config->item('site_id'));		
		$this->EE->db->order_by('group_order', 'asc');		
		$template_groups = $this->EE->db->get('template_groups');
		
		$vars['template_groups'] = $template_groups->result();		
		$vars['settings'] = $this->get_settings();

		return $this->EE->load->view('settings', $vars, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	  *  Update Settings
	  */
	function update_settings()
	{
		$settings = $this->get_settings();
		$new_settings = array();
		
		foreach ($settings as $key => $val)
		{
			$new_settings[$key] = $this->EE->input->post($key);			
		}
		
		$this->EE->continuum_model->update_settings($new_settings);
		
		$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('settings_updated'));
		
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=continuum');
	}

	// --------------------------------------------------------------------

	/**
	  *  Get settings
	  */
	function get_settings()
	{
		// load model
		$this->EE->load->model('continuum_model');
				
		return $this->EE->continuum_model->get_settings();
	}	
	
	// --------------------------------------------------------------------

	/**
	  *  Clear log confirm
	  */
	function clear_log_confirm()
	{
		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=continuum', $this->EE->lang->line('continuum_module_name'));

		$vars['cp_page_title'] = $this->EE->lang->line('clear_log_confirm');
		$vars['entire'] = $this->EE->input->get('entire') ? 1 : 0;
				
		return $this->EE->load->view('clear_log_confirm', $vars, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	  *  Clear log
	  */
	function clear_log()
	{
		// if clearing entire log
		if ($this->EE->input->get('entire'))
		{
			// empty tables (except users)
			$this->EE->db->empty_table('continuum_log');
			$this->EE->db->empty_table('continuum_urls');
			
			// reset auto increments
			$this->EE->db->query("ALTER TABLE exp_continuum_log AUTO_INCREMENT = 1");
			$this->EE->db->query("ALTER TABLE exp_continuum_urls AUTO_INCREMENT = 1");				
		}

		else 
		{			
			// get anonymous users
			$this->EE->db->select('user_id');
			$this->EE->db->where('member_id', '');
			$query = $this->EE->db->get('continuum_users');
			
			// foreach user
			foreach ($query->result() as $row) 
			{
				// delete from log	
				$this->EE->db->where('user_id', $row->user_id);
				$this->EE->db->delete('continuum_log');
			}
		}
		
		
		$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('log_cleared'));
		
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=continuum');
	}		
				
}
// END CLASS

/* End of file mcp.continuum.php */
/* Location: ./system/expressionengine/third_party/continuum/mcp.continuum.php */