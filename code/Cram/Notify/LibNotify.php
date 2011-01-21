<?php

class Notify_LibNotify implements Notifier
{
	protected $bin;

	public function __construct($bin = '/usr/bin/notify-send')
	{
		$this->bin = $bin;
	}

	public function notify($summary, $body, $type)
	{
		switch ($type)
		{
			case self::TYPE_FAIL:
			case self::TYPE_ERROR:
				$urgency = 'critical';
				$icon = 'dialog-warning';
				break;

			case self::TYPE_EXCEPTION:
				$urgency = 'critical';
				$icon = 'error';
				break;

			default:
				$urgency = 'normal';
				$icon = 'info';
		}
		$this->sendNotification($summary, $body, $urgency, $icon);
	}

	public function sendNotification($summary, $body = '', $urgency = 'normal', $icon = '')
	{
		echo "  {$summary}: {$body}\n";

		$cmd = $this->bin.' -u '.escapeshellarg($urgency);
		if ($icon) $cmd .= ' -i '.escapeshellarg($icon);
		$cmd .= ' '.escapeshellarg($summary);
		if ($body) $cmd .= ' '.escapeshellarg($body);

		`$cmd`;
	}
}

?>