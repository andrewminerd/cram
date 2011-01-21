<?php

@dl('php_gtk2.so');

$w = new GtkWindow();
$w->connect_simple('destroy', array('Gtk', 'main_quit'));

class GtkGraph
{
	/**
	 * @var GtkDrawingArea
	 */
	protected $area;

	protected $points;

	public function __construct($points = 60)
	{
		$this->area = new GtkDrawingArea();
		$this->area->connect_simple('realize', array($this, 'onRealize'));
		$this->area->connect_simple('configure-event', array($this, 'onResize'));
		$this->area->connect_simple('expose-event', array($this, 'onExpose'));

		$this->points = array_fill(0, $points, 0);
	}

	public function addTo(GtkContainer $c)
	{
		$c->add($this->area);
	}

	public function addPoint($value)
	{
		array_shift($this->points);
		$this->points[] = $value;

		$this->refresh();
	}

	public function onRealize()
	{
	}

	public function onResize()
	{
		//var_dump($this->area->allocation);
	}

	public function onExpose()
	{
		$this->refresh();
	}

	protected function refresh()
	{
		$rect = $this->area->allocation;
		$max = (max($this->points) / .8);
		$count = count($this->points);

		$border_x = 20;
		$border_y = 20;

		$inner_w = ($rect->width - ($border_x * 2));
		$inner_h = ($rect->height - ($border_y * 2));
		$pw = floor($inner_w / $count);
		$ph = floor($inner_h / $max);

		$border_x = (($rect->width - ($pw * $count)) / 2);
		$border_y = (($rect->height - ($ph * $max)) / 2);

		$this->drawGrid($rect, $border_x, $border_y);

		$last = $this->points[0];

		for ($i = 1; $i < $count; $i++)
		{
			$x1 = $i - 1;
			$y1 = $last;
			$y2 = $this->points[$i];

			$this->area->window->draw_line(
				$this->area->style->white_gc,
				(($x1 * $pw) + $border_x),
				($inner_h - ($y1 * $ph) + $border_y),
				(($i * $pw) + $border_x),
				($inner_h - ($y2 * $ph) + $border_y)
			);

			$last = $y2;
		}
	}

	protected function drawGrid($rect, $border_x, $border_y)
	{
		$this->area->window->draw_rectangle(
			$this->area->style->black_gc,
			TRUE,
			$rect->x,
			$rect->y,
			$rect->width,
			$rect->height
		);

		$this->area->window->draw_rectangle(
			$this->area->style->white_gc,
			FALSE,
			$border_x,
			$border_y,
			($rect->width - $border_x * 2),
			($rect->height - $border_y * 2)
		);
	}
}

class Load
{
	protected $graph;

	public function __construct(GtkGraph $graph)
	{
		$this->graph = $graph;
	}

	public function update()
	{
		$load = explode(' ', file_get_contents('/proc/loadavg'));
		$this->graph->addPoint($load[0]);

		return TRUE;
	}
}

$draw = new GtkDrawingArea();
$graph = new GtkGraph();
$graph->addTo($w);

$load = new Load($graph);

$w->show_all();

Gtk::timeout_add(5000, array($load, 'update'));
Gtk::main();

?>