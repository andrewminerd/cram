<?php

class Cram
{
	protected $running = FALSE;

	/**
	 * @var FileMonitor
	 */
	protected $monitor;

	/**
	 * @var RunTests
	 */
	protected $watcher;

	/**
	 * @var array
	 */
	protected $projects = array();

	/**
	 * @var int
	 */
	protected $last_interrupt;

	public function onInterrupt()
	{
		if ($this->running)
		{
			$this->monitor->stopWatching();
			$this->running = FALSE;
		}
	}

	public function main(array $args)
	{
		$notifier = new Notify_LibNotify();
		$this->watcher = new RunTests(new TestNotifier($notifier));
		$this->monitor = new FileMonitor();

		$dir = '/tmp/phpunit_'.microtime(TRUE);
		mkdir($dir);

		foreach ($args as $config_file)
		{
			$proj = new Project(
				new PHPUnit_Configuration($config_file),
				new PHPUnit_CoverageXML($dir)
			);

			$this->projects[] = $proj;
			$this->watcher->addProject($proj);
		}

		do
		{
			$this->refreshProjects();

			$this->running = TRUE;
			$this->monitor->beginWatching($this->watcher);

			// 2 interrupts within 2 seconds kills us
			$quit = ($this->last_interrupt !== NULL
				&& (time() - $this->last_interrupt) <= 2);
			$this->last_interrupt = time();
		}
		while (!$quit);
	}

	protected function refreshProjects()
	{
		$this->monitor->clear();

		foreach ($this->projects as $project)
		{
			/* @var $project Project */
			$project->reset();
			$project->addToMonitor($this->monitor);
		}
	}
}

?>