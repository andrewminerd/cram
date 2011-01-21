<?php

class FileTestRegistry
{
	protected $files = array();
	protected $tests = array();

	public function __construct(array $files = array())
	{
		$this->files = $files;
	}

	public function addFile($filename, array $tests)
	{
		$this->files[$filename] = $tests;
	}

	public function updateTest($test, array $files)
	{
		foreach ($this->files as $filename=>$tests)
		{
			$covered = in_array($filename, $files);
			$added = array_search($test, $tests);

			if ($covered && $added === FALSE)
			{
				$this->files[$filename][] = $test;
			}
			elseif (!$covered && $added !== FALSE)
			{
				unset($this->files[$filename][$added]);
			}
		}
	}

	public function updateTest2($test, array $files)
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

	public function getFiles()
	{
		return array_keys($this->files);
	}

	public function getTestsForFile($filename)
	{
		return isset($this->files[$filename])
			? $this->files[$filename]
			: array();
	}
}

class TestRegistry
{
	protected $directories = array();
	protected $files = array();

	public function addDirectory($path, $suffix)
	{
		$path = realpath($path);

		isset($this->directories[$path])
			? $this->directories[$path][] = $suffix
			: $this->directories[$path] = array($suffix);
	}

	public function addFile($filename)
	{
		$filename = realpath($filename);
		$this->files[$filename] = TRUE;
	}

	public function getFiles()
	{
		return array_keys($this->files);
	}

	public function getPaths()
	{
		return array_keys($this->directories);
	}

	public function isTest($filename)
	{
		if (isset($this->files[$filename]))
		{
			return TRUE;
		}

		foreach ($this->directories as $path=>$match)
		{
			if (substr($filename, 0, strlen($path)) != $path)
			{
				continue;
			}

			foreach ($match as $suffix)
			{
				if (substr($filename, -strlen($suffix)) == $suffix)
				{
					// cache for future lookups?
					$this->files[$filename] = TRUE;
					return TRUE;
				}
			}
		}

		return FALSE;
	}
}

?>