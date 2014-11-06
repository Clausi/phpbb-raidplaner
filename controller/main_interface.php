<?php

namespace clausi\raidplaner\controller;

use Symfony\Component\DependencyInjection\ContainerInterface;

interface main_interface
{

	public function createRaid($schedule_id, $raid_time, $invite_time, $start_time, $end_time, $autoaccept);
	public function addAttendees($raid_id);
	public function index();
	public function view($id);
	public function setUserstatus($raid_id, $status_id);
	public function setModstatus($raid_id);
	
}
