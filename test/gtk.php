<?php

@dl('php_gtk2.so');

class Coverage
{
	protected $window;
	protected $list;
	protected $store;

	public function __construct()
	{
		$this->window = new GtkWindow();
		$this->store = new GtkListStore(
			GObject::TYPE_LONG,    // line number
			GObject::TYPE_STRING, // line contents
			GObject::TYPE_STRING  // background color
		);

		$render = new GtkCellRendererText();
		$render->set_property('font', 'Courier New 10');
		$col = new GtkTreeViewColumn('', $render, 'text', 1, 'cell-background', 2);
		//$col->set_attributes($render, 'cell-background', 1);

		$this->list = new GtkTreeView($this->store);
		$this->list->append_column(new GtkTreeViewColumn('', new GtkCellRendererText(), 'text', 0));
		$this->list->append_column($col);

		$scroll = new GtkScrolledWindow();
		$scroll->add($this->list);
		$this->window->add($scroll);
	}

	public function show()
	{
		$this->window->show_all();
		$this->window->connect_simple('destroy', array('gtk', 'main_quit'));
	}

	public function showCoverage(array $lines, array $covered)
	{
		foreach ($lines as $i=>$line)
		{
			// dead: -2
			// executable: -1
			// executed: 1
			switch ($covered[$i])
			{
				case 1:
					$color = '#ccffcc';
					break;
				case -2:
					$color = '#cccccc';
					break;
				case -1:
					$color = '#ffcccc';
					break;
				default:
					$color = '#ffffff';
			}
			$this->store->append(array($i, rtrim($line), $color));
		}
	}
}

function loadCoverage($dsn, $rev, $file)
{
	$db = new PDO($dsn);
	$st = $db->prepare("
		SELECT code_line,
			code_line_covered
		FROM code_file f
			JOIN code_line l ON (l.code_file_id = f.code_file_id)
		WHERE f.code_full_file_name = ?
			AND f.revision = ?
	");

	$st->execute(array($file, $rev));

	$lines = array();
	$cov = array();

	foreach ($st as $row)
	{
		$lines[] = rtrim($row[0]);
		$cov[] = $row[1];
	}
	return array($lines, $cov);
}


list($lines, $cov) = loadCoverage(
	'sqlite:/virtualhosts/projects/ecash_api/vendor_api_commercial/tests/test.db',
	56,
	//'/virtualhosts/projects/ecash_api/vendor_api_commercial/code/ECash/VendorAPI/LoanAmountCalculator.php'
	'/virtualhosts/projects/ecash_api/vendor_api_commercial/code/ECash/VendorAPI/Actions/Qualify.php'
);

$c = new Coverage();
$c->show();
$c->showCoverage($lines, $cov);
Gtk::main();
die();

class Main extends GtkWindow
{
	protected $_window;
	protected $_list;
	protected $_store;
	protected $_text;

	public function __construct()
	{
		parent::__construct();

		$this->_store = new GtkTreeStore();
		$this->_list = new GtkTreeView($this->_store);
		$this->_text = new GtkTextView();

		$vpane = new GtkVPaned();
		$vpane->add1($this->_list);
		$vpane->add2($this->_text);
		$this->add($vpane);
	}
}

$w = new Main();
$w->show_all();
$w->connect_simple('destroy', array('gtk', 'main_quit'));

Gtk::main();

?>