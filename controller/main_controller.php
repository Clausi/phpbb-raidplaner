<?php

namespace clausi\raidplaner\controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

// TODO: find a better way to include privmsgs
if (defined('ADMIN_START')) $phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './../';
else $phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
require_once($phpbb_root_path . 'includes/functions_privmsgs.php');

class main_controller implements main_interface
{
	protected $config;
	protected $helper;
	protected $template;
	protected $user;
	protected $auth;
	protected $cp;
	protected $container;
	
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
	
	protected $classes = [
		1 => "warrior",
		2 => "paladin",
		3 => "hunter",
		4 => "rogue",
		5 => "priest",
		6 => "deathknight",
		7 => "shaman",
		8 => "mage",
		9 => "warlock",
		10 => "monk",
		11 => "druid",
	];
	
	protected $raidsTable;
	protected $eventsTable;
	protected $scheduleTable;
	protected $attendeeTable;
	protected $userTable;
	protected $statisticTable;
	protected $logsTable;


	public function __construct(\phpbb\config\config $config, \phpbb\auth\auth $auth, \phpbb\controller\helper $helper, \phpbb\db\driver\driver_interface $db, \phpbb\template\template $template, \phpbb\user $user, \phpbb\request\request $request, ContainerInterface $container)
	{
		$this->config = $config;
		$this->auth = $auth;
		$this->helper = $helper;
		$this->db = $db;
		$this->template = $template;
		$this->user = $user;
		$this->request = $request;
		$this->container = $container;
				
		$this->json_response = new \phpbb\json_response;
		
		$this->raidsTable = $this->container->getParameter('tables.clausi.raidplaner_raids');
		$this->eventsTable = $this->container->getParameter('tables.clausi.raidplaner_events');
		$this->scheduleTable = $this->container->getParameter('tables.clausi.raidplaner_schedule');
		$this->attendeeTable = $this->container->getParameter('tables.clausi.raidplaner_attendees');
		$this->userTable = $this->container->getParameter('tables.users');
		$this->statisticTable = $this->container->getParameter('tables.clausi.raidplaner_statistics');
		$this->logsTable = $this->container->getParameter('tables.clausi.raidplaner_logs');
	}

	
	public function index()
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
		
		$user_id = $this->user->data['user_id'];
		
		// get all raids up to one year ago
		// TODO Archive
		$sql = "SELECT r.*, e.name, e.raidsize FROM 
			" . $this->raidsTable . " r,
			" . $this->eventsTable . " e, 
			" . $this->scheduleTable . " s 
			WHERE 
				r.deleted = '0' 
				AND r.raid_time > '".(time()-31536000)."'
				AND s.schedule_id = r.schedule_id  
				AND e.event_id = s.event_id 
			ORDER BY r.raid_time";
		$result = $this->db->sql_query($sql);
		
		$sql = "SELECT status, raid_id FROM 
			" . $this->attendeeTable ." 
			WHERE
				user_id = '" . $user_id . "' 
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
			
			$flags = OPTION_FLAG_BBCODE + OPTION_FLAG_SMILIES + OPTION_FLAG_LINKS;
			$row['note'] = generate_text_for_display($row['note'], $row['bbcode_uid'], $row['bbcode_bitfield'], $flags);

			$this->template->assign_block_vars('n_raids', array(
				'EVENTNAME' => $row['name'],
				'RAIDSIZE' => $row['raidsize'],
				'ID' => $row['raid_id'],
				'DATE' => $this->user->format_date($row['raid_time']),
				'TIMESTAMP' => $row['raid_time'],
				'FLAG' => ($raid_endtime < time()) ? 'past' : 'future',
				'FIRSTFUTURE' => $firstfuture,
				'DAY' => $this->user->lang(array('datetime', strftime ('%A', $row['raid_time']))),
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
				'U_COMMENT' => $this->helper->route('clausi_raidplaner_controller_comment', array('raid_id' => $row['raid_id'])),
				'U_RAID' => $this->helper->route('clausi_raidplaner_controller_view', array('raid_id' => $row['raid_id'])),
			));
			$memberCount = ['attending' => 0, 'decline' => 0, 'substitute' => 0, 'accept' => 0];
			if($raid_endtime >= time()) $firstfuture = 2;
		}
		$this->db->sql_freeresult($result);
		
		if($this->auth->acl_get('u_raidplaner'))
		{
			$u_raidplaner = true;
			$user_profile = $this->getUserProfileFields($user_id);
		}
		else $u_raidplaner = false;
		
		$this->getRaidstatistics();
		
		$this->template->assign_vars(array(
			'U_RAIDPLANER' => ($u_raidplaner && !empty($user_profile['role']) && !empty($user_profile['class'])),
			'M_RAIDPLANER' => $this->auth->acl_get('m_raidplaner'),
			'A_RAIDPLANER' => $this->auth->acl_get('a_raidplaner'),
			'RAIDPLANER_INDEX' => true,
			'S_CLAUSI_RAIDPLANER_PAGE' => true,
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
		$user_id = $this->user->data['user_id'];
		
		$this->template->assign_vars(array(
			'RAID_ID' => $raid_id,
			'RAIDSIZE' => $row_raid['raidsize'],
			'EVENTNAME' => $row_raid['name'],
			'DATE' => $this->user->format_date($row_raid['raid_time']),
			'DAY' => $this->user->lang(array('datetime', strftime ('%A', $row_raid['raid_time']))),
			'INVITE_TIME' => $row_raid['invite_time'],
			'START_TIME' => $row_raid['start_time'],
			'END_TIME' => $row_raid['end_time'],
			'FLAG' => ($row_raid['raid_time'] < time()) ? 'past' : 'future',
			
			'USERSTATUS' => $this->getStatus($raid_id, $user_id),
			
			'U_COMMENT' => $this->helper->route('clausi_raidplaner_controller_comment', array('raid_id' => $raid_id)),
			'U_STATUS' => $this->helper->route('clausi_raidplaner_controller_status', array('raid_id' => $raid_id, 'status_id' => 0)),
		));
		
		$flags = OPTION_FLAG_BBCODE + OPTION_FLAG_SMILIES + OPTION_FLAG_LINKS;
		
		if( $this->auth->acl_get('m_raidplaner'))
		{
			add_form_key('clausi/raidplaner');
			$row_raid['note'] = generate_text_for_edit($row_raid['note'], $row_raid['bbcode_uid'], $row_raid['bbcode_bitfield'], $flags);
			$this->template->assign_vars(array(
				'RAID_NOTE' => $row_raid['note']['text'],
			));
			
			$this->getRaidlog($raid_id);
			
			// Get ids of last 6 raids
			$sql_ary = array(
				'deleted' => 0,
			);
			$sql = "SELECT raid_id, raid_time FROM " .  $this->raidsTable . " 
				WHERE " . $this->db->sql_build_array('SELECT', $sql_ary) ."
				AND raid_time < ".$row_raid['raid_time'] ."
				ORDER BY raid_time DESC LIMIT 6";
			$result = $this->db->sql_query($sql);
			$row_lastraids = $this->db->sql_fetchrowset($result);
			$this->db->sql_freeresult($result);
		}
		else
		{
			$row_raid['note'] = generate_text_for_display($row_raid['note'], $row_raid['bbcode_uid'], $row_raid['bbcode_bitfield'], $flags);
			$this->template->assign_var('RAID_NOTE', $row_raid['note']);
		}
		
		$row_attendees = $this->getAttendees($raid_id);
		
		if($row_raid['raid_time'] >= time())
		{
			// Get all users with U_RAIDPLANER
			$user_ary = $this->auth->acl_get_list(false, 'u_raidplaner', false);
			$user_ary = $user_ary[0]['u_raidplaner'];
			
			// Remove removed attendees 
			$this->removeAttendees($raid_id, $row_attendees, $user_ary);
			
			// Create a similar $attendee_ary as $user_ary
			$attendee_ary = array();
			$i = 0;
			foreach($row_attendees as $attendee)
			{
				$attendee_ary[$i] = $attendee['user_id'];
				$i++;
			}

			$diff = array_diff($user_ary, $attendee_ary);
			
			if(is_array($diff) && count($diff) > 0)
			{
				// Add missing member
				if($row_raid['autoaccept'] == 1)
				{
					$this->addAttendees($raid_id);
				}
				$row_attendees = $this->getAttendees($raid_id);
			}
		}
		
		$row_count = $this->getRaidmemberCount($raid_id);
		
		$sql = "SELECT user_id, username FROM " . $this->userTable . "";
		$result = $this->db->sql_query($sql);
		$row_user = $this->db->sql_fetchrowset($result);		
		$this->db->sql_freeresult($result);
		
		foreach($this->roles as $role_id => $role_name)
		{
			$this->template->assign_block_vars('n_roleNames', array(
				'ROLE' => $role_id,
				'ROLENAME' => strtolower($role_name),
				'ROLELANG' => $this->user->lang[$this->roles[$role_id]],
			));
		}
		
		$currentUsername = '';
		$currentUserId = '';
		$i_status = 0;
		$len_status = count($this->status);
		$len_roles = count($this->roles);

		foreach($this->status as $status_id => $status_name)
		{
			$this->template->assign_block_vars('n_status', array(
				'STATUS' => $status_id,
				'STATUSNAME' => strtolower($status_name),
				'STATUSLANG' => $this->user->lang[$this->status[$status_id]],
				'MEMBERS_COUNT' => (!empty($row_count[0][strtolower($status_name)])) ? $row_count[0][strtolower($status_name)] : 0,
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
				
				foreach($row_attendees as $attendee)
				{
					if($attendee['role'] == $role_id && $attendee['status'] == $status_id)
					{
						foreach($row_user as $user)
						{
							if($user['user_id'] == $attendee['user_id'])
							{
								$currentUserId = $user['user_id'];
								$currentUsername = $user['username'];
								break;
							}
						}
						
						$user_profile = $this->getUserProfileFields($currentUserId);
						
						if(!empty($user_profile['charname'])) $currentCharname = $user_profile['charname'];
						else $currentCharname = '';

						$this->template->assign_block_vars('n_status.n_roles.n_users', array(
							'USER_ID' => $currentUserId,
							'USERNAME' => $currentUsername,
							'CHARNAME' => $currentCharname,
							'ROLENAME' => strtolower($role_name),
							'CLASSNAME' => $this->classes[$attendee['class']],
							'COMMENT' => $attendee['comment'],
						));
						
						if( $this->auth->acl_get('m_raidplaner'))
						{
							foreach($row_lastraids as $lastraid)
							{
								$past_status = $this->getStatus($lastraid['raid_id'], $currentUserId);
								if(is_numeric($past_status))
								{
									$this->template->assign_block_vars('n_status.n_roles.n_users.n_lastraids', array(
										'RAID_DATE' => date('d.m.Y', $lastraid['raid_time']),
										'STATUS' => $this->user->lang($this->status[$past_status]),
									));
								}
							}
						}
					}
				}
			}
			$i_status++;
		}
		
		if($this->auth->acl_get('u_raidplaner'))
		{
			$u_raidplaner = true;
			$user_profile = $this->getUserProfileFields($user_id);
		}
		else $u_raidplaner = false;

		$this->template->assign_vars(array(
			'S_CLAUSI_RAIDPLANER_PAGE' => true,
			'RAIDPLANER_VIEW' => true,
			'U_RAIDPLANER' => ($u_raidplaner && !empty($user_profile['role']) && !empty($user_profile['class'])),
			'M_RAIDPLANER' => $this->auth->acl_get('m_raidplaner'),
			'A_RAIDPLANER' => $this->auth->acl_get('a_raidplaner'),
			'U_MODSTATUSCHANGE' => ($this->auth->acl_get('m_raidplaner')) ? $this->helper->route('clausi_raidplaner_controller_modstatus', array('raid_id' => $raid_id)) : '',
			'U_MODALLSTATUSCHANGE' => ($this->auth->acl_get('m_raidplaner')) ? $this->helper->route('clausi_raidplaner_controller_modallstatus', array('raid_id' => $raid_id)) : '',
		));
		return $this->helper->render('raidplaner_view.html', $this->user->lang['RAIDPLANER_RAID'] . ': ' . $raid_id);
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
		
		if( ! is_numeric($status_id) || $status_id < 1 || $status_id > 3)
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
		$currentAttendee = $this->getAttendee($raid_id, $user_id);
		$this->template->assign_vars(array(
			'COMMENT_WARNING' => ($status_id == 3 || $status_id == 2) ? true : false,
			'COMMENT' => $currentAttendee['comment'],
		));
		
		if($currentAttendee['status'] == 4 && confirm_box(false, 'ACCEPTED_TITLE', '', 'raidplaner_confirm.html', $this->u_action))
		{
			
		}
		else
		{
			if (confirm_box(true))
			{
				$comment = $this->request->variable('comment', '', true);
				if(($status_id == 2 || $status_id == 3) && ! $this->validComment($comment))
				{
					$response = array(
						'MESSAGE_TITLE' => $this->user->lang('RAIDPLANER_COMMENT_SHORT_TITLE'),
						'MESSAGE_TEXT' => $this->user->lang('RAIDPLANER_COMMENT_SHORT'),
					);
					
					if ($this->request->is_ajax())
					{
						$this->json_response->send($response);
					}

					$this->template->assign_vars($response);
					return $this->helper->render('raidplaner_status.html', $this->user->lang['RAIDPLANER_PAGE']);
				}
				
				$this->setStatus($raid_id, $user_id, $status_id, $currentAttendee['status']);
				$this->setComment($raid_id, $user_id, $comment);
				
				$this->raidLog($raid_id, $user_id, $status_id, $currentAttendee['role'], $comment, $user_id);
				
				$row_count = $this->getRaidmemberCount($raid_id);
				foreach($row_count as $raid)
				{
					if($raid['raid_id'] == $raid_id) $row_count = $raid;
				}
				
				$response = array(
					'MESSAGE_TITLE' => $this->user->lang['STATUS_CHANGE_TITLE'],
					'MESSAGE_TEXT' => sprintf($this->user->lang['STATUS_CHANGE_TEXT'], $this->user->lang[$this->status[$status_id]], $raid_id, $this->user->format_date($raid_data['raid_time'])),
					'USER_ID' => $user_id,
					'RAID_ID' => $raid_id,
					'STATUS_ID' => $status_id,
					'OLD_STATUSNAME' => ( !empty($this->status[$currentAttendee['status']]) ) ? strtolower($this->status[$currentAttendee['status']]) : 0,
					'OLD_ROLENAME' => ( !empty($this->roles[$currentAttendee['role']]) ) ? strtolower($this->roles[$currentAttendee['role']]) : 0,
					'STATUSNAME' => strtolower($this->status[$status_id]),
					'ROLENAME' => strtolower($this->roles[$this->getRole($raid_id, $user_id)]),
					'ATTENDING' => $row_count['attending'],
					'DECLINE' => $row_count['decline'],
					'SUBSTITUTE' => $row_count['substitute'],
					'NEW_COMMENT' => $comment,
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
				$this->template->assign_vars(array(
					'U_ACTION' => $this->u_action,
				));
				confirm_box(false, 'STATUSCHANGE_TITLE', '', 'raidplaner_confirm.html', $this->u_action);
			}
		}
	}
	
	
	public function setUsercomment($raid_id)
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
		
		$this->u_action = $this->helper->route('clausi_raidplaner_controller_comment', array('raid_id' => $raid_id));
		
		$currentAttendee = $this->getAttendee($raid_id, $user_id);
		$status_id = $currentAttendee['status'];
		$this->template->assign_var('COMMENT_WARNING', ($status_id == 3 || $status_id == 2) ? true : false);

		if(confirm_box(true))
		{
			$comment = $this->request->variable('comment', '', true);
			if(($status_id == 2 || $status_id == 3) && ! $this->validComment($comment))
			{
				$response = array(
					'MESSAGE_TITLE' => $this->user->lang('RAIDPLANER_COMMENT_SHORT_TITLE'),
					'MESSAGE_TEXT' => $this->user->lang('RAIDPLANER_COMMENT_SHORT'),
				);
				
				if ($this->request->is_ajax())
				{
					$this->json_response->send($response);
				}

				$this->template->assign_vars($response);
				return $this->helper->render('raidplaner_status.html', $this->user->lang['RAIDPLANER_PAGE']);
			}
			
			$this->setComment($raid_id, $user_id, $comment);
			
			$this->raidLog($raid_id, $user_id, $status_id, $currentAttendee['role'], $comment, $user_id);
			
			$response = array(
				'MESSAGE_TITLE' => $this->user->lang['COMMENT_CHANGE_TITLE'],
				'MESSAGE_TEXT' => sprintf($this->user->lang['COMMENT_CHANGE_TEXT'], $raid_id, $this->user->format_date($raid_data['raid_time'])),
				'NEW_COMMENT' => $comment,
				'USER_ID' => $user_id,
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
			$this->template->assign_vars(array(
				'COMMENT' => $currentAttendee['comment'],
				'U_ACTION' => $this->u_action,
			));

			confirm_box(false, 'COMMENT_TITLE', '', 'raidplaner_confirm.html', $this->u_action);
		}
	}
	
	
	private function setModRaidnote($raid_id)
	{
		$note = $this->request->variable('note', '', true);
		$errors = generate_text_for_storage($note, $uid, $bitfield, $flags, true, true, true);
		
		if(sizeof($errors))
		{
			$response = array(
				'MESSAGE_TITLE' => $this->user->lang['RAIDPLANER_ERROR'],
				'MESSAGE_TEXT' => implode('<br />', $errors),
			);
			if ($this->request->is_ajax())
			{
				$this->json_response->send($response);
			}
			$this->template->assign_vars($response);
			return $this->helper->render('raidplaner_status.html', $this->user->lang['RAIDPLANER_PAGE']);
		}

		$this->setRaidnote($raid_id, $note, $uid, $bitfield);
	}
	
	
	public function setModstatus($raid_id)
	{
		if( ! $this->auth->acl_get('m_raidplaner'))
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
		
		$status_id = $this->request->variable('status_id', 0);
		if( ! is_numeric($status_id) || $status_id < 1 || $status_id > 4)
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
		
		$role_id = $this->request->variable('role_id', 0);
		if( ! is_numeric($role_id) || $role_id < 1 || $role_id > 4)
		{
			if ($this->request->is_ajax())
			{
				$this->json_response->send(array(
					'MESSAGE_TITLE' => $this->user->lang['ERROR'],
					'MESSAGE_TEXT' => $this->user->lang['RAIDPLANER_INVALID_ROLE'],
				));
			}
			$this->template->assign_var('RAIDPLANER_MESSAGE', $this->user->lang['RAIDPLANER_INVALID_ROLE']);
			return $this->helper->render('raidplaner_error.html', $this->user->lang['RAIDPLANER_PAGE'], 500);
		}
		
		$user_id = $this->request->variable('user_id', 0);
		if( ! is_numeric($user_id) || $user_id == 0)
		{
			if ($this->request->is_ajax())
			{
				$this->json_response->send(array(
					'MESSAGE_TITLE' => $this->user->lang['ERROR'],
					'MESSAGE_TEXT' => $this->user->lang['RAIDPLANER_INVALID_USERID'],
				));
			}
			$this->template->assign_var('RAIDPLANER_MESSAGE', $this->user->lang['RAIDPLANER_INVALID_USERID']);
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
		
		$currentAttendee = $this->getAttendee($raid_id, $user_id);
		$this->setStatus($raid_id, $user_id, $status_id, 0, $role_id, true);
		if($currentAttendee['status'] != $status_id || $currentAttendee['role'] != $role_id) $this->raidLog($raid_id, $this->user->data['user_id'], $status_id, $role_id, '', $user_id);
		
		$this->json_response->send(array(
			'statusupdate' => true,
		));
		
		return $this->helper->render('raidplaner_status.html', $this->user->lang['RAIDPLANER_PAGE']);
	}
	
	public function setModAllstatus($raid_id)
	{
		if( ! $this->auth->acl_get('m_raidplaner'))
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
		
		add_form_key('clausi/raidplaner');
		if (!check_form_key('clausi/raidplaner'))
		{
			trigger_error('FORM_INVALID');
		}
		
		$this->setModRaidnote($raid_id);
		
		foreach($this->status as $status_id => $statusName)
		{
			foreach($this->roles as $role_id => $roleName)
			{
				$current = $this->request->variable(strtolower($statusName) . '_' . strtolower($roleName), '');
				$current = explode(',', $current);
				foreach($current as $user_id)
				{
					if($user_id && $user_id != 0)
					{
						$currentAttendee = $this->getAttendee($raid_id, $user_id);
						$this->setStatus($raid_id, $user_id, $status_id, 0, $role_id, true);
						if($currentAttendee['status'] != $status_id || $currentAttendee['role'] != $role_id) $this->raidLog($raid_id, $this->user->data['user_id'], $status_id, $role_id, '', $user_id);
					}
				}
			}
		}
		
		$response = array(
			'MESSAGE_TITLE' => $this->user->lang['STATUS_PREVIEW_SAVE'],
			'MESSAGE_TEXT' => sprintf($this->user->lang['STATUS_PREVIEW_TEXT'], $raid_id, $this->user->format_date($raid_data['raid_time'])),
		);
		
		if ($this->request->is_ajax())
		{
			$this->json_response->send($response);
		}
		$this->template->assign_vars($response);
		return $this->helper->render('raidplaner_status.html', $this->user->lang['RAIDPLANER_PAGE']);
	}
	
	
	/**
	* Private functions
	**/
	
	private function setRaidnote($raid_id, $note, $uid, $bitfield)
	{
		$sql_ary = array(
			'note' => $note,
			'bbcode_bitfield' => $bitfield,
			'bbcode_uid' => $uid,
		);
		$sql = "UPDATE " . $this->raidsTable . " 
			SET " . $this->db->sql_build_array('UPDATE', $sql_ary) . " 
			WHERE raid_id = " . $raid_id ."";
		$this->db->sql_query($sql);
	}
	
	
	private function updateAllStatus()
	{
		if( ! $this->auth->acl_get('u_raidplaner'))
		{
			$this->template->assign_var('RAIDPLANER_MESSAGE', $this->user->lang['RAIDPLANER_INVALID_USER']);
			return $this->helper->render('raidplaner_error.html', $this->user->lang['RAIDPLANER_PAGE'], 403);
		}
		
		$status_id = $this->request->variable('change_all_status', 0);
		if( ! is_numeric($status_id) || $status_id < 1 || $status_id > 3)
		{
			$this->template->assign_var('RAIDPLANER_MESSAGE', $this->user->lang['RAIDPLANER_INVALID_STATUS']);
			return $this->helper->render('raidplaner_error.html', $this->user->lang['RAIDPLANER_PAGE'], 500);
		}
		
		$comment = $this->request->variable('comment_all', '', true);
		if(($status_id == 2 || $status_id == 3) && ! $this->validComment($comment))
		{
			meta_refresh(5, $this->u_action);
			trigger_error($this->user->lang('RAIDPLANER_COMMENT_SHORT') . '<br /><br />' . sprintf($this->user->lang['RETURN_INDEX'], '<a href="' . str_replace('&', '&amp;', $this->u_action) . '">', '</a>'));
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
				$currentAttendee = $this->getAttendee($raid_id, $user_id);
				$currentStatus = $currentAttendee['status'];
				
				$this->setStatus($raid_id, $user_id, $status_id, $currentStatus);

				$this->setComment($raid_id, $user_id, $comment);
				
				$this->raidLog($raid_id, $user_id, $status_id, $currentAttendee['role'], $comment, $user_id);
			}
		}
	}
	
	
	private function setComment($raid_id, $user_id, $comment)
	{
		$sql_ary = array(
			'comment' => $comment,
			'change_time' => time(),
		);
		$sql = "UPDATE " . $this->attendeeTable . " 
			SET " . $this->db->sql_build_array('UPDATE', $sql_ary) . " 
			WHERE raid_id = " . $raid_id . " AND user_id = '". $user_id ."'";
		$result = $this->db->sql_query($sql);
		
		$currentStatus = $this->getStatus($raid_id, $user_id);

		if($this->db->sql_affectedrows() <= 0)
		{
			$user_profile = $this->getUserProfileFields($user_id);
			if( empty($user_profile['role']) || empty($user_profile['class']))
			{
				$this->template->assign_var('RAIDPLANER_MESSAGE', $this->user->lang['RAIDPLANER_INVALID_USER']);
				return $this->helper->render('raidplaner_error.html', $this->user->lang['RAIDPLANER_PAGE'], 500);
			}

			$sql_ary = array(
				'user_id' => $user_id,
				'raid_id' => $raid_id,
				'role' => $user_profile['role'],
				'class' => $user_profile['class'],
				'status' => $currentStatus,
				'comment' => $comment,
				'signup_time' => time(),
			);
			$sql = 'INSERT INTO ' . $this->attendeeTable . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
			$this->db->sql_query($sql);
		}
		
		// Send message if user was accepted
		if($currentStatus == 4)
		{
			$mod_ary = $this->auth->acl_get_list(false, 'm_raidplaner', false);
			foreach($mod_ary[0]['m_raidplaner'] as $mod)
			{
				$user_to[$mod] = 'bcc';
			}
			$to = array('u' => $user_to);
			
			$raid_data = $this->getRaidData($raid_id);
			
			$subject = sprintf($this->user->lang['PM_SUBJECT_COMMENT'], $this->user->data['username'], date('d.m.Y', $raid_data['raid_time']));
			$message = sprintf($this->user->lang['PM_MESSAGE_COMMENT'], 
				$this->user->data['username'],
				$this->helper->route('clausi_raidplaner_controller_view', array('raid_id' => $raid_id), true, false, UrlGeneratorInterface::ABSOLUTE_URL),
				$raid_id,
				date('d.m.Y', $raid_data['raid_time']),
				$comment
			);
			
			$this->sendPm($subject, $message, $to);
		}
	}
	
	
	private function getComment($raid_id, $user_id)
	{
		$sql_ary = array(
			'raid_id' => $raid_id,
			'user_id' => $user_id,
		);
		$sql = "SELECT comment FROM " .  $this->attendeeTable . " WHERE "  . $this->db->sql_build_array('SELECT', $sql_ary);
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		
		return $row['comment'];
	}
	
	
	private function getRole($raid_id, $user_id)
	{
		$sql_ary = array(
			'raid_id' => $raid_id,
			'user_id' => $user_id,
		);
		$sql = "SELECT role FROM " .  $this->attendeeTable . " WHERE "  . $this->db->sql_build_array('SELECT', $sql_ary);
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		
		return $row['role'];
	}
	
	
	private function getClass($raid_id, $user_id)
	{
		$sql_ary = array(
			'raid_id' => $raid_id,
			'user_id' => $user_id,
		);
		$sql = "SELECT class FROM " .  $this->attendeeTable . " WHERE "  . $this->db->sql_build_array('SELECT', $sql_ary);
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		
		return $row['class'];
	}
	
	
	// Get all attendees of raid
	private function getAttendees($raid_id)
	{
		$sql = "SELECT * FROM " . $this->attendeeTable . " WHERE raid_id = '".$raid_id."' ORDER BY class, user_id";
		$result = $this->db->sql_query($sql);
		$row_attendees = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);
		
		return $row_attendees;
	}
	
	
	// Get specific attendee of raid
	private function getAttendee($raid_id, $user_id)
	{
		$sql_ary = array(
			'raid_id' => $raid_id,
			'user_id' => $user_id,
		);
		$sql = "SELECT * FROM " .  $this->attendeeTable . " WHERE "  . $this->db->sql_build_array('SELECT', $sql_ary);
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		
		return $row;
	}
	
	
	private function getStatus($raid_id, $user_id)
	{
		$sql_ary = array(
			'raid_id' => $raid_id,
			'user_id' => $user_id,
		);
		$sql = "SELECT status FROM " .  $this->attendeeTable . " WHERE "  . $this->db->sql_build_array('SELECT', $sql_ary);
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		
		return $row['status'];
	}
	
	
	private function setStatus($raid_id, $user_id, $status_id, $old_status = 0, $role_id = 0, $mod = false)
	{
		if( $role_id == 0)
		{
			$role_id = $this->getRole($raid_id, $user_id);
		}
		
		if($mod && $this->auth->acl_get('m_raidplaner')) $sql_mod = array('adminchange_time' => time());
		else {
			// Send message if user was accepted
			if($old_status == 4 && $status_id != 4 && $old_status != 0)
			{
				$mod_ary = $this->auth->acl_get_list(false, 'm_raidplaner', false);
				foreach($mod_ary[0]['m_raidplaner'] as $mod)
				{
					$user_to[$mod] = 'bcc';
				}
				$to = array('u' => $user_to);
				
				$raid_data = $this->getRaidData($raid_id);
				
				$subject = sprintf($this->user->lang['PM_SUBJECT_DECLINE'], $this->user->data['username'], date('d.m.Y', $raid_data['raid_time']));
				$message = sprintf($this->user->lang['PM_MESSAGE_DECLINE'], 
					$this->user->data['username'],
					$this->helper->route('clausi_raidplaner_controller_view', array('raid_id' => $raid_id), true, false, UrlGeneratorInterface::ABSOLUTE_URL),
					$raid_id,
					date('d.m.Y', $raid_data['raid_time']),
					$this->user->lang[$this->status[$status_id]],
					$this->request->variable('comment', '', true)
				);
				
				$this->sendPm($subject, $message, $to);
			}
			
			$sql_mod = array('change_time' => time());
		}
		
		$sql_ary = array(
			'status' => $status_id,
			'role' => $role_id,
		);
		$sql_ary = array_merge($sql_mod, $sql_ary);
		
		$sql = "UPDATE " . $this->attendeeTable . " 
			SET " . $this->db->sql_build_array('UPDATE', $sql_ary) . " 
			WHERE raid_id = " . $raid_id . " AND user_id = '". $user_id ."'";
		$result = $this->db->sql_query($sql);
			
		if($this->db->sql_affectedrows() <= 0)
		{
			$user_profile = $this->getUserProfileFields($user_id);
			if( empty($user_profile['role']) || empty($user_profile['class']))
			{
				$this->template->assign_var('RAIDPLANER_MESSAGE', $this->user->lang['RAIDPLANER_INVALID_USER']);
				return $this->helper->render('raidplaner_error.html', $this->user->lang['RAIDPLANER_PAGE'], 500);
			}

			$sql_ary = array(
				'user_id' => $user_id,
				'raid_id' => $raid_id,
				'role' => $user_profile['role'],
				'class' => $user_profile['class'],
				'status' => $status_id,
				'signup_time' => time(),
			);
			$sql = 'INSERT INTO ' . $this->attendeeTable . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
			$this->db->sql_query($sql);
		}
	}
	
	
	private function getUserProfileFields($user_id)
	{
		$this->cp = $this->container->get('profilefields.manager');
		$user_data = $this->cp->grab_profile_fields_data($user_id);
		
		$user_profile = array(
			'role' => $user_data[$user_id]['raidplaner_role']['value']-1,
			'class' => $user_data[$user_id]['raidplaner_class']['value']-1,
			'charname' => $user_data[$user_id]['raidplaner_charname']['value'],
		);
		
		return $user_profile;
	}
	
	
	private function getRaidmemberCount($raid_id = 0)
	{
		if($raid_id == 0) $where = 'WHERE role > 0 AND class > 0 GROUP BY raid_id';
		else $where = "WHERE raid_id = '".$raid_id."' AND role > 0 AND class > 0";
		
		$sql = "SELECT 
			raid_id,
			SUM(IF(status = 1, 1, 0)) AS attending,
			SUM(IF(status = 2, 1, 0)) AS decline,
			SUM(IF(status = 3, 1, 0)) AS substitute,
			SUM(IF(status = 4, 1, 0)) AS accept
			FROM " . $this->attendeeTable ."
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
			" . $this->raidsTable . " r,
			" . $this->eventsTable . " e, 
			" . $this->scheduleTable . " s 
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
		$sql = 'INSERT INTO ' . $this->raidsTable . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
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
					$sql = "INSERT INTO " . $this->attendeeTable . " 
						" . $this->db->sql_build_array('INSERT_SELECT', array_merge($sql_ary_index, $sql_ary)) . "
						FROM dual
							WHERE NOT EXISTS
							( SELECT *
								FROM " . $this->attendeeTable . "
								WHERE " . $this->db->sql_build_array('SELECT', $sql_ary_index) . " 
							);";
					$this->db->sql_query($sql);
				}
			}
		}
	}
	
	
	private function removeAttendees($raid_id, $attendees = false, $users = false)
	{
		if( ! $attendees)
		{
			$attendees = $this->getAttendees($raid_id);
		}
		if( ! $users)
		{
			$users = $this->auth->acl_get_list(false, 'u_raidplaner', false);
			$users = $users[0]['u_raidplaner'];
		}

		foreach($attendees as $attendee)
		{
			$seen = false;
			foreach($users as $user)
			{
				if($user == $attendee['user_id']) 
				{
					$seen = true;
					break;
				}
			}
			
			if($seen == false)
			{
				$this->removeAttendee($raid_id, $attendee['user_id']);
			}
		}
	}
	
	private function removeAttendee($raid_id, $user_id)
	{
		$sql = "DELETE FROM " . $this->attendeeTable . "
			WHERE raid_id = '". $raid_id ."'
			AND user_id = '". $user_id ."'";
		$this->db->sql_query($sql);
	}
	
	
	private function sendPm($subject, $message, $to) {
		$uid = $bitfield = $options = ''; 
		generate_text_for_storage($subject, $uid, $bitfield, $options, false, false, false);
		generate_text_for_storage($message, $uid, $bitfield, $options, true, true, true);

		$data = array( 
			'address_list'      => $to,
			'from_user_id'      => $this->config['clausi_raidplaner_user'],
			'from_username'     => 'Raidplaner',
			'icon_id'           => 0,
			'from_user_ip'      => $this->user->data['user_ip'],
			 
			'enable_bbcode'     => true,
			'enable_smilies'    => true,
			'enable_urls'       => true,
			'enable_sig'        => false,

			'message'           => $message,
			'bbcode_bitfield'   => $bitfield,
			'bbcode_uid'        => $uid,
		);

		submit_pm('post', $subject, $data, false);
	}
	
	
	private function raidLog($raid_id, $user_id, $newStatus = 0, $newRole = 0, $newComment = '', $changed_user_id)
	{
		if($newRole == NULL) $newRole = 0;
		$sql_ary = array(
			'user_id' => $user_id,
			'raid_id' => $raid_id,
			'new_status' => $newStatus,
			'new_role' => $newRole,
			'new_comment' => $newComment,
			'changed_user_id' => $changed_user_id,
			'log_ip' => $this->user->data['user_ip'],
			'created' => time(),
			'modified' => time(),
		);
		$sql = 'INSERT INTO ' . $this->logsTable . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
		$this->db->sql_query($sql);
	}
	
	
	private function getRaidlog($raid_id)
	{
		$sql = "SELECT * FROM 
			" . $this->logsTable . "
			WHERE 
				raid_id = ". $raid_id ."
				AND deleted = '0' 
			GROUP BY created
			ORDER BY log_id
			";
		$result = $this->db->sql_query($sql);
		
		while($row = $this->db->sql_fetchrow($result))
		{
			$sql = "SELECT * FROM 
				" . $this->logsTable . "
				WHERE 
					created = ". $row['created'] ."
					AND raid_id = ". $raid_id ."
					AND deleted = '0' 
				ORDER BY log_id
				";
			$result2 = $this->db->sql_query($sql);
			$changed_user = '';
			$changed_role = (!empty($this->roles[$row['new_role']])) ? $this->user->lang($this->roles[$row['new_role']]) : 'Unkown';
			$i = 0;
			while($row_changed = $this->db->sql_fetchrow($result2))
			{
				if($i > 0) $changed_role = '';
				$changed_user .= $this->getUsername($row_changed['changed_user_id']) . ', ';
				$i++;
			}
			$this->db->sql_freeresult($result2);
			
			$this->template->assign_block_vars('n_raidlog', array(
				'LOG_ID' => $row['log_id'],
				'USERNAME' => $this->getUsername($row['user_id']),
				'CHANGED_USERNAME' => rtrim($changed_user, ', '),
				'STATUS' => $this->user->lang($this->status[$row['new_status']]),
				'ROLE' => $changed_role,
				'COMMENT' => $row['new_comment'],
				'TIMESTAMP' => $row['created'],
				'TIME' => date('d.m.Y, H:i:s', $row['created']),
			));
		}
		$this->db->sql_freeresult($result);
	}
	
	
	private function getRaidstatistics()
	{
		$sql = "SELECT * FROM 
			" . $this->statisticTable . "
			WHERE
				deleted = 0
			ORDER BY user_id
			";
		$result = $this->db->sql_query($sql);
		
		while($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('n_statistics', array(
				'USERNAME' => $this->getUsername($row['user_id']),
				'RAIDS' => $row['raids'],
				'ACCEPTED' => $row['accepted'],
				'ACCEPTEDPERCENT' => round($row['accepted'] / $row['raids'] *100),
				'ATTENDING' => $row['attending'],
				'ATTENDINGPERCENT' => round($row['attending'] / $row['raids'] *100),
				'SUBSTITUTE' => $row['substitute'],
				'SUBSTITUTEPERCENT' => round($row['substitute'] / $row['raids'] *100),
				'DECLINED' => $row['declined'],
				'DECLINEDPERCENT' => round($row['declined'] / $row['raids'] *100),
			));
		}
	}
	
	
	private function getUsername($user_id)
	{
		$sql_ary = array(
			'user_id' => $user_id,
		);
		$sql = "SELECT username FROM " . $this->userTable . " WHERE " . $this->db->sql_build_array('SELECT', $sql_ary);
		$result = $this->db->sql_query($sql);
		$row_user = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);
		
		return $row_user[0]['username'];
	}
	
	
	private function validComment($comment)
	{
		if(strlen($comment) > 4)
		{
			if( preg_match('/(.*[a-z0-9]){3,}/i', $comment) )
			{
				return true;
			}
		}
		
		return false;
	}
	
	
	private function var_display($var)
	{
		echo "<pre>";
		print_r($var);
		echo "</pre>";
	}
	
}
