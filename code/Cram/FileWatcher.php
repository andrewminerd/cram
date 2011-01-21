<?php

interface FileWatcher
{
	/**
	 * Fired when file modifications have been detected
	 *
	 * The single parameter is an array of files (key) and the operation
	 * that was detected (value).
	 *
	 * @param array $files array(file => op, ...)
	 * @return void
	 */
	public function onChanged(array $files, FileMonitor $monitor);
}

class CompositeFileWatcher implements FileWatcher
{
	protected $watchers = array();

	public function add(FileWatcher $w, FileMonitor $monitor)
	{
		$this->watchers[] = $w;
	}

	public function onChanged(array $files, FileMonitor $monitor)
	{
		foreach ($this->watchers as $w) $w->onChanged($files);
	}
}

?>