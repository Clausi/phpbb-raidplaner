<?php

namespace clausi\raidplaner\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Event listener
*/
class main_listener implements EventSubscriberInterface
{
	
	static public function getSubscribedEvents()
	{
		return array(
			'core.user_setup'	=> 'load_language_on_setup',
			'core.page_header'	=> 'add_page_header_link',
			// ACP event
			'core.permissions'	=> 'add_permission',
		);
	}

	protected $helper;

	protected $template;
	
	protected $config;
	
	protected $auth;

	
	public function __construct(\phpbb\controller\helper $helper, \phpbb\template\template $template, \phpbb\config\config $config, \phpbb\auth\auth $auth)
	{
		$this->helper = $helper;
		$this->template = $template;
		$this->config = $config;
		$this->auth = $auth;
	}

	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'clausi/raidplaner',
			'lang_set' => 'raidplaner_common',
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}
	
	public function add_permission($event)
	{
		$permissions = $event['permissions'];
		$permissions['a_raidplaner'] = array('lang' => 'ACL_A_RAIDPLANER', 'cat' => 'misc');
		$permissions['m_raidplaner'] = array('lang' => 'ACL_M_RAIDPLANER', 'cat' => 'misc');
		$permissions['u_raidplaner'] = array('lang' => 'ACL_U_RAIDPLANER', 'cat' => 'misc');
		$event['permissions'] = $permissions;
	}

	public function add_page_header_link($event)
	{
		$this->template->assign_vars(array(
			'U_RAIDPLANER' => ($this->auth->acl_get('u_raidplaner') || $this->auth->acl_get('m_raidplaner') || $this->auth->acl_get('a_raidplaner')),
			'U_RAIDPLANER_PAGE'	=> $this->helper->route('clausi_raidplaner_controller'),
			'S_CLAUSI_RAIDPLANER_ACTIVE' => $this->config['clausi_raidplaner_active'],
		));
	}
}
