<?php

namespace clausi\raidplaner\controller;

use Symfony\Component\DependencyInjection\ContainerInterface;

interface main_interface
{

	public function handle();
	public function view($id);
	
}
