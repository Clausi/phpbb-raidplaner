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
			array('permission.add', array('m_raidplaner', true)),
			array('permission.add', array('u_raidplaner', true)),
			// Set permissions
			array('permission.permission_set', array('ROLE_ADMIN_FULL', 'a_raidplaner')),
			array('permission.permission_set', array('ROLE_ADMIN_STANDARD', 'a_raidplaner')),
			array('permission.permission_set', array('ROLE_ADMIN_FULL', 'm_raidplaner')),
			array('permission.permission_set', array('ROLE_ADMIN_STANDARD', 'm_raidplaner')),
			
			array('custom', array(array($this, 'add_raidplaner_buff_data'))),
			array('custom', array(array($this, 'add_raidplaner_comp_data'))),
			array('custom', array(array($this, 'add_raidplaner_event_data'))),
		);
	}
	
	// Create raidplaner tables
	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'rp_buffs' => array(
					'COLUMNS' => array(
						'buff_id' => array('UINT', NULL, 'auto_increment'),
						'active' => array('TINT:1', 1),
						'name' => array('VCHAR', ''),
						'image' => array('VCHAR', ''),
					),
					'PRIMARY_KEY'	=> 'buff_id',
				),
				
				$this->table_prefix . 'rp_comp' => array(
					'COLUMNS' => array(
						'class' => array('VCHAR', NULL),
						'role' => array('VCHAR', NULL),
						'buff' => array('INT:11', 0),
						'maybe' => array('TINT:1', 0),
					)
				),
				
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
				$this->table_prefix . 'rp_comp',
				$this->table_prefix . 'rp_buffs',
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
	
	public function add_raidplaner_buff_data()
	{
		$sql = "INSERT INTO `". $this->table_prefix . 'rp_buffs' ."` (`buff_id`, `active`, `name`, `image`) VALUES
			(1, 1, 'Bloodlust', 'bloodlust.jpg'),
			(2, 1, 'Attack Power', 'attackpower.jpg'),
			(3, 1, 'Attack Speed', 'attackspeed.jpg'),
			(4, 1, 'Armor reduction', 'armor.jpg'),
			(5, 1, 'Cast speed slow', 'castspeedslow.jpg'),
			(6, 1, 'Critical Strike Chance', 'crit.jpg'),
			(7, 1, 'Mastery', 'mastery.jpg'),
			(8, 1, 'Healing reduce', 'mortalstrike.jpg'),
			(9, 1, 'Physical damage reduction', 'physicaldamagereduction.jpg'),
			(10, 1, 'Physical Vulnerability', 'physicalvulnerability.jpg'),
			(11, 1, 'Spell damage taken', 'spelldamagetaken.jpg'),
			(12, 1, 'Spell Haste', 'spellhaste.jpg'),
			(13, 1, 'Spell Power', 'spellpower.jpg'),
			(14, 1, 'Stats', 'stats.jpg'),
			(15, 1, 'Stamina', 'fortitude.jpg');";
		$result = $this->db->sql_query($sql);
	}
	
	public function add_raidplaner_comp_data()
	{
		$sql = "INSERT INTO `". $this->table_prefix . 'rp_comp' ."` (`class`, `role`, `buff`, `maybe`) VALUES
			('warrior', 'tank', 2, 1),
			('warrior', 'tank', 15, 1),
			('warrior', 'tank', 4, 0),
			('warrior', 'tank', 9, 0),
			('warrior', 'melee', 2, 1),
			('warrior', 'melee', 15, 1),
			('warrior', 'melee', 4, 0),
			('warrior', 'melee', 8, 0),
			('warrior', 'melee', 9, 0),
			('warrior', 'melee', 10, 0),
			('paladin', 'tank', 7, 1),
			('paladin', 'tank', 14, 1),
			('paladin', 'tank', 9, 0),
			('paladin', 'heal', 7, 1),
			('paladin', 'heal', 14, 1),
			('paladin', 'melee', 7, 1),
			('paladin', 'melee', 14, 1),
			('paladin', 'melee', 9, 0),
			('paladin', 'melee', 10, 0),
			('hunter', 'range', 2, 0),
			('hunter', 'range', 8, 0),
			('hunter', 'range', 1, 1),
			('hunter', 'range', 3, 1),
			('hunter', 'range', 4, 1),
			('hunter', 'range', 5, 1),
			('hunter', 'range', 6, 1),
			('hunter', 'range', 7, 1),
			('hunter', 'range', 9, 1),
			('hunter', 'range', 10, 1),
			('hunter', 'range', 11, 1),
			('hunter', 'range', 12, 1),
			('hunter', 'range', 13, 1),
			('hunter', 'range', 14, 1),
			('hunter', 'range', 15, 1),
			('rogue', 'melee', 3, 0),
			('rogue', 'melee', 4, 0),
			('rogue', 'melee', 5, 0),
			('rogue', 'melee', 8, 1),
			('rogue', 'melee', 11, 0),
			('priest', 'heal', 15, 0),
			('priest', 'range', 15, 0),
			('priest', 'range', 12, 0),
			('deathknight', 'melee', 2, 0),
			('deathknight', 'melee', 3, 0),
			('deathknight', 'melee', 5, 0),
			('deathknight', 'melee', 10, 0),
			('deathknight', 'tank', 2, 0),
			('deathknight', 'tank', 5, 0),
			('deathknight', 'tank', 9, 0),
			('shaman', 'range', 1, 0),
			('shaman', 'range', 7, 0),
			('shaman', 'range', 12, 0),
			('shaman', 'range', 13, 0),
			('shaman', 'range', 9, 0),
			('shaman', 'melee', 1, 0),
			('shaman', 'melee', 3, 0),
			('shaman', 'melee', 7, 0),
			('shaman', 'melee', 13, 0),
			('shaman', 'melee', 9, 0),
			('shaman', 'heal', 1, 0),
			('shaman', 'heal', 7, 0),
			('shaman', 'heal', 13, 0),
			('mage', 'range', 1, 0),
			('mage', 'range', 6, 0),
			('mage', 'range', 13, 0),
			('mage', 'range', 5, 0),
			('warlock', 'range', 13, 0),
			('warlock', 'range', 15, 0),
			('warlock', 'range', 5, 0),
			('warlock', 'range', 8, 0),
			('warlock', 'range', 9, 0),
			('warlock', 'range', 11, 0),
			('monk', 'tank', 14, 0),
			('monk', 'tank', 9, 0),
			('monk', 'heal', 14, 0),
			('monk', 'melee', 14, 0),
			('monk', 'melee', 6, 0),
			('monk', 'melee', 8, 0),
			('druid', 'range', 12, 0),
			('druid', 'range', 14, 0),
			('druid', 'range', 4, 0),
			('druid', 'melee', 6, 0),
			('druid', 'melee', 14, 0),
			('druid', 'melee', 4, 0),
			('druide', 'melee', 9, 0),
			('druid', 'tank', 6, 0),
			('druid', 'tank', 14, 0),
			('druid', 'tank', 4, 0),
			('druid', 'tank', 9, 0),
			('druid', 'heal', 14, 0),
			('druid', 'heal', 4, 0);";
		$result = $this->db->sql_query($sql);
	}
	
}
