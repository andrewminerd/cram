<?php

$root = dirname(__FILE__).'/';
require $root.'../code/Cram/Process.php';
require $root.'../code/Cram/TestWatcher.php';
require $root.'../code/Cram/PHPUnit/Configuration.php';
require $root.'../code/Cram/PHPUnit/TestResult.php';


interface Stream
{
	public function read($max = NULL);
	public function write($string);
	public function close();
}

interface StreamReader
{
	public function onRead($stream);
}

interface StreamWriter
{
	public function onWrite($stream);
}

class Engine
{
	protected $read = array();
	protected $read_map = array();
	protected $write = array();
	protected $write_map = array();

	public function watchRead($stream, StreamReader $reader)
	{
		$this->read[(int)$stream] = $stream;
		$this->read_map[(int)$stream] = $reader;
	}

	public function unwatchRead($stream)
	{
		unset($this->read[(int)$stream]);
		unset($this->read_map[(int)$stream]);
	}

	public function watchWrite($stream, StreamWriter $writer)
	{
		$this->write[(int)$stream] = $stream;
		$this->write_map[(int)$stream] = $writer;
	}

	public function unwatchWrite($stream)
	{
		unset($this->watch[(int)$stream]);
		unset($this->watch_map[(int)$stream]);
	}

	public function select($timeout = 2)
	{
		$read = $this->read;
		$write = $this->write;
		$expect = array();

		if (stream_select($read, $write, $expect, $timeout) > 0)
		{
			foreach ($read as $stream)
			{
				/* @var $reader Reader */
				$reader = $this->read_map[(int)$stream];
				$reader->onRead($stream);
			}

			foreach ($write as $stream)
			{
				/* @var $write Writer */
				$writer = $this->write_map[(int)$stream];
				$write->onWrite($stream);
			}
		}

		return (count($this->read)
			+ count($this->write));
	}
}

class PHPUnitReader implements StreamReader
{
	protected $engine;
	protected $process;
	protected $watcher;
	protected $log_file;

	public function __construct(Engine $e, Process $p, TestWatcher $w, $log_file)
	{
		$this->engine = $e;
		$this->process = $p;
		$this->watcher = $w;
		$this->log_file = $log_file;
	}

	public function onRead($stream)
	{
		$running = $this->process->isRunning();

		if (!$running)
		{
			$out = fread($stream, 8092);
			$this->engine->unwatchRead($stream);

			$doc = new DomDocument();
			$doc->load($this->log_file);
			$result = new PHPUnit_TestResult('', $doc);

			$this->watcher->onPass('', $result);
		}
	}
}

class Test implements TestWatcher
{
	public function onException($file, $out) {}
	public function onError($file, PHPUnit_TestResult $result) {}
	public function onFail($file, PHPUnit_TestResult $result) {}
	public function onPass($file, PHPUnit_TestResult $result)
	{
		echo "Assertions: {$result->getAssertions()}, "
			."Failures: {$result->getFailures()}, "
			."Errors: {$result->getErrors()}\n";
	}
}

$engine = new Engine();
$watcher = new Test();
$config = new PHPUnit_Configuration('./config.xml');

for ($i = 0; $i < 5 ; $i++)
{
	$log_file = tempnam('/tmp/', 'phpunit_');
	$cmd = "phpunit --configuration {$config->getFilename()} --log-xml {$log_file} > /dev/null";

	echo "{$cmd}\n";
	$p = Process::open($cmd);

	$reader = new PHPUnitReader($engine, $p, $watcher, $log_file);
	$engine->watchRead($p->getSTDOUT(), $reader);
}

while ($engine->select(10) > 0) usleep(1000);

?>