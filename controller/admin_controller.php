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
	/** string Custom form action */
	protected $u_action;
	protected $auth;
	protected $type_collection;
	protected $raidplaner;

	/**
	* Constructor
	*
	* @param \phpbb\config\config		$config
	* @param \phpbb\controller\helper	$helper
	* @param \phpbb\template\template	$template
	* @param \phpbb\user				$user
	*/
	public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\request\request $request, \phpbb\template\template $template, \phpbb\user $user, \phpbb\auth\auth $auth, ContainerInterface $container, \phpbb\di\service_collection $type_collection, \clausi\raidplaner\controller\main_controller $raidplaner)
	{
		$this->config = $config;
		$this->db = $db;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->auth = $auth;
		$this->container = $container;
		$this->type_collection = $type_collection;
		$this->raidplaner = $raidplaner;
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
	
		$sql = "SELECT * FROM " . $this->container->getParameter('tables.clausi.raidplaner_events') . " WHERE deleted = '0' ORDER BY event_id";
		$result = $this->db->sql_query($sql);
		while($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('n_events', array(
				'EVENT_ID' => $row['event_id'],
				'NAME' => $row['name'],
				'RAIDSIZE' => $row['raidsize'],
				'U_EDIT' => $this->u_action . '&amp;action=edit&amp;id='.$row['event_id'],
				'U_DELETE' => $this->u_action . '&amp;action=delete&amp;id='.$row['event_id'],
			));
		}
		$this->db->sql_freeresult($result);
		
		$this->template->assign_vars(array(
			'U_ACTION'	=> $this->u_action,
		));
	}
	
	public function delete_event($event_id)
	{
		if(!$event_id) trigger_error('INVALID_ID');
		
		$sql_ary = array(
			'deleted' => time(),
		);
		$sql = 'UPDATE ' . $this->container->getParameter('tables.clausi.raidplaner_schedule') . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . ' WHERE event_id = ' . $event_id;
		$this->db->sql_query($sql);
		
		$sql = 'UPDATE ' . $this->container->getParameter('tables.clausi.raidplaner_events') . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . ' WHERE event_id = ' . $event_id;
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
			$sql = 'UPDATE ' . $this->container->getParameter('tables.clausi.raidplaner_events') . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . ' WHERE event_id = ' . $event_id;
			$this->db->sql_query($sql);
			
			trigger_error($this->user->lang('ACP_RAIDPLANER_EVENT_SAVED') . adm_back_link($this->u_action));
		}
		
		$sql = "SELECT * FROM " . $this->container->getParameter('tables.clausi.raidplaner_events') . " WHERE event_id = '".$event_id."' LIMIT 1";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->template->assign_vars(array(
			'EVENT_ID' => $row['event_id'],
			'EVENT_NAME' => $row['name'],
			'EVENT_RAIDSIZE' => $row['raidsize'],
		));
		$this->db->sql_freeresult($result);
		
		$this->template->assign_vars(array(
			'S_EDIT_RAIDPLANER_EVENT' => true,
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
		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key('clausi/raidplaner'))
			{
				trigger_error('FORM_INVALID');
			}
			$action = $this->request->variable('action', 0);
			if($action == 'edit') $this->edit_schedule($this->request->variable('id', 0));
			
			trigger_error($this->user->lang('ACP_RAIDPLANER_SCHEDULE_SAVED_SAVED') . adm_back_link($this->u_action));
		}
		
		$sql = "SELECT * FROM " . $this->container->getParameter('tables.clausi.raidplaner_schedule') . " WHERE deleted = '0' AND repeatable != 'no_repeat' ORDER BY schedule_id";
		$result = $this->db->sql_query($sql);
		while($row = $this->db->sql_fetchrow($result))
		{
			$sql = "SELECT name, raidsize FROM " . $this->container->getParameter('tables.clausi.raidplaner_events') . " WHERE event_id = '".$row['event_id']."' LIMIT 1";
			$result_event = $this->db->sql_query($sql);
			$row_event = $this->db->sql_fetchrow($result_event);
			$this->db->sql_freeresult($result_event);
			
			$this->template->assign_block_vars('n_schedules', array(
				'SCHEDULE_ID' => $row['schedule_id'],
				'EVENT' => $row_event['name'],
				'RAIDSIZE' => $row_event['raidsize'],
				'START_TIME' => $row['start_time'],
				'END_TIME' => $row['end_time'],
				'INVITE_TIME' => $row['invite_time'],
				'DAY' => date('l', $row['repeat_start']),
				'REPEAT_START' => $this->user->format_date($row['repeat_start']),
				'REPEAT' => $row['repeatable'],
				'EXPIRE' => ($row['repeat_end']) ? $this->user->format_date($row['repeat_end']) : 'never',
				'U_EDIT' => $this->u_action . '&amp;action=edit&amp;id='.$row['schedule_id'],
				'U_DELETE' => $this->u_action . '&amp;action=delete&amp;id='.$row['schedule_id'],
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
					'event_id' => $event,
					'repeat_start' => strtotime($repeat_start . ' ' . $start_time),
					'repeat_end' => ($repeat_end == 0) ? 0 : strtotime($repeat_end . ' ' . $end_time),
					'repeatable' => $repeatable,
					'autoaccept' => $autoaccept,
					'invite_time' => $invite_time,
					'start_time' => $start_time,
					'end_time' => $end_time,
				);
				$sql = 'INSERT INTO ' . $this->container->getParameter('tables.clausi.raidplaner_schedule') . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
				$this->db->sql_query($sql);
				
				trigger_error($this->user->lang('ACP_RAIDPLANER_SCHEDULE_SAVED') . adm_back_link($this->u_action));
			}
		}
		
		$sql = "SELECT * FROM " . $this->container->getParameter('tables.clausi.raidplaner_events') . " WHERE deleted = '0' ORDER BY event_id";
		$result = $this->db->sql_query($sql);
		while($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('n_events', array(
				'EVENT_ID' => $row['event_id'],
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
	
	public function edit_schedule($schedule_id)
	{
		add_form_key('clausi/raidplaner');
		if ($this->request->is_set_post('submit'))
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
				'event_id' => $event,
				'repeat_start' => strtotime($repeat_start . ' ' . $start_time),
				'repeat_end' => ($repeat_end == 0) ? 0 : strtotime($repeat_end . ' ' . $end_time),
				'repeatable' => $repeatable,
			);
			$sql_ary_both = array(
				'autoaccept' => $autoaccept,
				'invite_time' => $invite_time,
				'start_time' => $start_time,
				'end_time' => $end_time,
			);
			
			$sql = 'UPDATE ' . $this->container->getParameter('tables.clausi.raidplaner_schedule') . ' SET ' . $this->db->sql_build_array('UPDATE', array_merge($sql_ary, $sql_ary_both)) . ' WHERE schedule_id = ' . $schedule_id;
			$this->db->sql_query($sql);
			
			$sql = 'UPDATE ' . $this->container->getParameter('tables.clausi.raidplaner_raids') . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary_both) . ' WHERE schedule_id = ' . $schedule_id . ' AND raid_time > ' . time();
			$this->db->sql_query($sql);
			
			trigger_error($this->user->lang('ACP_RAIDPLANER_SCHEDULE_SAVED') . adm_back_link($this->u_action));
		}
		
		$sql = "SELECT * FROM " . $this->container->getParameter('tables.clausi.raidplaner_schedule') . " WHERE schedule_id = '".$schedule_id."' LIMIT 1";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$event_id = $row['event_id'];
		$this->template->assign_vars(array(
			'SCHEDULE_ID' => $row['schedule_id'],
			'EVENT_ID' => $row['event_id'],
			'START_TIME' => $row['start_time'],
			'END_TIME' => $row['end_time'],
			'INVITE_TIME' => $row['invite_time'],
			'DAY' => date('l', $row['repeat_start']),
			'REPEAT' => $row['repeatable'],
			'REPEAT_START' => date('Y-m-d', $row['repeat_start']),
			'AUTOACCEPT' => $row['autoaccept'],
			'REPEAT_END' => ($row['repeat_end']) ? date('Y-m-d', $row['repeat_end']) : '',
		));
		$this->db->sql_freeresult($result);
		
		$sql = "SELECT * FROM " . $this->container->getParameter('tables.clausi.raidplaner_events') . " WHERE deleted = '0' ORDER BY event_id";
		$result = $this->db->sql_query($sql);
		while($row = $this->db->sql_fetchrow($result))
		{
			if($row['event_id'] == $event_id) $event_name = $row['name'];
			$this->template->assign_block_vars('n_events', array(
				'EVENT_ID' => $row['event_id'],
				'NAME' => $row['name'],
				'RAIDSIZE' => $row['raidsize'],
			));
		}
		$this->db->sql_freeresult($result);
		
		$this->template->assign_vars(array(
			'EVENT_NAME' => $event_name,
			'S_EDIT_RAIDPLANER_SCHEDULE' => true,
			'U_BACK'	=> $this->u_action,
			'U_ACTION'	=> $this->u_action . '&amp;action=edit',
		));
	}
	
	public function delete_schedule($schedule_id)
	{
		if(!$schedule_id) trigger_error('INVALID_ID');
		
		$sql_ary = array(
			'deleted' => time(),
		);
		$sql = 'UPDATE ' . $this->container->getParameter('tables.clausi.raidplaner_schedule') . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . ' WHERE schedule_id = ' . $schedule_id;
		$this->db->sql_query($sql);
		
		$sql = 'UPDATE ' . $this->container->getParameter('tables.clausi.raidplaner_raids') . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . ' WHERE schedule_id = ' . $schedule_id . ' AND raid_time > ' . time();
		$this->db->sql_query($sql);
	}
	
	
	public function display_users()
	{
		add_form_key('clausi/raidplaner');
		if ($this->request->is_set_post('submit'))
		{

			if (!check_form_key('clausi/raidplaner'))
			{
				trigger_error('FORM_INVALID');
			}
			$this->cp = $this->container->get('profilefields.manager');
			$users = $this->request->variable('users', array(0 => array('' => 0)));
			
			$key_raid = '';

			foreach( $users as $user_id => $user_data )
			{
				foreach( $user_data as $key => $value )
				{
					$user_data[$key] = $value+1;
					
					$key_raid = '';
					switch($key)
					{
						case 'pf_raidplaner_class':
							$key_raid = 'class';
						break;
						case 'pf_raidplaner_role':
							$key_raid = 'role';
						break;
					}
					$raid_data[$key_raid] = $user_data[$key];
				}
				$this->cp->update_profile_field_data($user_id, $user_data);

				// Update attendees of future raids to reflect new role and class
				$sql = "UPDATE " . $this->container->getParameter('tables.clausi.raidplaner_attendees') . " 
					SET " . $this->db->sql_build_array('UPDATE', $raid_data) . " 
					WHERE 
						user_id = '".$user_id."' 
						AND raid_id IN ( SELECT raid_id FROM " . $this->container->getParameter('tables.clausi.raidplaner_raids') . " WHERE deleted = '0' AND raid_time > '".time()."' )
					";
				$result = $this->db->sql_query($sql);
			}
			
			trigger_error($this->user->lang('ACP_RAIDPLANER_USERS_SAVED') . adm_back_link($this->u_action));
		}
		
		$user_ary = $this->auth->acl_get_list(false, 'u_raidplaner', false);
		foreach($user_ary as $permission)
		{
			foreach($permission['u_raidplaner'] as $user_id)
			{
				$sql = "SELECT user_id, username FROM " . $this->container->getParameter('tables.users') . " WHERE user_id = '".$user_id."'";
				$result = $this->db->sql_query($sql);
				$row = $this->db->sql_fetchrow($result);
				$this->template->assign_block_vars('n_users', array(
					'NAME' => $row['username'],
					'USER_ID' => $user_id,
				));
				$this->db->sql_freeresult($result);
				
				$sql = 'SELECT l.*, f.*
					FROM ' . $this->container->getParameter('tables.profile_fields_language') . ' l, 
						' . $this->container->getParameter('tables.profile_fields') . " f
					WHERE f.field_active = 1
					AND l.lang_id = " . (int) $this->user->get_iso_lang_id() . '
					AND l.field_id = f.field_id
					ORDER BY f.field_order';
				$result = $this->db->sql_query($sql);
				while ($row = $this->db->sql_fetchrow($result))
				{
					$profile_field = $this->type_collection[$row['field_type']];
					
					$sql = "SELECT ".$profile_field->get_field_ident($row)." FROM " . $this->container->getParameter('tables.profile_fields_data') . " WHERE user_id = '".$user_id."'";
					$result_data = $this->db->sql_query($sql);
					$row_user_data = $this->db->sql_fetchrow($result_data);
					$this->db->sql_freeresult($result_data);
					
					$user_data = $row_user_data[$profile_field->get_field_ident($row)];
					
					$this->template->assign_block_vars('n_users.profile_fields', array(
						'LANG_NAME'	=> $this->user->lang($row['lang_name']),
						'LANG_EXPLAIN'	=> $this->user->lang($row['lang_explain']),
						'FIELD_ID'	=> $profile_field->get_field_ident($row),
						'S_REQUIRED'	=> ($row['field_required']) ? true : false,
						'USER_DATA' => $user_data-1,
					));

					$sql = 'SELECT *
						FROM ' . $this->container->getParameter('tables.profile_fields_options_language') . '
						WHERE field_id = '. $row['field_id'] .'
						AND lang_id = ' . (int) $this->user->get_iso_lang_id() . '
						ORDER BY option_id';
					$result_options = $this->db->sql_query($sql);
					while($row_options = $this->db->sql_fetchrow($result_options))
					{
						$this->template->assign_block_vars('n_users.profile_fields.options', array(
							'OPTION_ID' => $row_options['option_id'],
							'LANG_VALUE' => $row_options['lang_value'],
						));
					}
					$this->db->sql_freeresult($result_options);
				}
				$this->db->sql_freeresult($result);
			}
		}
		
	}
	

	public function set_page_url($u_action)
	{
		$this->u_action = $u_action;
	}
	
	private function var_display($var)
	{
		echo "<pre>";
		print_r($var);
		echo "</pre>";
	}
	
}
