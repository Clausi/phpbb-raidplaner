<?php

namespace clausi\raidplaner\migrations;

class profilefield_class extends \phpbb\db\migration\profilefield_base_migration
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

	protected $profilefield_name = 'raidplaner_class';

	protected $profilefield_database_type = array('VCHAR', '');

	protected $profilefield_data = array(
		'field_name'			=> 'raidplaner_class',
		'field_type'			=> 'profilefields.type.dropdown',
		'field_ident'			=> 'raidplaner_class',
		'field_length'			=> 0,
		'field_minlen'			=> 0,
		'field_maxlen'			=> 12,
		'field_novalue'			=> '0',
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
			'option_id' => 1,
			'field_type' => 'profilefields.type.dropdown',
			'lang_value' => 'none',
		),
		array(
			'option_id' => 2,
			'field_type' => 'profilefields.type.dropdown',
			'lang_value' => 'paladin',
		),
		array(
			'option_id' => 3,
			'field_type' => 'profilefields.type.dropdown',
			'lang_value' => 'hunter',
		),
		array(
			'option_id' => 4,
			'field_type' => 'profilefields.type.dropdown',
			'lang_value' => 'rogue',
		),
		array(
			'option_id' => 5,
			'field_type' => 'profilefields.type.dropdown',
			'lang_value' => 'priest',
		),
		array(
			'option_id' => 6,
			'field_type' => 'profilefields.type.dropdown',
			'lang_value' => 'deathknight',
		),
		array(
			'option_id' => 7,
			'field_type' => 'profilefields.type.dropdown',
			'lang_value' => 'shaman',
		),
		array(
			'option_id' => 8,
			'field_type' => 'profilefields.type.dropdown',
			'lang_value' => 'mage',
		),
		array(
			'option_id' => 9,
			'field_type' => 'profilefields.type.dropdown',
			'lang_value' => 'warlock',
		),
		array(
			'option_id' => 10,
			'field_type' => 'profilefields.type.dropdown',
			'lang_value' => 'monk',
		),
		array(
			'option_id' => 11,
			'field_type' => 'profilefields.type.dropdown',
			'lang_value' => 'druid',
		),
		array(
			'option_id' => 12,
			'field_type' => 'profilefields.type.dropdown',
			'lang_value' => 'warrior',
		),
	);
	
}
