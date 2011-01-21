<?php

class TestNotifier implements TestWatcher
{
	const NOTIFY_DELAY = 480;

	/**
	 * @var Notifier
	 */
	protected $notifier;

	protected $last = array();

	public function __construct(Notifier $notifier)
	{
		$this->notifier = $notifier;
	}

	public function onException($filename, $output)
	{
		if (preg_match('#\s*[a-zA-Z]*Exception:\s+(.+)#', $output, $m))
		{
			$output = $m[1];
		}

		if ($this->shouldNotify($filename, Notifier::TYPE_EXCEPTION))
		{
			$this->notifier->notify(
				'Error',
				'The test cases could not run due to an error: '.$output,
				Notifier::TYPE_ERROR
			);
		}
	}

	public function onError($filename,  PHPUnit_TestResult $result)
	{
		if ($this->shouldNotify($filename, Notifier::TYPE_ERROR))
		{
			$body = "The recent modification to ".basename($filename)." caused test cases to fail because of an error\n\n"
				.$this->getCounts($result);

			$this->notifier->notify(
				'Test cases failed',
				$body,
				Notifier::TYPE_ERROR
			);
		}
	}

	public function onFail($filename, PHPUnit_TestResult $result)
	{
		if ($this->shouldNotify($filename, Notifier::TYPE_FAIL))
		{
			$body = "The recent modification to ".basename($filename)." caused test cases to fail.\n\n"
				.$this->getCounts($result);

			$this->notifier->notify(
				'Test cases failed',
				$body,
				Notifier::TYPE_FAIL
			);
		}
	}

	public function onPass($filename, PHPUnit_TestResult $result)
	{
		if ($this->shouldNotify($filename, Notifier::TYPE_PASS))
		{
			$body = "The recent modification to ".basename($filename)." resolved its failed test cases.\n\n"
				.$this->getCounts($result);

			$this->notifier->notify(
				'Test cases passed',
				$body,
				Notifier::TYPE_PASS
			);
			unset($this->failed[$filename]);
		}
	}

	protected function getCounts(PHPUnit_TestResult $result)
	{
		$count = "Assertions: {$result->getAssertionCount()}";

		if (($failed = $result->getFailureCount()) > 0)
		{
			$count .= ", Failed: {$failed}";
		}

		if (($errors = $result->getErrorCount()) > 0)
		{
			$count .= ", Errors: {$errors}";
		}

		return $count;
	}

	protected function shouldNotify($filename, $type)
	{
		return TRUE;

		$last = isset($this->last[$filename])
			? $this->last[$filename]
			: NULL;
		$now = time();

		if ($last === NULL
			|| $last[1] !== $type
			|| ($type !== Notifier::TYPE_PASS && ($now - $last[0]) > self::NOTIFY_DELAY))
		{
			$this->last[$filename] = array($now, $type);
			return TRUE;
		}
		return FALSE;
	}
}

?>