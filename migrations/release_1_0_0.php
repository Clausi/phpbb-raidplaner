<?php

namespace clausi\raidplaner\migrations;

class release_1_0_0 extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v310\rc5');
	}

	public function update_data()
	{
		return array(
			array('config.add', array('clausi_raidplaner_active', 0)),
			array('config.add', array('clausi_raidplaner_cron_lastrun', 0)),
			array('config.add', array('clausi_raidplaner_cron_interval', 60)),

			array('module.add', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_RAIDPLANER_TITLE'
			)),
			array('module.add', array(
				'acp',
				'ACP_RAIDPLANER_TITLE',
				array(
					'module_basename' => '\clausi\raidplaner\acp\main_module',
					'modes' => array('settings', 'schedule', 'events', 'usermanage'),
				),
			)),
			
			// Add permission
			array('permission.add', array('a_raidplaner', true)),
			array('permission.add', array('a_raidplaner_usermanage', true)),
			array('permission.add', array('m_raidplaner', true)),
			array('permission.add', array('u_raidplaner', true)),
			// Set permissions
			array('permission.permission_set', array('ROLE_ADMIN_FULL', 'a_raidplaner')),
			array('permission.permission_set', array('ROLE_ADMIN_STANDARD', 'a_raidplaner')),
			array('permission.permission_set', array('ROLE_ADMIN_FULL', 'a_raidplaner_usermanage')),
			array('permission.permission_set', array('ROLE_ADMIN_STANDARD', 'a_raidplaner_usermanage')),
			array('permission.permission_set', array('ROLE_ADMIN_FULL', 'm_raidplaner')),
			array('permission.permission_set', array('ROLE_ADMIN_STANDARD', 'm_raidplaner')),
			
			array('custom', array(array($this, 'add_raidplaner_event_data'))),
		);
	}
	
	// Create raidplaner tables
	public function update_schema()
	{
		return array(
			'add_tables' => array(
				
				$this->table_prefix . 'rp_events' => array(
					'COLUMNS' => array(
						'event_id' => array('UINT', NULL, 'auto_increment'),
						'name' => array('VCHAR', ''),
						'raidsize' => array('USINT', 20),
						'precreate' => array('TINT:4', 4),
						'deleted' => array('TIMESTAMP', 0),
					),
					'PRIMARY_KEY'	=> 'event_id',
				),
				
				$this->table_prefix . 'rp_logs' => array(
					'COLUMNS' => array(
						'log_id' => array('UINT', NULL, 'auto_increment'),
						'user_id' => array('UINT', 0),
						'raid_id' => array('UINT', 0),
						'changed_user' => array('STEXT', ''),
						'new_status' => array('TINT:1', 0),
						'new_comment' => array('TEXT', ''),
						'log_ip' => array('VCHAR:40', '0'),
						'log_time' => array('TIMESTAMP', 0),
					),
					'PRIMARY_KEY'	=> 'log_id',
				),
				
				$this->table_prefix . 'rp_attendees' => array(
					'COLUMNS' => array(
						'attendee_id' => array('UINT', NULL, 'auto_increment'),
						'user_id' => array('UINT', 0),
						'raid_id' => array('UINT', 0),
						'role' => array('TINT:4', '0'),
						'class' => array('TINT:4', '0'),
						'status' => array('TINT:1', 1),
						'comment' => array('TEXT', NULL),
						'signup_time' => array('TIMESTAMP', '0'),
						'change_time' => array('TIMESTAMP', '0'),
						'adminchange_time' => array('TIMESTAMP', 0),
					),
					'PRIMARY_KEY'	=> 'attendee_id',
					'KEYS'		=> array(
						'u_index' => array('UNIQUE', array('user_id', 'raid_id'))
					)
				),
				
				$this->table_prefix . 'rp_raids' => array(
					'COLUMNS' => array(
						'raid_id' => array('UINT', NULL, 'auto_increment'),
						'schedule_id' =>array('UINT', NULL),
						'raid_time' => array('TIMESTAMP', 0),
						'invite_time' => array('VCHAR:20', NULL),
						'start_time' => array('VCHAR:20', NULL),
						'end_time' => array('VCHAR:20', NULL),
						'autoaccept' => array('TINT:1', 0),
						'cancel' => array('TINT:1', 0),
						'active' => array('TINT:1', 1),
						'deleted' => array('TIMESTAMP', 0),
						'note' => array('TEXT', NULL),
						'bbcode_bitfield' => array('VCHAR:255', ''),
						'bbcode_uid' => array('VCHAR:8', ''),
					),
					'PRIMARY_KEY'	=> 'raid_id',
				),
				
				$this->table_prefix . 'rp_schedule' => array(
					'COLUMNS' => array(
						'schedule_id' => array('UINT', NULL, 'auto_increment'),
						'event_id' => array('UINT', 0),
						'invite_time' => array('VCHAR:20', NULL),
						'start_time' => array('VCHAR:20', NULL),
						'end_time' => array('VCHAR:20', NULL),
						'repeatable' => array('VCHAR:20', 'NULL'),
						'repeat_start' => array('TIMESTAMP', NULL),
						'repeat_end' => array('TIMESTAMP', NULL),
						'autoaccept' => array('TINT:1', 1),
						'deleted' => array('TIMESTAMP', 0),
						'note' => array('TEXT', NULL),
					),
					'PRIMARY_KEY'	=> 'schedule_id',
				),
			),

		);
	}
	
	// Remove raidplaner tables
	public function revert_schema()
	{
		return array(
			'drop_tables'    => array(
				$this->table_prefix . 'rp_raids',
				$this->table_prefix . 'rp_schedule',
				$this->table_prefix . 'rp_attendees',
				$this->table_prefix . 'rp_logs',
				$this->table_prefix . 'rp_events',
			),
		);
	}
	
	public function add_raidplaner_event_data()
	{
		$sql = "INSERT INTO `". $this->table_prefix . 'rp_events' ."` (`event_id`, `name`, `raidsize`) VALUES
			(1, 'Mythic', 20),
			(2, 'Heroic', 30),
			(3, 'Normal', 30),
			(4, 'WoD', 20);";
		$result = $this->db->sql_query($sql);
	}

}
