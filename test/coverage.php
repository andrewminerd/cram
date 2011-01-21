<?php

class Cram_TestCoverage
{
	protected $monitor;

	protected $files = array();

	protected $tests = array();

	public function __construct(FileMonitor $monitor)
	{
		$this->monitor = $monitor;
	}

	public function getFileTests($file)
	{
		return isset($this->files[$file])
			? $this->files[$file]
			: array();
	}

	public function updateTest($test, array $files)
	{
		$old = isset($this->tests[$test])
			? $this->tests[$test]
			: array();

		foreach (array_diff($files, $old) as $added)
		{
			$this->add($added, $test);
		}

		foreach (array_diff($old, $files) as $deleted)
		{
			$this->delete($deleted, $test);
		}

		$this->tests[$test] = $files;
	}

	protected function add($file, $test)
	{
		if (!isset($this->files[$file]))
		{
			// start watching
			$this->monitor->addFile($file);
			$this->files[$file] = array($test);
		}
		else
		{
			$this->files[$file][] = $test;
		}
	}

	protected function delete($file, $test)
	{
		if (isset($this->files[$file])
			&& ($key = array_search($test, $this->files[$file])) !== FALSE)
		{
			unset($this->files[$file][$key]);
			if (empty($this->files[$file]))
			{
				// stop watching
				$this->monitor->removeFile($file);
				unset($this->files[$file]);
			}
		}
	}
}

require '../code/Cram/FileMonitor.php';

$m = new FileMonitor();
$c = new Cram_TestCoverage($m);

$c->updateTest('/tmp/blah', array('/tmp/woot'));
var_dump($c);

$c->updateTest('/tmp/blah', array('/tmp/hi'));
var_dump($c);


?>