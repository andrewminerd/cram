<?php

class Poll
{
	protected $files = array();
	protected $directories = array();
	protected $watching = FALSE;

	public function __construct(array $files)
	{
		$this->files = $files;//array_map('realpath', $files);
	}

	public function addFile($file)
	{
		/*if (($file = realpath($file)) === FALSE)
		{
			throw new InvalidArgumentException('File must exist');
		}*/
		$this->files[] = $file;
	}

	public function addDirectory($path)
	{
		if (!is_dir($path))
		{
			throw new InvalidArgumentException('Path must be a directory');
		}

		$iterator = new RecursiveDirectoryIterator($path);
		$this->directories[] = new RecursiveIteratorIterator($iterator);
	}

	public function beginWatching(FileWatcher $w)
	{
		$this->watching = TRUE;
		$modified = array();

		foreach ($this->directories as $dir)
		{
			foreach ($dir as $f)
				$modified[$f->getPathname()] = $f->getMTime();
		}
		foreach ($this->files as $filename)
		{
			if (($mtime = @filemtime($filename)) !== FALSE)
				$modified[$filename] = $mtime;
		}

		while ($this->watching)
		{
			$new = array();

			// PHP caches the results of stat calls;
			// we need the real mtime for this to work
			clearstatcache();

			foreach ($this->directories as $dir)
			{
				$this->pollDirectory($dir, $w, $new, $modified);
			}

			$this->pollFiles($w, $new, $modified);
			$this->pollDeletes($w, $modified);

			$modified = $new;
			sleep(2);
		}
	}

	public function stopWatching()
	{
		$this->watching = FALSE;
	}

	protected function pollDirectory(Iterator $dir, FileWatcher $w, array &$new, array &$old)
	{
		foreach ($dir as $file)
		{
			$filename = $file->getPathname();
			$mtime = $file->getMTime();

			if (!isset($old[$filename]))
			{
				$w->onCreated($filename);
			}
			elseif ($old[$filename] < $mtime)
			{
				$w->onModification($filename);
			}

			$new[$filename] = $mtime;
			unset($old[$filename]);
		}
	}

	protected function pollFiles(FileWatcher $w, array &$new, array &$old)
	{
		foreach ($this->files as $filename)
		{
			if (isset($new[$filename])
				|| ($mtime = @filemtime($filename)) === FALSE)
			{
				continue;
			}

			if (!isset($old[$filename]))
			{
				$w->onCreated($filename);
			}
			elseif ($old[$filename] < $mtime)
			{
				$w->onModification($filename);
			}

			$new[$filename] = $mtime;
			unset($old[$filename]);
		}
	}

	protected function pollDeletes(FileWatcher $w, array $old)
	{
		foreach ($old as $filename=>$mtime)
		{
			$w->onDeleted($filename);
		}
	}
}

require '../code/Cram/FileWatcher.php';

class E implements FileWatcher
{
	public function onCreated($filename)
	{
		echo "CREATED: {$filename}\n";
	}
	public function onModification($filename)
	{
		echo "MODIFIED: {$filename}\n";
	}
	public function onDeleted($filename)
	{
		echo "DELETED: {$filename}\n";
	}
}

$dir = '/virtualhosts/projects/recash/Monitor';
$files = array('/tmp/blah');

$fm = new Poll($files);
$fm->addDirectory($dir);
$fm->beginWatching(new E());

?>