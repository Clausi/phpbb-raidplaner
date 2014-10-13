<?php

namespace clausi\raidplaner\controller;

use Symfony\Component\DependencyInjection\ContainerInterface;

class admin_controller implements admin_interface
{
	/** @var \phpbb\config\config */
	protected $config;
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;
	/** @var \phpbb\request\request */
	protected $request;
	/** @var \phpbb\template\template */
	protected $template;
	/** @var \phpbb\user */
	protected $user;
	/** @var ContainerInterface */
	protected $container;
	/** @var \phpbb\boardrules\operators\rule */
	protected $rule_operator;
	/** @var string phpBB root path */
	protected $root_path;
	/** @var string phpEx */
	protected $php_ext;
	/** string Custom form action */
	protected $u_action;

	/**
	* Constructor
	*
	* @param \phpbb\config\config		$config
	* @param \phpbb\controller\helper	$helper
	* @param \phpbb\template\template	$template
	* @param \phpbb\user				$user
	*/
	public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\request\request $request, \phpbb\template\template $template, \phpbb\user $user, ContainerInterface $container, $root_path, $php_ext)
	{
		$this->config = $config;
		$this->db = $db;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->container = $container;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
	}
	
	public function display_options()
	{
		add_form_key('clausi/raidplaner');

		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key('clausi/raidplaner'))
			{
				trigger_error('FORM_INVALID');
			}

			$this->set_options();
			trigger_error($this->user->lang('ACP_RAIDPLANER_SETTING_SAVED') . adm_back_link($this->u_action));
		}

		$this->template->assign_vars(array(
			'U_ACTION'	=> $this->u_action,
			'CLAUSI_RAIDPLANER_ACTIVE' => $this->config['clausi_raidplaner_active'],
		));
	}
	
	protected function set_options()
	{
		$this->config->set('clausi_raidplaner_active', $this->request->variable('clausi_raidplaner_active', 0));
	}
	
	public function display_schedule()
	{
		add_form_key('clausi/raidplaner');

		$this->template->assign_vars(array(
			'U_ACTION_ADD' => $this->u_action . '&amp;action=add',
			'U_ACTION' => $this->u_action,
		));
	}
	
	public function add_schedule()
	{
		add_form_key('clausi/raidplaner');
		if ($this->request->is_set_post('submit'))
		{
			if($this->request->variable('show', '') == 'add')
			{
				if (!check_form_key('clausi/raidplaner'))
				{
					trigger_error('FORM_INVALID');
				}

				trigger_error($this->user->lang('ACP_RAIDPLANER_SCHEDULE_SAVED') . adm_back_link($this->u_action));
			}
		}
		
		$this->template->assign_vars(array(
			'S_EDIT_RAIDPLANER_SCHEDULE' => true,
			'U_BACK'	=> $this->u_action,
			'U_ACTION'	=> $this->u_action . '&amp;action=add',
		));
	}

	public function set_page_url($u_action)
	{
		$this->u_action = $u_action;
	}
	
}
