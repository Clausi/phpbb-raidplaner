<?php

namespace clausi\raidplaner\cron\task\core;

use Symfony\Component\DependencyInjection\ContainerInterface;

class raidplaner_cron_attendee extends \phpbb\cron\task\base
{
	protected $config;
	protected $db;
	protected $container;
	

	public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, ContainerInterface $container)
	{
		$this->config = $config;
		$this->db = $db;
		$this->container = $container;
	}
	
	/**
	* Runs this cron task.
	*
	* @return null
	*/
	public function run()
	{
		// echo "run".'<br />';
		
		
		$this->config->set('clausi_raidplaner_cron_attendee_lastrun', time());
	}
	
	
	/**
	* Returns whether this cron task can run, given current board configuration.
	*
	* @return bool
	*/
	public function is_runnable()
	{
		if($this->config['clausi_raidplaner_active']) return true;
		
		return false;
	}
	
	/**
	* Returns whether this cron task should run now, because enough time
	* has passed since it was last run.
	*
	* @return bool
	*/
	public function should_run()
	{
		return $this->config['clausi_raidplaner_cron_attendee_lastrun'] < time() - $this->config['clausi_raidplaner_cron_interval'];
	}
	
}
