<?php

namespace clausi\raidplaner\cron\task\core;

use Symfony\Component\DependencyInjection\ContainerInterface;

class raidplaner_cron extends \phpbb\cron\task\base
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
	{	echo "ja";
		$sql = "SELECT * FROM " . $this->container->getParameter('tables.clausi.raidplaner_schedule') . " 
			WHERE 
				deleted = '0' AND repeatable != 'no_repeat' AND 
				(
					repeat_end = '0' OR
					repeat_end > '".time()."'
				) AND 
				repeat_start < '".time()."' 
			ORDER BY id";
		$result = $this->db->sql_query($sql);
		while($row = $this->db->sql_fetchrow($result))
		{
			$sql = "SELECT precreate FROM " . $this->container->getParameter('tables.clausi.raidplaner_events') . " WHERE id = '". $row['event_id'] ."' LIMIT 1";
			$result_event = $this->db->sql_query($sql);
			$row_event = $this->db->sql_fetchrow($result_event);
			$this->db->sql_freeresult($result_event);
			
			$weekday = date('N', $row['repeat_start']);
			
			$raid_start = explode(':', $row['start_time']);
			
			for($week = 0; $week <= $row_event['precreate']; $week++)
			{
				for($day = 0; $day <= 6; $day++)
				{
					$time = time()+($week*86400*7)+($day*86400);
					$raid_time = mktime ($raid_start[0], $raid_start[1], 0, date("n", $time), date("j", $time), date("Y", $time));
					echo $raid_time;
				}
			}
			
			$sql = "SELECT * FROM " . $this->container->getParameter('tables.clausi.raidplaner_raids') . " WHERE deleted = '0' AND schedule_id = '". $row['id'] ."' ORDER BY id";
			$result_raids = $this->db->sql_query($sql);
			
			
			
			$this->db->sql_freeresult($result_raids);
		}
		$this->db->sql_freeresult($result);
		
		$this->config->set('clausi_raidplaner_cron_lastrun', time());
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
		return $this->config['clausi_raidplaner_cron_lastrun'] < time() - $this->config['clausi_raidplaner_cron_interval'];
	}
}
