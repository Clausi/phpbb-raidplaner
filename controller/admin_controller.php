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
		
		$sql = "SELECT * FROM " . $this->container->getParameter('tables.clausi.raidplaner_schedule') . " WHERE repeatable != 'no_repeat' ORDER BY id";
		$result = $this->db->sql_query($sql);
		while($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('n_schedules', array(
				'ID' => $row['id'],
				'EVENT' => $row['event'],
				'START_TIME' => date('H:i', $row['start_time']),
				'END_TIME' => date('H:i', $row['end_time']),
				'INVITE_TIME' => date('H:i', $row['invite_time']),
				'DAY' => date('l', $row['repeat_start']),
				'REPEAT' => $row['repeatable'],
				'EXPIRE' => ($row['repeat_end']) ? $this->user->format_date($row['repeat_end']) : 'never',
			));
		}
		$this->db->sql_freeresult($result);

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
				
				if(!$repeat_start = $this->request->variable('repeat_start', 0)) trigger_error($this->user->lang('ACP_RAIDPLANER_ERROR') . adm_back_link($this->u_action . '&amp;action=add'), E_USER_WARNING);
				$repeatable = $this->request->variable('repeatable', 'no_repeat');
				$repeat_end = $this->request->variable('repeat_end', 0);
				if(!$invite_time_hour = $this->request->variable('invite_time_hour', 0)) trigger_error($this->user->lang('ACP_RAIDPLANER_ERROR') . adm_back_link($this->u_action . '&amp;action=add'), E_USER_WARNING);
				$invite_time_minute = $this->request->variable('invite_time_minute', 0);
				if(!$start_time_hour = $this->request->variable('start_time_hour', 0)) trigger_error($this->user->lang('ACP_RAIDPLANER_ERROR') . adm_back_link($this->u_action . '&amp;action=add'), E_USER_WARNING);
				$start_time_minute = $this->request->variable('start_time_minute', 0);
				if(!$end_time_hour = $this->request->variable('end_time_hour', 0)) trigger_error($this->user->lang('ACP_RAIDPLANER_ERROR') . adm_back_link($this->u_action . '&amp;action=add'), E_USER_WARNING);
				$end_time_minute = $this->request->variable('end_time_minute', 0);
				$autoaccept = $this->request->variable('autoaccept', 1);
				
				$invite_time = $invite_time_hour . ':' . $invite_time_minute;
				$start_time = $start_time_hour . ':' . $start_time_minute;
				$end_time = $end_time_hour . ':' . $end_time_minute;
								
				$sql_ary = array(
					'event' => 1,
					'repeat_start' => strtotime($repeat_start),
					'repeat_end' => strtotime($repeat_end),
					'repeatable' => $repeatable,
					'autoaccept' => $autoaccept,
					'invite_time' => strtotime($invite_time),
					'start_time' => strtotime($start_time),
					'end_time' => strtotime($end_time),
				);
				$sql = 'INSERT INTO ' . $this->container->getParameter('tables.clausi.raidplaner_schedule') . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
				$this->db->sql_query($sql);
				
				trigger_error($this->user->lang('ACP_RAIDPLANER_SCHEDULE_SAVED') . adm_back_link($this->u_action));
			}
		}
		
		$this->template->assign_vars(array(
			'S_EDIT_RAIDPLANER_SCHEDULE' => true,
			'AUTOACCEPT' => true,
			'U_BACK'	=> $this->u_action,
			'U_ACTION'	=> $this->u_action . '&amp;action=add',
		));
	}

	public function set_page_url($u_action)
	{
		$this->u_action = $u_action;
	}
	
}
