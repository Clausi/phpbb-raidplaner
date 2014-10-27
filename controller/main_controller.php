<?php

namespace clausi\raidplaner\controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class main_controller implements main_interface
{
	/* @var \phpbb\config\config */
	protected $config;

	/* @var \phpbb\controller\helper */
	protected $helper;

	/* @var \phpbb\template\template */
	protected $template;

	/* @var \phpbb\user */
	protected $user;
	protected $auth;
	protected $cp;
	protected $root_path;
	
	/* @var \phpbb\db\driver\driver_interface */
	protected $db;
	protected $u_action;
	protected $json_response;
	
	protected $status = [
		4 => 'ACCEPT',
		1 => 'ATTENDING',
		3 => 'SUBSTITUTE',
		2 => 'DECLINE',
		0 => 'NOT_SIGNUP',
	];
	
	protected $roles = [
		1 => 'TANK',
		2 => 'HEAL',
		3 => 'MELEE',
		4 => 'RANGE',
	];


	public function __construct(\phpbb\config\config $config, \phpbb\auth\auth $auth, \phpbb\controller\helper $helper, \phpbb\db\driver\driver_interface $db, \phpbb\template\template $template, \phpbb\user $user, \phpbb\request\request $request, ContainerInterface $container ,$root_path)
	{
		$this->config = $config;
		$this->auth = $auth;
		$this->helper = $helper;
		$this->db = $db;
		$this->template = $template;
		$this->user = $user;
		$this->request = $request;
		$this->container = $container;
		
		$this->root_path = $root_path;
		
		$this->json_response = new \phpbb\json_response;
	}

	
	public function handle()
	{
		if($this->config['clausi_raidplaner_active'] == 0) 
		{
			$this->template->assign_var('RAIDPLANER_MESSAGE', $this->user->lang['RAIDPLANER_INACTIVE']);
			return $this->helper->render('raidplaner_error.html', $this->user->lang['RAIDPLANER_PAGE'], 404);
		}
		$message = false;
		$this->u_action = $this->helper->route('clausi_raidplaner_controller');
		
		add_form_key('clausi/raidplaner');
		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key('clausi/raidplaner'))
			{
				trigger_error('FORM_INVALID');
			}

			$this->updateAllStatus();

			meta_refresh(3, $this->u_action);
			trigger_error($this->user->lang('RAIDPLANER_STATUS_UPDATE') . '<br /><br />' . sprintf($this->user->lang['RETURN_INDEX'], '<a href="' . str_replace('&', '&amp;', $this->u_action) . '">', '</a>'));
		}
		
		// get all raids up to one year ago
		// TODO Archive
		$sql = "SELECT r.*, e.name, e.raidsize FROM 
			" . $this->container->getParameter('tables.clausi.raidplaner_raids') . " r,
			" . $this->container->getParameter('tables.clausi.raidplaner_events') . " e, 
			" . $this->container->getParameter('tables.clausi.raidplaner_schedule') . " s 
			WHERE 
				r.deleted = '0' 
				AND r.raid_time > '".(time()-31536000)."'
				AND s.schedule_id = r.schedule_id  
				AND e.event_id = s.event_id 
			ORDER BY r.raid_time";
		$result = $this->db->sql_query($sql);
		
		$sql = "SELECT status, raid_id FROM 
			" . $this->container->getParameter('tables.clausi.raidplaner_attendees') ." 
			WHERE
				user_id = '" .$this->user->data['user_id']. "' 
			";
		$result_attendee = $this->db->sql_query($sql);
		$row_attendees = $this->db->sql_fetchrowset($result_attendee);

		$row_count = $this->getRaidmemberCount();
		
		$firstfuture = 0;
		$memberCount = ['attending' => 0, 'decline' => 0, 'substitute' => 0, 'accept' => 0];
		
		while($row = $this->db->sql_fetchrow($result))
		{
			$user_status = 0;
			foreach($row_count as $key => $raid_count)
			{
				if($raid_count['raid_id'] == $row['raid_id']) $memberCount = $row_count[$key];
			}

			foreach($row_attendees as $key => $raid_attendee)
			{
				if($raid_attendee['raid_id'] == $row['raid_id']) $user_status = $raid_attendee['status'];
			}
			
			$raid_end = explode(':', $row['end_time']);
			$raid_endtime = mktime($raid_end[0], $raid_end[1], 0, date("n", $row['raid_time']), date("j", $row['raid_time']), date("Y", $row['raid_time']));
			if($raid_endtime >= time() && $firstfuture == 0) $firstfuture = 1;
			
			$this->template->assign_block_vars('n_raids', array(
				'EVENTNAME' => $row['name'],
				'RAIDSIZE' => $row['raidsize'],
				'ID' => $row['raid_id'],
				'DATE' => $this->user->format_date($row['raid_time']),
				'TIMESTAMP' => $row['raid_time'],
				'FLAG' => ($raid_endtime < time()) ? 'past' : 'future',
				'FIRSTFUTURE' => $firstfuture,
				'DAY' => strftime ('%A', $row['raid_time']),
				'INVITE_TIME' => $row['invite_time'],
				'START_TIME' => $row['start_time'],
				'END_TIME' => $row['end_time'],
				'NOTE' => $row['note'],
				'MEMBERS_ACCEPT' => $memberCount['accept'],
				'MEMBERS_ATTENDING' => $memberCount['attending'],
				'MEMBERS_DECLINE' => $memberCount['decline'],
				'MEMBERS_SUBSTITUTE' => $memberCount['substitute'],
				
				'USERSTATUS' => $user_status,
				'U_STATUS' => $this->helper->route('clausi_raidplaner_controller_status', array('raid_id' => $row['raid_id'], 'status_id' => 0)),
				
				'U_RAID' => $this->helper->route('clausi_raidplaner_controller_view', array('raid_id' => $row['raid_id'])),
			));
			if($raid_endtime >= time()) $firstfuture = 2;
		}
		$this->db->sql_freeresult($result);
				
		$this->template->assign_vars(array(
			'U_RAIDPLANER' => $this->auth->acl_get('u_raidplaner'),
			'M_RAIDPLANER' => $this->auth->acl_get('m_raidplaner'),
			'A_RAIDPLANER' => $this->auth->acl_get('a_raidplaner'),
			'S_RAIDPLANER_PAGE' => 'index',
			'U_ACTION' => $this->u_action,
		));
		return $this->helper->render('raidplaner_index.html', $this->user->lang['RAIDPLANER_PAGE']);
	}
	
	
	public function view($raid_id)
	{
		if($this->config['clausi_raidplaner_active'] == 0) 
		{
			$this->template->assign_var('RAIDPLANER_MESSAGE', $this->user->lang['RAIDPLANER_INACTIVE']);
			return $this->helper->render('raidplaner_error.html', $this->user->lang['RAIDPLANER_PAGE'], 404);
		}
		if(!is_numeric($raid_id))
		{
			$this->template->assign_var('RAIDPLANER_MESSAGE', $this->user->lang['RAIDPLANER_INVALID_ID']);
			return $this->helper->render('raidplaner_error.html', $this->user->lang['RAIDPLANER_PAGE'], 404);
		}
		$message = false;

		$row_raid = $this->getRaidData($raid_id);
		
		$sql = "SELECT * FROM " . $this->container->getParameter('tables.clausi.raidplaner_attendees') . " WHERE raid_id = '".$raid_id."'";
		$result = $this->db->sql_query($sql);
		$row_attendees = $this->db->sql_fetchrowset($result);

		$this->db->sql_freeresult($result);
		
		foreach($this->roles as $role_id => $role_name)
			{
				$this->template->assign_block_vars('n_roleNames', array(
					'ROLE' => $role_id,
					'ROLELANG' => $this->user->lang[$this->roles[$role_id]],
				));
			}
		
		$i_status = 0;
		$len_status = count($this->status);
		$len_roles = count($this->roles);
		foreach($this->status as $status_id => $status_name)
		{
			$this->template->assign_block_vars('n_status', array(
				'STATUS' => $status_id,
				'STATUSNAME' => strtolower($status_name),
				'STATUSLANG' => $this->user->lang[$this->status[$status_id]],
			));
			$i_roles = 0;
			foreach($this->roles as $role_id => $role_name)
			{
				$this->template->assign_block_vars('n_status.n_roles', array(
					'ROLE' => $role_id,
					'ROLENAME' => strtolower($role_name),
					'ROLELANG' => $this->user->lang[$this->roles[$role_id]],
					'LASTELEMENT' => ($i_status == ($len_status-1) && $i_roles == ($len_roles-1)) ? true : false,
				));
				$i_roles++;
			}
			$i_status++;
		}
		
		$this->template->assign_vars(array(
			'U_RAIDPLANER' => $this->auth->acl_get('u_raidplaner'),
			'M_RAIDPLANER' => $this->auth->acl_get('m_raidplaner'),
			'A_RAIDPLANER' => $this->auth->acl_get('a_raidplaner'),
			'S_RAIDPLANER_PAGE' => 'index',
		));
		return $this->helper->render('raidplaner_view.html', $this->user->lang['RAIDPLANER_RAID'] . ': ' . $raid_id);
	}
	
	
	public function createRaid($schedule_id, $raid_time, $invite_time, $start_time, $end_time, $autoaccept)
	{
		$sql_ary = array(
			'schedule_id' => $schedule_id,
			'raid_time' => $raid_time,
			'invite_time' => $invite_time,
			'start_time' => $start_time,
			'end_time' => $end_time,
			'autoaccept' => $autoaccept,
		);
		$sql = 'INSERT INTO ' . $this->container->getParameter('tables.clausi.raidplaner_raids') . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
		$this->db->sql_query($sql);
		
		// Add attendees if autoaccept and raid in future
		if($autoaccept == 1 && $raid_time > time()) $this->addAttendees($this->db->sql_nextid());
	}
	
	
	public function addAttendees($raid_id)
	{
		$user_ary = $this->auth->acl_get_list(false, 'u_raidplaner', false);
		$this->cp = $this->container->get('profilefields.manager');

		foreach($user_ary as $permission)
		{
			$user_data = $this->cp->grab_profile_fields_data($permission['u_raidplaner']);
			foreach($permission['u_raidplaner'] as $user_id)
			{
				if($user_data[$user_id]['raidplaner_role']['value']-1 > 0 && $user_data[$user_id]['raidplaner_class']['value']-1 > 0)
				{
					$sql_ary_index = array(
						'user_id' => $user_id,
						'raid_id' => $raid_id,
					);
					$sql_ary = array(
						'role' => $user_data[$user_id]['raidplaner_role']['value']-1,
						'class' => $user_data[$user_id]['raidplaner_class']['value']-1,
						'status' => 1,
						'signup_time' => time(),
					);
					$sql = "INSERT INTO " . $this->container->getParameter('tables.clausi.raidplaner_attendees') . " 
						" . $this->db->sql_build_array('INSERT_SELECT', array_merge($sql_ary_index, $sql_ary)) . "
						FROM dual
							WHERE NOT EXISTS
							( SELECT *
								FROM " . $this->container->getParameter('tables.clausi.raidplaner_attendees') . "
								WHERE " . $this->db->sql_build_array('SELECT', $sql_ary_index) . " 
							);";
					$this->db->sql_query($sql);
				}
			}
		}
	}
	
	
	public function setUserstatus($raid_id, $status_id)
	{
		if( ! $this->auth->acl_get('u_raidplaner'))
		{
			if ($this->request->is_ajax())
			{
				$this->json_response->send(array(
					'MESSAGE_TITLE' => $this->user->lang['ERROR'],
					'MESSAGE_TEXT' => $this->user->lang['RAIDPLANER_INVALID_USER'],
				));
			}
			$this->template->assign_var('RAIDPLANER_MESSAGE', $this->user->lang['RAIDPLANER_INVALID_USER']);
			return $this->helper->render('raidplaner_error.html', $this->user->lang['RAIDPLANER_PAGE'], 403);
		}
		
		if( ! is_numeric($raid_id))
		{
			if ($this->request->is_ajax())
			{
				$this->json_response->send(array(
					'MESSAGE_TITLE' => $this->user->lang['ERROR'],
					'MESSAGE_TEXT' => $this->user->lang['RAIDPLANER_INVALID_ID'],
				));
			}
			$this->template->assign_var('RAIDPLANER_MESSAGE', $this->user->lang['RAIDPLANER_INVALID_ID']);
			return $this->helper->render('raidplaner_error.html', $this->user->lang['RAIDPLANER_PAGE'], 404);
		}
		
		if( ! is_numeric($status_id) || $status_id < 0 || $status_id > 3)
		{
			if ($this->request->is_ajax())
			{
				$this->json_response->send(array(
					'MESSAGE_TITLE' => $this->user->lang['ERROR'],
					'MESSAGE_TEXT' => $this->user->lang['RAIDPLANER_INVALID_STATUS'],
				));
			}
			$this->template->assign_var('RAIDPLANER_MESSAGE', $this->user->lang['RAIDPLANER_INVALID_STATUS']);
			return $this->helper->render('raidplaner_error.html', $this->user->lang['RAIDPLANER_PAGE'], 500);
		}
		
		$raid_data = $this->getRaidData($raid_id);
		if( $raid_data['raid_time'] < time())
		{
			if ($this->request->is_ajax())
			{
				$this->json_response->send(array(
					'MESSAGE_TITLE' => $this->user->lang['ERROR'],
					'MESSAGE_TEXT' => $this->user->lang['RAIDPLANER_INVALID_RAID'],
				));
			}
			$this->template->assign_var('RAIDPLANER_MESSAGE', $this->user->lang['RAIDPLANER_INVALID_RAID']);
			return $this->helper->render('raidplaner_error.html', $this->user->lang['RAIDPLANER_PAGE'], 403);
		}
		
		$user_id = $this->user->data['user_id'];
		
		$this->u_action = $this->helper->route('clausi_raidplaner_controller_status', array('raid_id' => $raid_id, 'status_id' => $status_id));
		
		// Get current status
		$currentStatus = $this->getStatus($raid_id, $user_id);
		if($currentStatus == 4 && confirm_box(false, 'ACCEPTED_TITLE', '', 'raidplaner_confirm.html', $this->root_path.ltrim($this->u_action, '/')))
		{
			
		}
		else
		{
			if (confirm_box(true))
			{
				$this->setStatus($raid_id, $user_id, $status_id);
				$comment = $this->request->variable('comment', '');
				$this->setComment($raid_id, $user_id, $comment);
				
				$row_count = $this->getRaidmemberCount($raid_id);
				foreach($row_count as $raid)
				{
					if($raid['raid_id'] == $raid_id) $row_count = $raid;
				}
				
				$response = array(
					'MESSAGE_TITLE' => $this->user->lang['STATUS_CHANGE_TITLE'],
					'MESSAGE_TEXT' => sprintf($this->user->lang['STATUS_CHANGE_TEXT'], $this->user->lang[$this->status[$status_id]], $raid_id, $this->user->format_date($raid_data['raid_time'])),
					'RAID_ID' => $raid_id,
					'STATUS_ID' => $status_id,
					'ATTENDING' => $row_count['attending'],
					'DECLINE' => $row_count['decline'],
					'SUBSTITUTE' => $row_count['substitute'],
					// 'ACCEPT' => $row_count['accept'],
					// 'RAIDSIZE' => $raid_data['raidsize'],
				);

				if ($this->request->is_ajax())
				{
					$this->json_response->send($response);
				}

				$this->template->assign_vars($response);
				return $this->helper->render('raidplaner_status.html', $this->user->lang['RAIDPLANER_PAGE']);
			}
			else
			{
				//display mode
				confirm_box(false, 'STATUSCHANGE_TITLE', '', 'raidplaner_confirm.html', $this->root_path.ltrim($this->u_action, '/'));
			}
		}
	}
	
	
	private function updateAllStatus()
	{
		if( ! $this->auth->acl_get('u_raidplaner'))
		{
			$this->template->assign_var('RAIDPLANER_MESSAGE', $this->user->lang['RAIDPLANER_INVALID_USER']);
			return $this->helper->render('raidplaner_error.html', $this->user->lang['RAIDPLANER_PAGE'], 403);
		}
		
		$status_id = $this->request->variable('change_all_status', 0);
		if( ! is_numeric($status_id) || $status_id < 0 || $status_id > 3)
		{
			$this->template->assign_var('RAIDPLANER_MESSAGE', $this->user->lang['RAIDPLANER_INVALID_STATUS']);
			return $this->helper->render('raidplaner_error.html', $this->user->lang['RAIDPLANER_PAGE'], 500);
		}
		
		$status = $this->request->variable('status', array(0 => 0));
		foreach($status as $raid_id => $status_value)
		{
			if( ! is_numeric($raid_id))
			{
				$this->template->assign_var('RAIDPLANER_MESSAGE', $this->user->lang['RAIDPLANER_INVALID_ID']);
				return $this->helper->render('raidplaner_error.html', $this->user->lang['RAIDPLANER_PAGE'], 404);
			}
			
			$raid_data = $this->getRaidData($raid_id);
			if( $raid_data['raid_time'] < time())
			{
				continue;
			}
			
			if( $status_value == 1)
			{
				$user_id = $this->user->data['user_id'];
				
				$this->setStatus($raid_id, $user_id, $status_id);
				
				$comment = $this->request->variable('comment', '');
				$this->setComment($raid_id, $user_id, $comment);
			}
		}
	}
	
	private function setComment($raid_id, $user_id, $comment)
	{
		$sql_ary = array(
			'comment' => $comment,
			'change_time' => time(),
		);
		$sql = "UPDATE " . $this->container->getParameter('tables.clausi.raidplaner_attendees') . " 
			SET " . $this->db->sql_build_array('UPDATE', $sql_ary) . " 
			WHERE raid_id = " . $raid_id . " AND user_id = '". $user_id ."'";
		$this->db->sql_query($sql);
		
		if($this->db->sql_affectedrows() == 0)
		{
			$this->cp = $this->container->get('profilefields.manager');
			$user_data = $this->cp->grab_profile_fields_data($user_id);
			if( empty($user_data[$user_id]['raidplaner_role']['value']) || empty($user_data[$user_id]['raidplaner_class']['value']))
			{
				$this->template->assign_var('RAIDPLANER_MESSAGE', $this->user->lang['RAIDPLANER_INVALID_USER']);
				return $this->helper->render('raidplaner_error.html', $this->user->lang['RAIDPLANER_PAGE'], 500);
			}

			$sql_ary = array(
				'user_id' => $user_id,
				'raid_id' => $raid_id,
				'role' => $user_data[$user_id]['raidplaner_role']['value']-1,
				'class' => $user_data[$user_id]['raidplaner_class']['value']-1,
				'status' => $status_id,
				'comment' => $comment,
				'signup_time' => time(),
			);
			$sql = 'INSERT INTO ' . $this->container->getParameter('tables.clausi.raidplaner_attendees') . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
			$this->db->sql_query($sql);
		}
	}
	
	
	private function getStatus($raid_id, $user_id)
	{
		$sql_ary = array(
			'raid_id' => $raid_id,
			'user_id' => $user_id,
		);
		$sql = "SELECT status FROM " .  $this->container->getParameter('tables.clausi.raidplaner_attendees') . " WHERE "  . $this->db->sql_build_array('SELECT', $sql_ary);
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		
		return $row['status'];
	}
	
	
	private function setStatus($raid_id, $user_id, $status_id)
	{
		$sql_ary = array(
			'status' => $status_id,
			'change_time' => time(),
		);
		$sql = "UPDATE " . $this->container->getParameter('tables.clausi.raidplaner_attendees') . " 
			SET " . $this->db->sql_build_array('UPDATE', $sql_ary) . " 
			WHERE raid_id = " . $raid_id . " AND user_id = '". $user_id ."'";
		$this->db->sql_query($sql);
		
		if($this->db->sql_affectedrows() == 0)
		{
			$this->cp = $this->container->get('profilefields.manager');
			$user_data = $this->cp->grab_profile_fields_data($user_id);
			if( empty($user_data[$user_id]['raidplaner_role']['value']) || empty($user_data[$user_id]['raidplaner_class']['value']))
			{
				$this->template->assign_var('RAIDPLANER_MESSAGE', $this->user->lang['RAIDPLANER_INVALID_USER']);
				return $this->helper->render('raidplaner_error.html', $this->user->lang['RAIDPLANER_PAGE'], 500);
			}

			$sql_ary = array(
				'user_id' => $user_id,
				'raid_id' => $raid_id,
				'role' => $user_data[$user_id]['raidplaner_role']['value']-1,
				'class' => $user_data[$user_id]['raidplaner_class']['value']-1,
				'status' => $status_id,
				'signup_time' => time(),
			);
			$sql = 'INSERT INTO ' . $this->container->getParameter('tables.clausi.raidplaner_attendees') . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
			$this->db->sql_query($sql);
		}
	}
	
	
	private function getRaidmemberCount($raid_id = 0)
	{
		if($raid_id == 0) $where = 'GROUP BY raid_id';
		else $where = "WHERE raid_id = '".$raid_id."'";
		
		$sql = "SELECT 
			raid_id,
			SUM(IF(status = 1, 1, 0)) AS attending,
			SUM(IF(status = 2, 1, 0)) AS decline,
			SUM(IF(status = 3, 1, 0)) AS substitute,
			SUM(IF(status = 4, 1, 0)) AS accept
			FROM " . $this->container->getParameter('tables.clausi.raidplaner_attendees') ."
			".$where."
			";
		$result_count = $this->db->sql_query($sql);
		$row_count = $this->db->sql_fetchrowset($result_count);
		$this->db->sql_freeresult($result_count);
		
		return $row_count;
	}
	
	
	private function getRaidData($raid_id)
	{
		$sql = "SELECT r.*, e.name, e.raidsize FROM 
			" . $this->container->getParameter('tables.clausi.raidplaner_raids') . " r,
			" . $this->container->getParameter('tables.clausi.raidplaner_events') . " e, 
			" . $this->container->getParameter('tables.clausi.raidplaner_schedule') . " s 
			WHERE 
				r.raid_id = '". $raid_id ."'
				AND r.deleted = '0' 
				AND s.schedule_id = r.schedule_id  
				AND e.event_id = s.event_id				
			ORDER BY r.raid_time";
		$result = $this->db->sql_query($sql);
		
		$row_raid = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		
		return $row_raid;
	}
	
	
	private function var_display($var)
	{
		echo "<pre>";
		print_r($var);
		echo "</pre>";
	}
	
}
