<?php

abstract class FileMonitor
{
	const OP_CREATED = 1;
	const OP_MODIFIED = 2;
	const OP_MOVED = 4;
	const OP_DELETED = 8;

	protected $files = array();
	protected $directories = array();
	protected $valid = FALSE;

	protected $changed = array();
	protected $last = array();

	public function addFile($file)
	{
		$this->files[] = $file;
		$this->valid = FALSE;
	}

	public function addDirectory($path)
	{
		$this->directories[] = $path;
		$this->valid = FALSE;
	}

	public function watch(FileWatcher $w, $delay = 1, $interval = 20)
	{
		$this->watching = TRUE;

		while ($this->watching)
		{
			$now = time();

			$this->tick($now, $this->valid);
			$this->valid = TRUE;

			$this->getChangedFiles($w, $now, $delay, $interval);
			//if (count($changed)) $w->onModified($changed);
		}
	}

	public function stop()
	{
		$this->watching = FALSE;
	}

	abstract protected function tick($now, $valid = TRUE);

	protected function markFile($file, $op, $time)
	{
		if (isset($this->changed[$file]))
		{
			$this->changed[$file][0] |= $op;
			$this->changed[$file][1] = $time;
		}
		else
		{
			$this->changed[$file] = array($op, $time);
		}
	}

	protected function getChangedFiles(FileWatcher $w, $now, $delay, $interval)
	{
		$changed = array();

		foreach ($this->changed as $file=>$info)
		{
			list($op, $time) = $info;

			if (($now - $time) >= $delay
				&& (!isset($this->last[$file])
					|| ($now - $this->last[$file]) >= $interval))
			{
				//$changed[$file] = $op;
				if ($op & self::OP_DELETED
					&& !$op & self::OP_CREATED)
				{
					if ($op & self::OP_CREATED === 0)
						$w->onDeleted($file);
				}
				elseif (($op & self::OP_CREATED || $op & self::OP_MOVED)
					&& !$op & self::OP_DELETED)
				{
					$w->onCreated($file);
				}
				elseif ($op & self::OP_MODIFIED)
				{
					$w->onModified($file);
				}

				$this->last[$file] = $now;
				unset($this->changed[$file]);
			}
		}

		return $changed;
	}
}

class Monitor_INotify extends FileMonitor
{
	protected $bin;

	/**
	 * @var Process
	 */
	protected $proc;

	public function __construct($bin = '/usr/bin/inotifywait')
	{
		$this->bin = $bin;
	}

	public function watch(FileWatcher $w, $delay = 1, $interval = 20)
	{
		$this->proc = $this->startProcess();
		$this->valid = TRUE;

		parent::watch($w, $delay, $interval);

		$this->endProcess($this->proc);
	}

	protected function tick($now, $valid = TRUE)
	{
		$out = $this->proc->readSelect(1);

		if ($out !== FALSE)
		{
			$files = array_filter(explode("\n", $out));
			foreach ($files as $f)
			{
				$this->markFile($f, self::OP_MODIFIED, $now);
			}
		}

		// if the list of files we should be watching has
		// changed, restart the inotifywait process
		if (!$valid)
		{
			$this->endProcess($this->proc);
			$this->proc = $this->startProcess();
		}
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