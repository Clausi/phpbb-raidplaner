<?php

namespace clausi\raidplaner\controller;

use Symfony\Component\DependencyInjection\ContainerInterface;

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
	}


	public function handle()
	{
		if($this->config['clausi_raidplaner_active'] == 0) 
		{
			$this->template->assign_var('RAIDPLANER_MESSAGE', $this->user->lang['RAIDPLANER_INACTIVE']);
			return $this->helper->render('raidplaner_error.html', $this->user->lang['RAIDPLANER_PAGE'], 404);
		}
		$message = '';
		
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
		
		$sql = "SELECT 
			raid_id,
			SUM(IF(status = 1, 1, 0)) AS attending,
			SUM(IF(status = 2, 1, 0)) AS decline,
			SUM(IF(status = 3, 1, 0)) AS substitute,
			SUM(IF(status = 4, 1, 0)) AS accept
			FROM " . $this->container->getParameter('tables.clausi.raidplaner_attendees') ."
			GROUP BY raid_id
			";
		$result_count = $this->db->sql_query($sql);
		$row_count = $this->db->sql_fetchrowset($result_count);
		
		$firstfuture = 0;
		$memberCount = ['attending' => 0, 'decline' => 0, 'substitute' => 0, 'accept' => 0];
		while($row = $this->db->sql_fetchrow($result))
		{
			// $this->var_display($row);
			foreach($row_count as $key => $raid_count)
			{
				if($raid_count['raid_id'] == $row['raid_id']) $memberCount = $row_count[$key];
			}
			
			$raid_end = explode(':', $row['end_time']);
			$raid_endtime = mktime($raid_end[0], $raid_end[1], 0, date("n", $row['raid_time']), date("j", $row['raid_time']), date("Y", $row['raid_time']));
			if($raid_endtime >= time() && $firstfuture == 0) $firstfuture = 1;
			
			// $sql = "SELECT e.name, e.raidsize FROM 
				// " . $this->container->getParameter('tables.clausi.raidplaner_events') . " e, 
				// " . $this->container->getParameter('tables.clausi.raidplaner_schedule') . " s 
				// WHERE s.id = '".$row['schedule_id']."' 
					// AND e.id = s.event_id
				// ";
			// $result_event = $this->db->sql_query($sql);
			// $row_event = $this->db->sql_fetchrow($result_event);
			// $this->db->sql_freeresult($result_event);
			
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
				'U_RAID' => $this->helper->route('clausi_raidplaner_controller_view', array('raid_id' => $row['raid_id'])),
			));
			if($raid_endtime >= time()) $firstfuture = 2;
		}
		$this->db->sql_freeresult($result);
		
		$this->template->assign_vars(array(
			'RAIDPLANER_MESSAGE' => $message,
			'S_RAIDPLANER_PAGE' => 'index',
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
		$message = '';
		
		$this->template->assign_vars(array(
			'RAIDPLANER_MESSAGE' => $message,
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
	
	
	private function var_display($var)
	{
		echo "<pre>";
		print_r($var);
		echo "</pre>";
	}
	
}
