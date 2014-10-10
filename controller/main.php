<?php

namespace clausi\raidplaner\controller;

class main
{
	/* @var \phpbb\config\config */
	protected $config;

	/* @var \phpbb\controller\helper */
	protected $helper;

	/* @var \phpbb\template\template */
	protected $template;

	/* @var \phpbb\user */
	protected $user;

	/**
	* Constructor
	*
	* @param \phpbb\config\config		$config
	* @param \phpbb\controller\helper	$helper
	* @param \phpbb\template\template	$template
	* @param \phpbb\user				$user
	*/
	public function __construct(\phpbb\config\config $config, \phpbb\controller\helper $helper, \phpbb\template\template $template, \phpbb\user $user)
	{
		$this->config = $config;
		$this->helper = $helper;
		$this->template = $template;
		$this->user = $user;
	}


	public function handle()
	{
		if($this->config['clausi_raidplaner_active'] == 0) 
		{
			$this->template->assign_var('RAIDPLANER_MESSAGE', $this->user->lang['RAIDPLANER_INACTIVE']);
			return $this->helper->render('raidplaner_error.html', $this->user->lang['RAIDPLANER_PAGE'], 404);
		}
		
		$this->template->assign_var('RAIDPLANER_MESSAGE', 'index');
		return $this->helper->render('raidplaner_body.html', $this->user->lang['RAIDPLANER_PAGE']);
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
		
		$this->template->assign_var('RAIDPLANER_MESSAGE', $id);
		return $this->helper->render('raidplaner_body.html', $this->user->lang['RAIDPLANER_RAID'] . ': ' . $id);
	}
	
}
