<?php

class Status implements TestWatcher
{
	/**
	 * @var Notifier
	 */
	protected $notifier;

	protected $status = array();
	protected $failed = 0;
	protected $passed = 0;
	protected $error = 0;

	public function __construct(Notifier $n)
	{
		$this->notifier = $n;
	}

	public function onException($filename, $output)
	{
	}

	public function onError($filename, PHPUnit_TestResult $result)
	{
		$this->markTest($filename, Notifier::TYPE_ERROR);
	}

	public function onFail($filename, PHPUnit_TestResult $result)
	{
		$this->markTest($filename, Notifier::TYPE_FAIL);
	}

	public function onPass($filename, PHPUnit_TestResult $result)
	{
		$this->markTest($filename, Notifier::TYPE_PASS);
	}

	protected function notify($status)
	{
		if ($this->error > 0)
		{
			$overall = Notifier::TYPE_ERROR;
		}
		elseif ($this->failed > 0)
		{
			$overall = Notifier::TYPE_FAIL;
		}
		elseif ($this->passed > 0)
		{
			$overall = Notifier::TYPE_PASS;
		}
	}

	protected function getCounts(PHPUnit_TestResult $result)
	{
		$count = "Passing: {$this->passed}";

		if ($this->failed > 0)
		{
			$count .= ", Failing: {$this->failed}";
		}

		if ($this->error > 0)
		{
			$count .= ", Errors: {$this->error}";
		}

		return $count;
	}

	protected function markTest($filename, $status)
	{
		$old = isset($this->status[$filename])
			? $this->status[$filename]
			: NULL;

		if ($old === $status)
		{
			return FALSE;
		}

		switch ($old)
		{
			case  Notifier::TYPE_FAIL:
				$this->failed--;
				break;
			case  Notifier::TYPE_PASS:
				$this->passed--;
				break;
			case  Notifier::TYPE_ERROR:
				$this->error--;
				break;
		}

		switch ($status)
		{
			case  Notifier::TYPE_FAIL:
				$this->failed++;
				break;
			case  Notifier::TYPE_PASS:
				$this->passed++;
				break;
			case  Notifier::TYPE_ERROR:
				$this->error++;
				break;
		}

		$this->status[$filename] = $status;
		return TRUE;
	}
}

?>