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
				'M_NOTE_ACTION' => $this->helper->route('clausi_raidplaner_controller_note', array('raid_id' => $raid_id)),
				'RAID_NOTE' => $row_raid['note']['text'],
			));
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
		
		$sql = "SELECT user_id, username FROM " . $this->container->getParameter('tables.users') . "";
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
		$currentAttendee = $this->getAttendee($raid_id, $user_id);
		$this->template->assign_vars(array(
			'COMMENT' => $currentAttendee['comment'],
		));
		
		// TODO: Check back if last value of confirm_box() can be done better
		if($currentAttendee['status'] == 4 && confirm_box(false, 'ACCEPTED_TITLE', '', 'raidplaner_confirm.html', ltrim($this->u_action, '/')))
		{
			
		}
		else
		{
			if (confirm_box(true))
			{
				$comment = $this->request->variable('comment', '', true);
				if(($status_id == 2 || $status_id == 3) && strlen($comment) < 5)
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
					'OLD_STATUSNAME' => strtolower($this->status[$currentAttendee['status']]),
					'OLD_ROLENAME' => strtolower($this->roles[$currentAttendee['role']]),
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
				$this->template->assign_var('U_ACTION', $this->u_action);
				confirm_box(false, 'STATUSCHANGE_TITLE', '', 'raidplaner_confirm.html', ltrim($this->u_action, '/'));
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

		// TODO: Check back if last value of confirm_box() can be done better
		if(confirm_box(true))
		{
			$comment = $this->request->variable('comment', '', true);
			$this->setComment($raid_id, $user_id, $comment);
			
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
				'COMMENT' => $this->getComment($raid_id, $user_id),
				'U_ACTION' => $this->u_action,
			));
			
			confirm_box(false, 'COMMENT_TITLE', '', 'raidplaner_confirm.html', ltrim($this->u_action, '/'));
		}
	}
	
	
	public function setModRaidnote($raid_id)
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
		
		add_form_key('clausi/raidplaner');
		if (!check_form_key('clausi/raidplaner'))
		{
			trigger_error('FORM_INVALID');
		}
		
		$note = $this->request->variable('note', '');
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
		}
		else
		{
			$this->setRaidnote($raid_id, $note, $uid, $bitfield);
			
			$raid_data = $this->getRaidData($raid_id);
			
			$response = array(
				'MESSAGE_TITLE' => $this->user->lang['NOTE_CHANGE_TITLE'],
				'MESSAGE_TEXT' => sprintf($this->user->lang['NOTE_CHANGE_TEXT'], $raid_id, $this->user->format_date($raid_data['raid_time'])),
			);
			if ($this->request->is_ajax())
			{
				$this->json_response->send($response);
			}
		}
		
		$this->template->assign_vars($response);
		return $this->helper->render('raidplaner_status.html', $this->user->lang['RAIDPLANER_PAGE']);
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
		
		$this->setStatus($raid_id, $user_id, $status_id, 0, $role_id, true);
		
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
		
		foreach($this->status as $status_id => $statusName)
		{
			foreach($this->roles as $role_id => $roleName)
			{
				$current = $this->request->variable(strtolower($statusName) . '_' . strtolower($roleName), '');
				$current = explode('&amp;', $current);
				foreach($current as $user_id)
				{
					$user_id = str_replace('user[]=', '', $user_id);
					if($user_id && $user_id != 0)
					{
						$this->setStatus($raid_id, $user_id, $status_id, 0, $role_id, true);
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
		$sql = "UPDATE " . $this->container->getParameter('tables.clausi.raidplaner_raids') . " 
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
		if( ! is_numeric($status_id) || $status_id < 0 || $status_id > 3)
		{
			$this->template->assign_var('RAIDPLANER_MESSAGE', $this->user->lang['RAIDPLANER_INVALID_STATUS']);
			return $this->helper->render('raidplaner_error.html', $this->user->lang['RAIDPLANER_PAGE'], 500);
		}
		
		$comment = $this->request->variable('comment', '', true);
		if(($status_id == 2 || $status_id == 3) && strlen($comment) < 5)
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
				$currentStatus = $this->getStatus($raid_id, $user_id);
				
				$this->setStatus($raid_id, $user_id, $status_id, $currentStatus);

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
				'comment' => $comment,
				'signup_time' => time(),
			);
			$sql = 'INSERT INTO ' . $this->container->getParameter('tables.clausi.raidplaner_attendees') . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
			$this->db->sql_query($sql);
		}
	}
	
	
	private function getComment($raid_id, $user_id)
	{
		$sql_ary = array(
			'raid_id' => $raid_id,
			'user_id' => $user_id,
		);
		$sql = "SELECT comment FROM " .  $this->container->getParameter('tables.clausi.raidplaner_attendees') . " WHERE "  . $this->db->sql_build_array('SELECT', $sql_ary);
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
		$sql = "SELECT role FROM " .  $this->container->getParameter('tables.clausi.raidplaner_attendees') . " WHERE "  . $this->db->sql_build_array('SELECT', $sql_ary);
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
		$sql = "SELECT class FROM " .  $this->container->getParameter('tables.clausi.raidplaner_attendees') . " WHERE "  . $this->db->sql_build_array('SELECT', $sql_ary);
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		
		return $row['class'];
	}
	
	
	// Get all attendees of raid
	private function getAttendees($raid_id)
	{
		$sql = "SELECT * FROM " . $this->container->getParameter('tables.clausi.raidplaner_attendees') . " WHERE raid_id = '".$raid_id."' ORDER BY class, user_id";
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
		$sql = "SELECT * FROM " .  $this->container->getParameter('tables.clausi.raidplaner_attendees') . " WHERE "  . $this->db->sql_build_array('SELECT', $sql_ary);
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
		$sql = "SELECT status FROM " .  $this->container->getParameter('tables.clausi.raidplaner_attendees') . " WHERE "  . $this->db->sql_build_array('SELECT', $sql_ary);
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
					$user_to[$mod] = 'to';
				}
				$to = array('u' => $user_to);
				
				$raid_data = $this->getRaidData($raid_id);
				
				$subject = sprintf($this->user->lang['PM_SUBJECT_DECLINE'], $user_id, date('d.m.Y', $raid_data['raid_time']));
				$message = sprintf($this->user->lang['PM_SUBJECT_DECLINE'], 
					$user_id,
					$this->helper->route('clausi_raidplaner_controller_view', array('raid_id' => $raid_id)),
					$raid_id,
					date('d.m.Y', $raid_data['raid_time'])
				);

				
				//$this->sendPm($subject, $message, $to);
			}
			
			$sql_mod = array('change_time' => time());
		}
		
		$sql_ary = array(
			'status' => $status_id,
			'role' => $role_id,
		);
		$sql_ary = array_merge($sql_mod, $sql_ary);
		
		$sql = "UPDATE " . $this->container->getParameter('tables.clausi.raidplaner_attendees') . " 
			SET " . $this->db->sql_build_array('UPDATE', $sql_ary) . " 
			WHERE raid_id = " . $raid_id . " AND user_id = '". $user_id ."'";
		$this->db->sql_query($sql);
		
		if($this->db->sql_affectedrows() == 0)
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
			$sql = 'INSERT INTO ' . $this->container->getParameter('tables.clausi.raidplaner_attendees') . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
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
		$sql = "DELETE FROM " . $this->container->getParameter('tables.clausi.raidplaner_attendees') . "
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
			'from_user_id'      => 0,
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
	
	
	private function var_display($var)
	{
		echo "<pre>";
		print_r($var);
		echo "</pre>";
	}
	
}
