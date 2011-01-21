<?php

define('ROOT', realpath(dirname(__FILE__).'/../'));

require ROOT.'/code/Cram/FileTestRegistry.php';
require ROOT.'/code/Cram/FileWatcher.php';
require ROOT.'/code/Cram/TestWatcher.php';
require ROOT.'/code/Cram/FileDelta.php';
require ROOT.'/code/Cram/FileMonitor.php';
require ROOT.'/code/Cram/PHPUnit/TestResult.php';
require ROOT.'/code/Cram/PHPUnit/Configuration.php';
require ROOT.'/code/Cram/PHPUnit/FilterConfiguration.php';
require ROOT.'/code/Cram/PHPUnit/CoverageXML.php';
require ROOT.'/code/Cram/Process.php';
require ROOT.'/code/Cram/Project.php';
require ROOT.'/code/Cram/RunTests.php';
require ROOT.'/code/Cram/TestNotifier.php';
require ROOT.'/code/Cram/Notifier.php';
require ROOT.'/code/Cram/Notify/LibNotify.php';
require ROOT.'/code/Cram.php';
//require ROOT.'/test/poll.php';

$bin = array_shift($argv);
if ($argc < 2)
{
	die("Usage: {$bin} config-file [config-file ...]\n");
}

declare(ticks = 1);

$cram = new Cram();
pcntl_signal(SIGINT, array($cram, 'onInterrupt'));
$cram->main($argv);

?>