<?php

class PHPUnit_TestResult
{
	/**
	 * Runs a test case and returns the result
	 *
	 * @param unknown_type $test_file
	 * @param unknown_type $bin
	 * @return PHPUnit_TestResult
	 */
	public static function runTest(PHPUnit_Configuration $config, $dsn = null, $revision = 0, $cwd = null, $bin = '/usr/bin/phpunit')
	{
		$log_file = tempnam('/tmp/', 'phpunit_');

		// write temporary config file
		$config_file = tempnam('/tmp', 'phpunit_');
		$config->save($config_file);

		$cmd = $bin.' --configuration '.escapeshellarg($config_file)
			.' --log-xml '.escapeshellarg($log_file);

		// PHPUnit appears to load the bootstrap file at different times
		// depending on whether its in this config or the command line
		/*if (($f = $config->getBootstrapFile()) !== FALSE)
		{
			$cmd .= ' --bootstrap '.escapeshellarg($f);
		}*/

		// database logging info currently can't
		// be placed into the XML config file
		if ($dsn !== NULL
			&& $revision > 0)
		{
			$cmd .= ' --test-db-dsn '.escapeshellarg($dsn)
				.' --test-db-log-rev '.(int)$revision;
		}

		echo $cwd, " ", $cmd, "\n";

		$proc = Process::open($cmd, $cwd);
		$out = '';

		while ($proc->isRunning())
		{
			$out .= $buff = $proc->read();
			echo $buff;
			usleep(10);
		}

		$out .= $buff = $proc->read();
		echo $buff;

		$proc->close();

		// cannot rely on PHPUnit exit code to determine
		// whether test cases were run; instead, check
		// to see whether or not a log file was created
		if (!file_exists($log_file)
			|| !filesize($log_file))
		{
			// clean up temporary files...
			unlink($log_file);
			unlink($config_file);

			throw new Exception($out, $exit);
		}

		$doc = new DOMDocument();
		$doc->load($log_file);

		// clean up temporary files...
		unlink($log_file);
		unlink($config_file);

		return new self('', $doc, $out);
	}

	protected $name;
	protected $output;
	protected $tests = 0;
	protected $failures = 0;
	protected $assertions = 0;
	protected $errors = 0;
	protected $test = array();
	protected $failed = array();
	protected $error = array();

	public function __construct($name, DOMDocument $log, $output)
	{
		$this->name = $name;
		$this->output = $output;
		$this->process($log);
	}

	public function getName()
	{
		return $this->name;
	}

	public function getOutput()
	{
		return $this->output;
	}

	public function getTestCount()
	{
		return $this->tests;
	}

	public function getAssertionCount()
	{
		return $this->assertions;
	}

	public function getFailureCount()
	{
		return $this->failures;
	}

	public function getErrorCount()
	{
		return $this->errors;
	}

	public function getTests()
	{
		return $this->test;
	}

	public function getFailed()
	{
		return $this->failed;
	}

	public function getErrors()
	{
		return $this->error;
	}

	public function merge(PHPUnit_TestResult $result)
	{
		$new = clone $this;
		$new->tests += $result->tests;
		$new->assertions += $result->assertions;
		$new->failures += $result->failures;
		$new->errors += $result->errors;

		$new->test = array_merge($new->test, $result->test);
		$new->failed = array_merge($new->failed, $result->failed);
		$new->error = array_merge($new->error, $result->error);

		return $new;
	}

	protected function process(DOMDocument $log)
	{
		$this->assertions = 0;
		$this->failures = 0;
		$this->errors = 0;
		$this->test = array();
		$this->failed = array();
		$this->error = array();

		$xpath = new DOMXPath($log);

		foreach ($xpath->query('//testcase') as $node)
		{
			$this->test[] = $this->getTestName($node);
			$this->assertions += (int)$node->getAttribute('assertions');
			$this->tests++;
		}

		foreach ($xpath->query('//failure') as $node)
		{
			/* @var $node DOMNode */
			$this->failed[] = $this->getTestName($node->parentNode);
			$this->failures++;
		}

		foreach ($xpath->query('//error') as $node)
		{
			/* @var $node DOMNode */
			$this->error[] = (string)$node->nodeValue;
			$this->errors++;
		}
	}

	protected function getTestName(DOMElement $node)
	{
		// if they don't have the class attribute, it's
		// probably a single test with multiple datasets
		if (!$node->hasAttribute('class'))
		{
			return $node->parentNode->parentNode->getAttribute('file')
				.'::'.(string)$node->parentNode->getAttribute('name')
				.'::'.(string)$node->getAttribute('name');
		}

		/*return new PHPUnit_TestReference(
			$node->getAttribute('file'),
			$node->getAttribute('class'),
			$node->getAttribute('name')
		);*/
		return (string)$node->getAttribute('file')
			.'::'.(string)$node->getAttribute('class')
			.'::'.(string)$node->getAttribute('name');
	}
}

class PHPUnit_TestReference
{
	protected $file,
		$class,
		$method,
		$dataset;

	public function __construct($file, $class, $method, $dataset = null)
	{
		$this->file = $file;
		$this->class = $class;
		$this->method = $method;
		$this->dataset = $dataset;
	}

	public function getFile()
	{
		return $this->file;
	}

	public function getClass()
	{
		return $this->class;
	}

	public function getMethod()
	{
		return $this->method;
	}

	public function getDataset()
	{
		return $this->dataset;
	}
}

?>
