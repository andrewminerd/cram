<?php

class FileMonitor
{
	protected $bin;
	protected $files = array();
	protected $valid = FALSE;
	protected $watching = FALSE;

	/**
	 * @var Process
	 */
	protected $proc;

	public function __construct(array $files = array(), $bin = '/usr/bin/inotifywait')
	{
		$this->files = array_map('realpath', $files);
		$this->bin = $bin;
	}

	public function clear()
	{
		$this->files = array();
	}

	public function addFile($filename)
	{
		// attempt to use the realpath whenever possible, but
		// don't overwrite the filename with FALSE
		if (($f = realpath($filename)) !== FALSE)
		{
			$filename = $f;
		}

		$this->files[] = $filename;
		$this->valid = FALSE;
	}

	public function removeFile($filename)
	{
		// attempt to use the realpath whenever possible, but
		// don't overwrite the filename with FALSE
		if (($f = realpath($filename)) !== FALSE)
		{
			$filename = $f;
		}

		if (($key = array_search($filename, $this->files)) !== FALSE)
		{
			unset($this->files[$key]);
			$this->valid = FALSE;
		}
	}

	public function beginWatching(FileWatcher $w, $delay = 1, $interval = 20)
	{
		$proc = $this->startProcess();

		$this->watching = TRUE;
		$this->valid = TRUE;
		$modified = array();

		$delta = new FileDelta();

		while ($this->watching)
		{
			$out = $proc->readSelect(1);
			$time = microtime(TRUE);

			if ($out !== FALSE)
			{
				$files = array_filter(explode("\n", $out));
				foreach ($files as $f)
				{
					$delta->markFile($f, FileDelta::MODIFIED, $time);
				}
			}

			// if the list of files we should be watching has changed,
			// restart the fileschanged process -- we do this after the
			// readSelect to ensure that we've received all notifications
			// from the old process before killing it off
			if (!$this->valid
				|| !$proc->isRunning())
			{
				$this->endProcess($proc);
				$proc = $this->startProcess();
				$this->valid = TRUE;
				continue;
			}

			$modified = $delta->getChangedFiles($time, $delay, $interval);
			if (count($modified) > 0)
			{
				$w->onChanged($modified, $this);
			}
		}

		$this->endProcess($proc);
	}

	public function stopWatching()
	{
		$this->watching = FALSE;
	}

	/**
	 * @return Process
	 */
	protected function startProcess()
	{
		$cmd = $this->bin." -rqme CREATE,MODIFY,CLOSE_WRITE,MOVED_TO --format '%w%f' --fromfile -";

		echo "Monitoring:\n  - ", implode("\n  - ", $this->files), "\n";

		$proc = Process::open($cmd);
		$proc->writeClose(implode("\n", $this->files));
		return $proc;
	}

	protected function endProcess(Process $proc)
	{
		$proc->terminate(SIGINT);
		$proc->waitClose();
	}
}

?>