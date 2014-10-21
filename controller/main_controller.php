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
	protected $profilefields;
	
	/* @var \phpbb\db\driver\driver_interface */
	protected $db;


	public function __construct(\phpbb\config\config $config, \phpbb\auth\auth $auth, \phpbb\controller\helper $helper, \phpbb\db\driver\driver_interface $db, \phpbb\template\template $template, \phpbb\user $user, \phpbb\profilefields\manager $profilefields, \phpbb\request\request $request, ContainerInterface $container)
	{
		$this->config = $config;
		$this->auth = $auth;
		$this->helper = $helper;
		$this->db = $db;
		$this->template = $template;
		$this->user = $user;
		$this->profilefields = $profilefields;
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
			if($row['raid_time'] >= time() && $firstfuture == 0) $firstfuture = 1;
			$this->template->assign_block_vars('n_raids', array(
				'ID' => $row['id'],
				'DATE' => $this->user->format_date($row['raid_time']),
				'TIMESTAMP' => $row['raid_time'],
				'FLAG' => ($row['raid_time'] < time()) ? 'past' : 'future',
				'FIRSTFUTURE' => $firstfuture,
				'DAY' => date('l', $row['raid_time']),
				'INVITE_TIME' => $row['invite_time'],
				'START_TIME' => $row['start_time'],
				'END_TIME' => $row['end_time'],
				'NOTE' => $row['note'],
				'MEMBERS' => '',
				'U_RAID' => $this->helper->route('clausi_raidplaner_controller_view', array('id' => $row['id'])),
			));
			if($row['raid_time'] >= time()) $firstfuture = 2;
		}
		
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
	
}
