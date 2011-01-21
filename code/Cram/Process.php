<?php

class Process
{
	/**
	 * @param string $cmd
	 * @param array $env string[]
	 * @param string $cwd
	 * @return Process
	 */
	public static function open($cmd, $cwd = null, array $env = null)
	{
		$spec = array(
			0 => array('pipe', 'r'), //stdin
			1 => array('pipe', 'w'), //stdout
			2 => array('pipe', 'w'), //stderr
		);

		if (($ph = proc_open($cmd, $spec, $pipes, $cwd, $env)) === FALSE)
		{
			throw new Exception('Could not execute command');
		}

		return new self($ph, $pipes);
	}

	protected $handle;
	protected $pid;
	protected $STDIN;
	protected $STDOUT;
	protected $STDERR;

	private function __construct($handle, array $pipes)
	{
		$status = proc_get_status($handle);

		$this->handle = $handle;
		$this->pid = $status['pid'];
		$this->STDIN = $pipes[0];
		$this->STDOUT = $pipes[1];
		$this->STDERR = $pipes[2];

		stream_set_blocking($this->STDOUT, 0);
		stream_set_blocking($this->STDERR, 0);
	}

	public function __destruct()
	{
		$this->close();
	}

	public function getSTDOUT()
	{
		return $this->STDOUT;
	}

	public function isRunning()
	{
		$s = proc_get_status($this->handle);
		return $s['running'];
	}

	public function writeClose($string)
	{
		$written = fwrite($this->STDIN, $string);

		fclose($this->STDIN);
		$this->STDIN = NULL;

		return $written;
	}

	public function read()
	{
		return stream_get_contents($this->STDOUT);
	}

	public function readError()
	{
		return stream_get_contents($this->STDERR);
	}

	public function readSelect($timeout = 1)
	{
		$read = array($this->STDOUT);
		$write = NULL;
		$except = NULL;

		if (@stream_select($read, $write, $except, $timeout) > 0)
		{
			$output = stream_get_contents($this->STDOUT);
			return $output;
		}
		return FALSE;
	}

	public function terminate($signal = NULL)
	{
		return proc_terminate($this->handle, $signal);
	}

	public function close()
	{
		if (!$this->handle) return;

		fclose($this->STDOUT);
		fclose($this->STDERR);

		$exit = proc_close($this->handle);
		$this->handle = NULL;
		return $exit;
	}

	public function wait()
	{
		$s = proc_get_status($this->handle);
		while ($s['running'])
		{
			usleep(1000);
			$s = proc_get_status($this->handle);
		}

		$exit = !$s['signaled']
			? $s['exitcode']
			: -1;
		return $exit;
	}

	public function waitClose()
	{
		$exit = $this->wait();
		$this->close();
		return $exit;
	}
}

?>