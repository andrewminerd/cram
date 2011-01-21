#!/usr/bin/php
<?php

#include ../code/Cram/FileTestRegistry.php
#include ../code/Cram/FileWatcher.php
#include ../code/Cram/TestWatcher.php
#include ../code/Cram/FileMonitor.php
#include ../code/Cram/PHPUnit/TestResult.php
#include ../code/Cram/PHPUnit/Configuration.php
#include ../code/Cram/PHPUnit/FilterConfiguration.php
#include ../code/Cram/PHPUnit/CoverageXML.php
#include ../code/Cram/Process.php
#include ../code/Cram/Project.php
#include ../code/Cram/RunTests.php
#include ../code/Cram/TestNotifier.php
#include ../code/Cram/Notifier.php
#include ../code/Cram/Notify/LibNotify.php
#include ../code/Cram.php

$bin = array_shift($argv);
if ($argc < 2)
{
	die("Usage: {$bin} config-file [config-file ...]\n");
}

declare(ticks = 1)
{
	$cram = new Cram();
	pcntl_signal(SIGINT, array($cram, 'onInterrupt'));
	$cram->main($argv);
}

?>