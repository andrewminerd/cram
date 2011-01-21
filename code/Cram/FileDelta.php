<?php

class FileDelta
{
	const CREATED = 1;
	const MODIFIED = 2;
	const MOVED = 4;
	const DELETED = 8;

	protected $changed = array();
	protected $last = array();

	public function markFile($filename, $op, $time)
	{
		if (isset($this->changed[$file]))
		{
			$this->changed[$filename][0] |= $op;
			$this->changed[$filename][1] = $time;
		}
		else
		{
			$this->changed[$filename] = array($op, $time);
		}
	}

	public function getChangedFiles($now, $delay, $interval)
	{
		$changed = array();

		foreach ($this->changed as $filename=>$info)
		{
			list($op, $time) = $info;

			if (($now - $time) >= $delay
				&& (!isset($this->last[$filename])
					|| ($now - $this->last[$filename]) >= $interval))
			{
				$changed[$filename] = $op;

				$this->last[$filename] = $now;
				unset($this->changed[$filename]);
			}
		}

		return $changed;
	}
}

?>