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
	
	public function display_events()
	{
		add_form_key('clausi/raidplaner');
		
		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key('clausi/raidplaner'))
			{
				trigger_error('FORM_INVALID');
			}
			$action = $this->request->variable('action', 0);
			if($action == 'add') $this->add_event();
			elseif($action == 'edit') $this->edit_event();
			
			trigger_error($this->user->lang('ACP_RAIDPLANER_EVENT_SAVED') . adm_back_link($this->u_action));
		}
	
		$sql = "SELECT * FROM " . $this->container->getParameter('tables.clausi.raidplaner_events') . " WHERE deleted = '0' ORDER BY id";
		$result = $this->db->sql_query($sql);
		while($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('n_events', array(
				'ID' => $row['id'],
				'NAME' => $row['name'],
				'RAIDSIZE' => $row['raidsize'],
				'U_EDIT' => $this->u_action . '&amp;action=edit&amp;id='.$row['id'],
				'U_DELETE' => $this->u_action . '&amp;action=delete&amp;id='.$row['id'],
			));
		}
		$this->db->sql_freeresult($result);
		
		$this->template->assign_vars(array(
			'U_ACTION'	=> $this->u_action,
		));
	}
	
	public function delete_event($id)
	{
		if(!$id) trigger_error('INVALID_ID');
		
		$sql_ary = array(
			'deleted' => time(),
		);
		$sql = 'UPDATE ' . $this->container->getParameter('tables.clausi.raidplaner_schedule') . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . ' WHERE event = ' . $id;
		$this->db->sql_query($sql);
		
		$sql = 'UPDATE ' . $this->container->getParameter('tables.clausi.raidplaner_events') . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . ' WHERE id = ' . $id;
		$this->db->sql_query($sql);
	}
	
	public function edit_event()
	{
		if(!$event_id = $this->request->variable('id', 0)) trigger_error('INVALID_ID');
		
		add_form_key('clausi/raidplaner');
		
		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key('clausi/raidplaner'))
			{
				trigger_error('FORM_INVALID');
			}
			
			if(!$event_name = $this->request->variable('event_name', '')) trigger_error($this->user->lang('ACP_RAIDPLANER_ERROR') . adm_back_link($this->u_action), E_USER_WARNING);
			if(!$raidsize = $this->request->variable('raidsize', 0)) trigger_error($this->user->lang('ACP_RAIDPLANER_ERROR') . adm_back_link($this->u_action), E_USER_WARNING);
			
			$sql_ary = array(
				'name' => $event_name,
				'raidsize' => $raidsize,
			);
			$sql = 'UPDATE ' . $this->container->getParameter('tables.clausi.raidplaner_events') . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . ' WHERE id = ' . $event_id;
			$this->db->sql_query($sql);
			
			trigger_error($this->user->lang('ACP_RAIDPLANER_EVENT_SAVED') . adm_back_link($this->u_action));
		}
		
		$sql = "SELECT * FROM " . $this->container->getParameter('tables.clausi.raidplaner_events') . " WHERE id = '".$event_id."' LIMIT 1";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->template->assign_vars(array(
			'EVENT_ID' => $row['id'],
			'EVENT_NAME' => $row['name'],
			'EVENT_RAIDSIZE' => $row['raidsize'],
		));
		$this->db->sql_freeresult($result);
		
		$this->template->assign_vars(array(
			'S_EDIT_RAIDPLANER_SCHEDULE' => true,
			'U_BACK' => $this->u_action,
			'U_ACTION'	=> $this->u_action,
		));
	}
	
	
	protected function add_event()
	{
		if(!$event_name = $this->request->variable('event_name', '')) trigger_error($this->user->lang('ACP_RAIDPLANER_ERROR') . adm_back_link($this->u_action), E_USER_WARNING);
		if(!$raidsize = $this->request->variable('raidsize', 0)) trigger_error($this->user->lang('ACP_RAIDPLANER_ERROR') . adm_back_link($this->u_action), E_USER_WARNING);
		
		$sql_ary = array(
			'name' => $event_name,
			'raidsize' => $raidsize,
		);
		$sql = 'INSERT INTO ' . $this->container->getParameter('tables.clausi.raidplaner_events') . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
		$this->db->sql_query($sql);
	}
	
	
	public function display_schedule()
	{
		
		$sql = "SELECT * FROM " . $this->container->getParameter('tables.clausi.raidplaner_schedule') . " WHERE deleted = '0' AND repeatable != 'no_repeat' ORDER BY id";
		$result = $this->db->sql_query($sql);
		while($row = $this->db->sql_fetchrow($result))
		{
			$sql = "SELECT * FROM " . $this->container->getParameter('tables.clausi.raidplaner_events') . " WHERE id = '".$row['event']."' LIMIT 1";
			$result_event = $this->db->sql_query($sql);
			$row_event = $this->db->sql_fetchrow($result_event);
			$this->db->sql_freeresult($result_event);
			
			$this->template->assign_block_vars('n_schedules', array(
				'ID' => $row['id'],
				'EVENT' => $row_event['name'],
				'START_TIME' => date('H:i', $row['start_time']),
				'END_TIME' => date('H:i', $row['end_time']),
				'INVITE_TIME' => date('H:i', $row['invite_time']),
				'DAY' => date('l', $row['repeat_start']),
				'REPEAT' => $row['repeatable'],
				'EXPIRE' => ($row['repeat_end']) ? $this->user->format_date($row['repeat_end']) : 'never',
				'U_EDIT' => $this->u_action . '&amp;action=edit&amp;id='.$row['id'],
				'U_DELETE' => $this->u_action . '&amp;action=delete&amp;id='.$row['id'],
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
				
				if(!$repeat_start = $this->request->variable('repeat_start', '0')) trigger_error($this->user->lang('ACP_RAIDPLANER_ERROR') . adm_back_link($this->u_action . '&amp;action=add'), E_USER_WARNING);
				$repeatable = $this->request->variable('repeatable', 'no_repeat');
				$repeat_end = $this->request->variable('repeat_end', '0');
				
				if(!$invite_time = $this->request->variable('invite_time', '0')) trigger_error($this->user->lang('ACP_RAIDPLANER_ERROR') . adm_back_link($this->u_action . '&amp;action=add'), E_USER_WARNING);
				if(!$start_time = $this->request->variable('start_time', '0')) trigger_error($this->user->lang('ACP_RAIDPLANER_ERROR') . adm_back_link($this->u_action . '&amp;action=add'), E_USER_WARNING);
				if(!$end_time = $this->request->variable('end_time', '0')) trigger_error($this->user->lang('ACP_RAIDPLANER_ERROR') . adm_back_link($this->u_action . '&amp;action=add'), E_USER_WARNING);
				
				$autoaccept = $this->request->variable('autoaccept', 1);
				
				if(!$event = $this->request->variable('event', 0)) trigger_error($this->user->lang('ACP_RAIDPLANER_ERROR') . adm_back_link($this->u_action . '&amp;action=add'), E_USER_WARNING);
								
				$sql_ary = array(
					'event' => $event,
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
		
		$sql = "SELECT * FROM " . $this->container->getParameter('tables.clausi.raidplaner_events') . " WHERE deleted = '0' ORDER BY id";
		$result = $this->db->sql_query($sql);
		while($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('n_events', array(
				'ID' => $row['id'],
				'NAME' => $row['name'],
				'RAIDSIZE' => $row['raidsize'],
			));
		}
		$this->db->sql_freeresult($result);
		
		$this->template->assign_vars(array(
			'S_EDIT_RAIDPLANER_SCHEDULE' => true,
			'AUTOACCEPT' => true,
			'U_BACK'	=> $this->u_action,
			'U_ACTION'	=> $this->u_action . '&amp;action=add',
		));
	}
	
	public function delete_schedule($id)
	{
		if(!$id) trigger_error('INVALID_ID');
		
		$sql_ary = array(
			'deleted' => time(),
		);
		$sql = 'UPDATE ' . $this->container->getParameter('tables.clausi.raidplaner_schedule') . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . ' WHERE id = ' . $id;
		$this->db->sql_query($sql);
	}
	

	public function set_page_url($u_action)
	{
		$this->u_action = $u_action;
	}
	
}
