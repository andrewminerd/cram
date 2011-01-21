<?php

class Project
{
	/**
	 * @var PHPUnit_Configuration
	 */
	protected $config;

	/**
	 * @var TestRegistry
	 */
	protected $tests;

	/**
	 * @var FileTestRegistry
	 */
	protected $reg;

	/**
	 * @var PHPUnit_CoverageXML
	 */
	protected $coverage;

	/**
	 * @var string
	 */
	protected $dead_file;

	public function __construct(PHPUnit_Configuration $config, PHPUnit_CoverageXML $coverage)
	{
		$this->config = $config;
		$this->tests = $config->getTests();
		$this->coverage = $coverage;
	}

	public function reset()
	{
		try
		{
			// if this fails, nothing will change
			$this->config->reload();
		}
		catch (Exception $e)
		{
		}

		$this->config->addLog(
			'coverage-source',
			$this->coverage->getPath()
		);

		// run the entire set
		if ($this->run($this->config) !== FALSE)
		{
			$this->reg = new FileTestRegistry($this->coverage->getFileTests());
			return TRUE;
		}
		return FALSE;
	}

	public function addToMonitor(FileMonitor $m)
	{
		/* watch all tests and test directories */
		$tests = $this->config->getTests();
		foreach ($tests->getPaths() as $dir) $m->addFile($dir);
		foreach ($tests->getFiles() as $file) $m->addFile($file);

		/* watch our configuration file */
		$m->addFile($this->config->getFilename());

		/* watch the file that's killing us, if any */
		if ($this->dead_file)
		{
			$m->addFile($this->dead_file);
		}
		/* watch all covered files */
		elseif ($this->reg)
		{
			foreach ($this->reg->getFiles() as $file)
			{
				$m->addFile($file);
			}
		}
	}

	protected function getNewConfig(array $tests = NULL)
	{
		$config = clone $this->config;
		if ($tests)
		{
			$config->clearTests();
			$config->addTestFiles($tests);
		}
		return $config;
	}

	/**
	 * @param string $filename
	 * @return PHPUnit_TestResult
	 */
	public function testFile($filename)
	{
		$config = NULL;
		$is_test = FALSE;

		if ($filename == $this->config->getFilename()
			/*|| $filename == $this->dead_file*/
			|| !$this->reg)
		{
			$this->reset();
			return;
		}

		if ($this->tests->isTest($filename))
		{
			$config = $this->getNewConfig(array($filename));
			$is_test = TRUE;
		}
		elseif ($this->reg
			&& $tests = $this->reg->getTestsForFile($filename))
		{
			$config = $this->getNewConfig($tests);
		}

		if ($config)
		{
			$result = $this->run($config);

			/*foreach ($this->coverage->getTestCoverage() as $test=>$files)
			{
				$this->reg->updateTest($test, $files);
			}*/

			// get new coverage info
			if (!$is_test)
			{
				$tests = $this->coverage->getFileTests();
				if (isset($tests[$filename]))
				{
					$this->reg->addFile($filename, $tests[$filename]);
				}
			}
			else
			{
				$files = $this->coverage->getTestCoverage();
				if (isset($files[$filename]))
				{
					$this->reg->updateTest($filename, $files[$filename]);
				}
			}

			return $result;
		}
	}

	protected function run(PHPUnit_Configuration $config)
	{
		$this->coverage->reset();
		$this->dead_file = NULL;

		try
		{
			$result = PHPUnit_TestResult::runTest(
				$config,
				null,
				0,
				dirname($config->getFilename())
			);
			return $result;
		}
		catch (Exception $e)
		{
			$out = $e->getMessage();

			if ((preg_match('#Fatal error:\s+.+?\s+in\s+(.+?)(?:\(\d+\))?\s+#', $out, $m)
				|| preg_match('#Parse error:\s+.+?\sin\s+(.+?)(?:\(\d+\))\s+#', $out, $m))
				&& is_file($m[1]))
			{
				$this->dead_file = $m[1];
			}
			return FALSE;
		}
	}
}

?>