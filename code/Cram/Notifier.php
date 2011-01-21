<?php

interface Notifier
{
	const TYPE_FAIL = 1;
	const TYPE_PASS = 2;
	const TYPE_ERROR = 3;
	const TYPE_EXCEPTION = 4;

	public function notify($summary, $body, $type);
}

?>