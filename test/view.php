<?php

interface Cram_View
{
	public function onFileChanged($filename);

	public function onTestRun($file);
	public function onTestError($file, $test);
	public function onTestFail($file, $test);
	public function onTestPass($file, $test);

	public function onTestOutput($output);
}

?>