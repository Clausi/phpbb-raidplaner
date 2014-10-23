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
		
		
		$sql = "SELECT * FROM " . $this->container->getParameter('tables.clausi.raidplaner_raids') . " WHERE deleted = '0' ORDER BY raid_time";
		$result = $this->db->sql_query($sql);
		$firstfuture = 0;
		while($row = $this->db->sql_fetchrow($result))
		{
			$raid_end = explode(':', $row['end_time']);
			$raid_endtime = mktime($raid_end[0], $raid_end[1], 0, date("n", $row['raid_time']), date("j", $row['raid_time']), date("Y", $row['raid_time']));
			if($raid_endtime >= time() && $firstfuture == 0) $firstfuture = 1;
			
			$sql = "SELECT e.name FROM 
				" . $this->container->getParameter('tables.clausi.raidplaner_events') . " e, 
				" . $this->container->getParameter('tables.clausi.raidplaner_schedule') . " s 
				WHERE s.id = '".$row['schedule_id']."' 
					AND e.id = s.event_id
				";
			$result_event = $this->db->sql_query($sql);
			$row_event = $this->db->sql_fetchrow($result_event);
			$this->db->sql_freeresult($result_event);
			
			$this->template->assign_block_vars('n_raids', array(
				'EVENTNAME' => $row_event['name'],
				'ID' => $row['id'],
				'DATE' => $this->user->format_date($row['raid_time']),
				'TIMESTAMP' => $row['raid_time'],
				'FLAG' => ($raid_endtime < time()) ? 'past' : 'future',
				'FIRSTFUTURE' => $firstfuture,
				'DAY' => date('l', $row['raid_time']),
				'INVITE_TIME' => $row['invite_time'],
				'START_TIME' => $row['start_time'],
				'END_TIME' => $row['end_time'],
				'NOTE' => $row['note'],
				'MEMBERS' => '',
				'U_RAID' => $this->helper->route('clausi_raidplaner_controller_view', array('id' => $row['id'])),
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

	
	public function view($id)
	{
		if($this->config['clausi_raidplaner_active'] == 0) 
		{
			$this->template->assign_var('RAIDPLANER_MESSAGE', $this->user->lang['RAIDPLANER_INACTIVE']);
			return $this->helper->render('raidplaner_error.html', $this->user->lang['RAIDPLANER_PAGE'], 404);
		}
		if(!is_numeric($id))
		{
			$this->template->assign_var('RAIDPLANER_MESSAGE', $this->user->lang['RAIDPLANER_INVALID_ID']);
			return $this->helper->render('raidplaner_error.html', $this->user->lang['RAIDPLANER_PAGE'], 404);
		}
		$message = '';
		
		$this->template->assign_vars(array(
			'RAIDPLANER_MESSAGE' => $message,
			'S_RAIDPLANER_PAGE' => 'index',
		));
		return $this->helper->render('raidplaner_view.html', $this->user->lang['RAIDPLANER_RAID'] . ': ' . $id);
	}
	
	
	public function addAttendees($raid_id)
	{
		$user_ary = $this->auth->acl_get_list(false, 'u_raidplaner', false);
		$this->cp = $this->container->get('profilefields.manager');
		echo "add";
		foreach($user_ary as $permission)
		{
			$user_data = $this->cp->grab_profile_fields_data($permission['u_raidplaner']);
			foreach($permission['u_raidplaner'] as $user_id)
			{
				if($user_data[$user_id]['raidplaner_role']['value']-1 > 0 && $user_data[$user_id]['raidplaner_class']['value']-1 > 0)
				{
					$sql_ary = array(
						'user_id' => $user_id,
						'raid_id' => $raid_id,
						'role' => $user_data[$user_id]['raidplaner_role']['value']-1,
						'class' => $user_data[$user_id]['raidplaner_class']['value']-1,
						'status' => 1,
						'signup_time' => time(),
					);
					
					$sql = 'INSERT INTO ' . $this->container->getParameter('tables.clausi.raidplaner_attendees') . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
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
