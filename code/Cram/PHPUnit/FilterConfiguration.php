<?php

class PHPUnit_FilterConfiguration
{
	const BLACKLIST = 1;
	const WHITELIST = 2;

	/**
	 * @var DOMElement
	 */
	protected $root;

	/**
	 * @var DOMDocument
	 */
	protected $doc;

	protected $exclude;

	/**
	 * @var DOMXPath
	 */
	protected $xpath;

	protected $mode = self::BLACKLIST;

	public function __construct(DOMDocument $doc, DOMElement $root)
	{
		switch ($root->localName)
		{
			case 'blacklist':
				$this->mode = self::BLACKLIST;
				break;
			case 'whitelist':
				$this->mode = self::WHITELIST;
				break;
			default:
				throw new Exception('Invalid root node, must be a blacklist or whitelist');
		}

		$this->doc = $doc;
		$this->root = $root;
		$this->xpath = new DOMXPath($doc);
	}

	public function excludeFile($filename)
	{
		$el = $this->createFile($filename);
		$this->appendExclude($el);
	}

	public function excludeDirectory($path, $suffix = '')
	{
		$el = $this->createDirectory($path, $suffix);
		$this->appendExclude($el);
	}

	public function includeFile($filename)
	{
		$el = $this->createFile($filename);
		$this->addInclude($el);
	}

	public function includeDirectory($path, $suffix = '')
	{
		$el = $this->createDirectory($path, $suffix);
		$this->appendInclude($el);
	}

	protected function getExclude()
	{
		if (!$this->exclude)
		{
			$i = $this->xpath->query('exclude', $this->root);
			if ($i->length > 0)
			{
				$this->exclude = $i->item(0);
			}
			else
			{
				$this->exclude = $this->doc->createElement('exclude');
				$this->root->appendChild($this->exclude);
			}
		}
		return $this->exclude;
	}

	protected function createFile($filename)
	{
		$el = $this->doc->createElement('file');
		$el->nodeValue = $filename;

		return $el;
	}

	protected function createDirectory($path, $suffix)
	{
		$el = $this->doc->createElement('directory');
		$el->nodeValue = $path;

		if ($suffix != '')
		{
			$el->setAttribute('suffix', $suffix);
		}

		return $el;
	}

	protected function appendExclude(DOMElement $el)
	{
		if ($this->mode == self::BLACKLIST)
		{
			// if we're using a whitelist, then not having a file show in
			// coverage (excluding it) means INCLUDING it in the blacklist
			$this->root->appendChild($el);
		}
		else
		{
			$exclude = $this->getExclude();
			$exclude->appendChild($el);
		}
	}

	protected function appendInclude(DOMElement $el)
	{
		if ($this->mode == self::BLACKLIST)
		{
			// if we're using a blacklist, then having a file show up in
			// coverage (including it) means EXCLUDING it from the blacklist
			$exclude = $this->getExclude();
			$exclude->appendChild($el);
		}
		else
		{
			$this->root->appendChild($el);
		}
	}
}

?>