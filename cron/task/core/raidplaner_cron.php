<?php

namespace clausi\raidplaner\cron\task\core;

use Symfony\Component\DependencyInjection\ContainerInterface;

class raidplaner_cron extends \phpbb\cron\task\base
{
	protected $config;
	protected $db;
	protected $container;
	protected $raidplaner;
	
	protected $schedule_id;
	protected $raid_time;
	protected $invite_time;
	protected $start_time;
	protected $end_time;
	protected $autoaccept;

	
	public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, ContainerInterface $container, \clausi\raidplaner\controller\main_controller $raidplaner)
	{
		$this->config = $config;
		$this->db = $db;
		$this->container = $container;
		$this->raidplaner = $raidplaner;
	}
	
	/**
	* Runs this cron task.
	*
	* @return null
	*/
	public function run()
	{
		//echo "run";
		$this->createScheduled();
		$this->processStatistics();
	}
	
	
	private function processStatistics()
	{
		// Get unprocessed past raids
		$sql = "SELECT * FROM " . $this->container->getParameter('tables.clausi.raidplaner_raids') . " 
			WHERE 
				deleted = '0' 
				AND raid_time < '". time() . "'
				AND processed = 0
			";
		$result_raids = $this->db->sql_query($sql);
		
		while($row_raids = $this->db->sql_fetchrow($result_raids))
		{
			// Get attendees of raid
			$sql = "SELECT * FROM " . $this->container->getParameter('tables.clausi.raidplaner_attendees') . " 
				WHERE 
					raid_id = '". $row_raids['raid_id'] . "'
				";
			$result_attendees = $this->db->sql_query($sql);
			
			while($row_attendees = $this->db->sql_fetchrow($result_attendees))
			{
				$sql = "INSERT INTO
						" . $this->container->getParameter('tables.clausi.raidplaner_statistics') . "
					SET
						user_id = '". $row_attendees['user_id'] ."',
						raids = 1,
						accepted = CASE WHEN ". $row_attendees['status'] ." = 4 THEN 1 ELSE 0 END,
						attending = CASE WHEN ". $row_attendees['status'] ." = 1 THEN 1 ELSE 0 END,
						substitute = CASE WHEN ". $row_attendees['status'] ." = 3 THEN 1 ELSE 0 END,
						declined = CASE WHEN ". $row_attendees['status'] ." = 2 THEN 1 ELSE 0 END,
						created = UNIX_TIMESTAMP(),
						modified = UNIX_TIMESTAMP()
					ON DUPLICATE KEY UPDATE
						raids = raids + 1,
						accepted = CASE WHEN ". $row_attendees['status'] ." = 4 THEN accepted + 1 ELSE accepted END,
						attending = CASE WHEN ". $row_attendees['status'] ." = 1 THEN attending + 1 ELSE attending END,
						substitute = CASE WHEN ". $row_attendees['status'] ." = 3 THEN substitute + 1 ELSE substitute END,
						declined = CASE WHEN ". $row_attendees['status'] ." = 2 THEN declined + 1 ELSE declined END,
						modified = UNIX_TIMESTAMP()
					";
				$result_stat = $this->db->sql_query($sql);
			}
			$this->db->sql_freeresult($result_attendees);
			
			$sql = "UPDATE " . $this->container->getParameter('tables.clausi.raidplaner_raids') . " 
				SET
					processed = UNIX_TIMESTAMP()
				WHERE 
					raid_id = '". $row_raids['raid_id'] . "'
				";
			$result_updateraid = $this->db->sql_query($sql);
		}
		$this->db->sql_freeresult($result_raids);
		
		// Get next raid to softdelete removed attendees
		$sql = "SELECT * FROM " . $this->container->getParameter('tables.clausi.raidplaner_raids') . " 
			WHERE 
				deleted = '0' 
				AND raid_time >= '". time() . "'
			ORDER BY raid_time ASC
			LIMIT 1
			";
		$result_next = $this->db->sql_query($sql);
		$row_next = $this->db->sql_fetchrow($result_next);

		$sql = "UPDATE " . $this->container->getParameter('tables.clausi.raidplaner_statistics') . " 
			SET
				deleted = UNIX_TIMESTAMP()
			WHERE 
				user_id not in (SELECT user_id FROM " . $this->container->getParameter('tables.clausi.raidplaner_attendees') . " WHERE raid_id = " . $row_next['raid_id'] . " )
			";
		$this->db->sql_query($sql);
	}
	
	
	private function createScheduled()
	{
		$sql = "SELECT * FROM " . $this->container->getParameter('tables.clausi.raidplaner_schedule') . " 
			WHERE 
				deleted = '0' AND repeatable != 'no_repeat'
			ORDER BY schedule_id";
		$result = $this->db->sql_query($sql);
		while($row = $this->db->sql_fetchrow($result))
		{
			$sql = "SELECT precreate FROM " . $this->container->getParameter('tables.clausi.raidplaner_events') . " WHERE event_id = '". $row['event_id'] ."' LIMIT 1";
			$result_event = $this->db->sql_query($sql);
			$row_event = $this->db->sql_fetchrow($result_event);
			$this->db->sql_freeresult($result_event);
			
			$raid_day = date('N', $row['repeat_start']);
			
			$raid_start = explode(':', $row['start_time']);
			
			$this->schedule_id = $row['schedule_id'];
			$this->invite_time = $row['invite_time'];
			$this->start_time = $row['start_time'];
			$this->end_time = $row['end_time'];
			$this->autoaccept = $row['autoaccept'];
			
			$repeat_start = mktime ($raid_start[0], $raid_start[1], 0, date("n", $row['repeat_start']), date("j", $row['repeat_start']), date("Y", $row['repeat_start']));

			for($time = $repeat_start; $time <= time()+(86400*7*$row_event['precreate']); $time += 86400)
			{
				$this->raid_time = mktime ($raid_start[0], $raid_start[1], 0, date("n", $time), date("j", $time), date("Y", $time));
				if($row['repeat_end'] != 0) $repeat_end = mktime ($raid_start[0], $raid_start[1], 0, date("n", $row['repeat_end']), date("j", $row['repeat_end']), date("Y", $row['repeat_end']));
				else $repeat_end = 0;
				
				if($repeat_start <= $this->raid_time && ($repeat_end == 0 || $repeat_end >= $this->raid_time))
				{
					$current_day = date('N', $this->raid_time);

					switch($row['repeatable'])
					{
						case 'daily':
							if( ! $this->getRaids() )
							{
								$this->createRaid();
							}
						break;
						case 'weekly':
							if( $current_day == $raid_day && ! $this->getRaids() )
							{
								$this->createRaid();
							}
						break;
						case 'twoweekly':
							if( $current_day == $raid_day && ! $this->getRaids() )
							{
								$offsetTwoWeek = mktime ($raid_start[0], $raid_start[1], 0, date("n", $time), date("j", $time)-14, date("Y", $time)) - $this->raid_time;
								if( $this->getRaids($offsetTwoWeek) || $this->raid_time == $repeat_start )
								{
									$this->createRaid();
								}
							}
						break;
					}
				}
			}
			
		}
		$this->db->sql_freeresult($result);
		
		$this->config->set('clausi_raidplaner_cron_lastrun', time());
	}
	
	private function getRaids($offset = 0)
	{
		$sql = "SELECT COUNT(raid_id) as count_id FROM " . $this->container->getParameter('tables.clausi.raidplaner_raids') . " WHERE deleted = '0' AND raid_time = '".($this->raid_time+$offset)."' LIMIT 1";
		$result = $this->db->sql_query($sql);
		if($this->db->sql_fetchfield('count_id') > 0) return true;
		
		return false;
	}
	
	private function createRaid()
	{
		$this->raidplaner->createRaid($this->schedule_id, $this->raid_time, $this->invite_time, $this->start_time, $this->end_time, $this->autoaccept);
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
