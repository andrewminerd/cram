<?php

interface PHPUnit_CoverageProvider
{
	public function getTestCoverage();

	public function reset();
}

class PHPUnit_CoverageXML
{
	protected $directory;

	public function __construct($coverage_dir)
	{
		$this->directory = $coverage_dir;
	}

	public function getPath()
	{
		return $this->directory;
	}

	public function reset()
	{
		$i = new DirectoryIterator($this->directory);
		foreach ($i as $n)
		{
			/* @var $file SplFileInfo */
			$file = $n->getFileInfo();
			if (!$file->isDir()
				&& $file->isReadable())
			{
				unlink($file->getPathname());
			}
		}
	}

	/**
	 * @return array
	 */
	public function getFileTests()
	{
		$i = new DirectoryIterator($this->directory);
		$coverage = array();

		foreach ($i as $n)
		{
			$file = $n->getFileInfo();
			/* @var $file SplFileInfo */
			if (!$file->isDir())
			{
				$coverage = $this->readCoverageFile($file->getPathname(), $coverage);
			}
		}

		return $coverage;
	}

	/**
	 * @return array
	 */
	public function getTestCoverage()
	{
		$coverage = $this->getFileTests();
		$files = array();

		foreach ($coverage as $test=>$covered)
		{
			foreach ($covered as $file)
			{
				isset($files[$file])
					? $files[$file][] = $test
					: $files[$file] = array($test);
			}
		}

		return $files;
	}

	protected function readCoverageFile($filename, array $coverage = array())
	{
		$doc = new DOMDocument();
		$doc->load($filename);

		$xpath = new DOMXPath($doc);
		$files = $xpath->evaluate('//coveredFile');

		foreach ($files as $node)
		{
			$filename = (string)$node->getAttribute('fullPath');
			$tests = array();

			$t = $xpath->evaluate('//test', $node);
			foreach ($t as $test)
			{
				$test_file = (string)$test->getAttribute('fullPath');
				$tests[$test_file] = $test_file;
			}

			if (count($tests))
			{
				$coverage[$filename] = array_values($tests);
			}
		}

		return $coverage;
	}
}

?>