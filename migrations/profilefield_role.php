<?php

namespace clausi\raidplaner\migrations;

class profilefield_role extends \phpbb\db\migration\profilefield_base_migration
{
	static public function depends_on()
	{
		return array(
			'\phpbb\db\migration\data\v310\profilefield_show_novalue',
			'\phpbb\db\migration\data\v310\profilefield_types',
			'\clausi\recruitment\migrations\release_1_0_0',
		);
	}

	public function update_data()
	{
		return array(
			array('custom', array(array($this, 'create_custom_field'))),
			array('custom', array(array($this, 'create_language_entries'))),
		);
	}

	protected $profilefield_name = 'raidplaner_role';

	protected $profilefield_database_type = array('VCHAR', '');

	protected $profilefield_data = array(
		'field_name'			=> 'raidplaner_role',
		'field_type'			=> 'profilefields.type.dropdown',
		'field_ident'			=> 'raidplaner_role',
		'field_length'			=> 0,
		'field_minlen'			=> 0,
		'field_maxlen'			=> 5,
		'field_novalue'			=> '1',
		'field_default_value'	=> '1',
		'field_validation'		=> '',
		'field_required'		=> 0,
		'field_show_novalue'	=> 0,
		'field_show_on_reg'		=> 0,
		'field_show_on_pm'		=> 0,
		'field_show_on_vt'		=> 0,
		'field_show_profile'	=> 0,
		'field_hide'			=> 1,
		'field_no_view'			=> 1,
		'field_active'			=> 1,
	);
	
	protected $profilefield_language_data = array(
		array(
			'option_id' => 0,
			'field_type' => 'profilefields.type.dropdown',
			'lang_value' => 'none',
		),
		array(
			'option_id' => 1,
			'field_type' => 'profilefields.type.dropdown',
			'lang_value' => 'tank',
		),
		array(
			'option_id' => 2,
			'field_type' => 'profilefields.type.dropdown',
			'lang_value' => 'heal',
		),
		array(
			'option_id' => 3,
			'field_type' => 'profilefields.type.dropdown',
			'lang_value' => 'melee',
		),
		array(
			'option_id' => 4,
			'field_type' => 'profilefields.type.dropdown',
			'lang_value' => 'range',
		),
	);
	
}
