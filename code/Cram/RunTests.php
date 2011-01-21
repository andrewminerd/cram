<?php

class RunTests implements FileWatcher
{
	/**
	 * @var Project
	 */
	protected $projects = array();

	/**
	 * @var TestWatcher
	 */
	protected $watcher;

	public function __construct(TestWatcher $w)
	{
		$this->watcher = $w;
	}

	public function addProject(Project $p)
	{
		$this->projects[] = $p;
	}

	public function onChanged(array $files, FileMonitor $monitor)
	{
		foreach ($this->projects as $project)
		{
			foreach ($files as $filename=>$op)
			{
				try
				{
					/* @var $project Project */
					$result = $project->testFile($filename);
					if ($result)
					{
						$this->notifyWatcher($filename, $result);
					}
				}
				catch (Exception $e)
				{
					$this->watcher->onException(
						$filename,
						$e->getMessage()
					);
				}
			}
		}
	}

	protected function notifyWatcher($filename, PHPUnit_TestResult $result)
	{
		if ($result->getErrorCount() > 0)
		{
			$this->watcher->onError($filename, $result);
		}
		elseif ($result->getFailureCount() > 0)
		{
			$this->watcher->onFail($filename, $result);
		}
		else
		{
			$this->watcher->onPass($filename, $result);
		}
	}
}

?>
