<?php

/**
 * Receive notification of test results
 *
 */
interface TestWatcher
{
	/**
	 * Triggered when the tests do not complete running (PHPUnit dies)
	 *
	 * @param string $filename File being tested
	 * @param string $output PHPUnit output
	 * @return void
	 */
	public function onException($filename, $output);

	/**
	 * Triggered when one or more errors occur during testing
	 *
	 * NOTE: Errors take precedence over failures.
	 *
	 * @param string $filename File being tested
	 * @param PHPUnit_TestResult $result
	 * @return void
	 */
	public function onError($filename, PHPUnit_TestResult $result);

	/**
	 * Triggered when one or more failures occur during testing
	 *
	 * @param string $filename File being tested
	 * @param PHPUnit_TestResult $result
	 * @return void
	 */
	public function onFail($filename, PHPUnit_TestResult $result);

	/**
	 * Triggered when no errors or failures occur during testing
	 *
	 * @param string $filename File being tested
	 * @param PHPUnit_TestResult $result
	 */
	public function onPass($filename, PHPUnit_TestResult $result);
}

class CompositeTestWatcher implements TestWatcher
{
	protected $watchers = array();

	public function add(TestWatcher $w)
	{
		$this->watchers[] = $w;
	}

	public function onException($filename, $output)
	{
		foreach ($this->watchers as $w)
		{
			$w->onException($filename, $output);
		}
	}

	public function onError($filename, PHPUnit_TestResult $result)
	{
		foreach ($this->watchers as $w)
		{
			$w->onError($filename, $result);
		}
	}

	public function onFail($filename, PHPUnit_TestResult $result)
	{
		foreach ($this->watchers as $w)
		{
			$w->onFail($filename, $result);
		}
	}

	public function onPass($filename, PHPUnit_TestResult $result)
	{
		foreach ($this->watchers as $w)
		{
			$w->onPass($filename, $result);
		}
	}
}

?>