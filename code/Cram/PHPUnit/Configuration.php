<?php

class PHPUnit_Configuration
{
	protected $file, $path;

	/**
	 * @var DOMDocument
	 */
	protected $doc;

	/**
	 * @var DOMElement
	 */
	protected $root;

	/**
	 * @var DOMElement
	 */
	protected $suite = array();

	/**
	 * @var DOMElement
	 */
	protected $logging;

	public function __construct($file, $base_path = NULL)
	{
		if (!is_file($file)
			|| !is_readable($file))
		{
			throw new InvalidArgumentException('File must be readable');
		}

		$this->file = $file;
		$this->path = ($base_path ? $base_path : dirname($file));

		$this->doc = new DOMDocument();
		$this->doc->load($this->file);
		$this->load();
	}

	public function __clone()
	{
		$this->doc = clone $this->doc;
		$this->root = NULL;
		$this->suite = array();
		$this->logging = NULL;

		$this->load();
	}

	public function reload()
	{
		$doc = new DOMDocument();
		$doc->load($this->file);

		$this->doc = $doc;
		$this->root = NULL;
		$this->suite = array();
		$this->logging = NULL;

		$this->load();
	}

	public function getFilename()
	{
		return $this->file;
	}

	public function getBootstrapFile()
	{
		if ($this->root->hasAttribute('bootstrap'))
		{
			return $this->root->getAttribute('bootstrap');
		}
		return FALSE;
	}

	/**
	 * @return PHPUnit_FilterConfiguration
	 */
	public function getFilterConfig()
	{
		$x = new DOMXPath($this->doc);

		$whitelist = $x->query('filter/whitelist');
		if ($whitelist->length > 0)
		{
			return new PHPUnit_FilterConfiguration($this->doc, $whitelist->item(0));
		}

		$i = $x->query('filter/blacklist');
		if ($blacklist->length > 0)
		{
			return new PHPUnit_FilterConfiguration($this->doc, $blacklist->item(0));
		}

		return FALSE;
	}

	public function clearTests()
	{
		foreach ($this->suite as $el)
		{
			$this->root->removeChild($el);
		}
		$this->suite = array();
	}

	public function clearLogging()
	{
		$this->clearChildren($this->logging);
	}

	/**
	 * @return TestRegistry
	 */
	public function getTests()
	{
		$x = new DOMXPath($this->doc);
		$reg = new TestRegistry();

		foreach ($x->query('testsuite/directory') as $item)
		{
			/* @var $item DomElement */
			$path = $this->getPath($item->nodeValue);
			$suffix = $item->hasAttribute('suffix')
				? (string)$item->getAttribute('suffix')
				: 'Test.php';
			$reg->addDirectory($path, $suffix);
		}

		foreach ($x->query('testsuite/file') as $item)
		{
			/* @var $item DomElement */
			$path = $this->getPath($item->nodeValue);
			$reg->addFile($path);
		}

		return $reg;
	}

	public function addTestFile($file)
	{
		if (empty($this->suite))
		{
			$this->suite[0] = $this->doc->createElement('testsuite');
			$this->root->appendChild($this->suite[0]);
		}

		$node = $this->doc->createElement('file', $file);
		$this->suite[0]->appendChild($node);
	}

	public function addTestFiles(array $files)
	{
		foreach ($files as $f) $this->addTestFile($f);
	}

	public function addLog($type, $target, array $settings = array())
	{
		if (!$this->logging)
		{
			$this->logging = $this->doc->createElement('logging');
			$this->root->appendChild($this->logging);
		}

		$node = $this->doc->createElement('log');
		$node->setAttribute('type', $type);
		$node->setAttribute('target', $target);

		foreach ($settings as $name=>$value)
		{
			$node->setAttribute($name, $value);
		}

		$this->logging->appendChild($node);
	}

	public function save($file)
	{
		$this->doc->save($file);
	}

	public function __toString()
	{
		return $this->doc->saveXML();
	}

	protected function getPath($path)
	{
		if ($path[0] !== DIRECTORY_SEPARATOR)
		{
			$path = $this->path.DIRECTORY_SEPARATOR.$path;
		}
		return $path;
	}

	protected function load()
	{
		$this->root = $this->doc->childNodes->item(0);
		if ($this->root->localName !== 'phpunit')
		{
			throw new Exception('Invalid configuration file');
		}

		foreach ($this->root->childNodes as $child)
		{
			switch ($child->localName)
			{
				case 'testsuite':
					$this->suite[] = $child;
					break;
				case 'logging':
					$this->logging = $child;
					break;
			}
		}
	}

	protected function clearChildren(DOMNode $n)
	{
		while ($n->childNodes->length)
		{
			$n->removeChild($n->firstChild);
		}
	}
}

?>
