<?php

class PHPUnit_CoverageDatabase
{
	/**
	 * @var PDO
	 */
	protected $db;

	/**
	 * @var int
	 */
	protected $revision;

	/**
	 * @var string
	 */
	protected $dsn;

	public function __construct(PDO $db, $dsn, $revision = 1)
	{
		$this->db = $db;
		$this->dsn = $dsn;
		$this->revision = $revision;
	}

	public function getDSN()
	{
		return $this->dsn;
	}

	public function getRevision()
	{
		return $this->revision;
	}

	public function setRevision($revision)
	{
		$this->revision = $revision;
	}

	public function getMaxRevision()
	{
		$q = "
			SELECT MAX(revision) AS max
			FROM run
		";
		$st = $this->db->query($q);
		return $st->fetch(PDO::FETCH_COLUMN);
	}

	public function getFileTests()
	{
		$run_id = $this->findRun($this->revision);

		$q = "
			SELECT DISTINCT file.code_full_file_name AS file,
				test_file.code_full_file_name AS test
			FROM test
				JOIN code_method m ON (m.code_method_id = test.code_method_id)
				JOIN code_class c ON (c.code_class_id = m.code_class_id)
				JOIN code_file test_file ON (test_file.code_file_id = c.code_file_id)
				JOIN code_coverage cov ON (cov.test_id = test.test_id)
				JOIN code_line l ON (l.code_line_id = cov.code_line_id)
				JOIN code_file file ON (file.code_file_id = l.code_file_id)
			WHERE test.run_id = {$run_id}
		";
		$st = $this->db->query($q);
		$st->setFetchMode(PDO::FETCH_ASSOC);

		$files = array();

		foreach ($st as $row)
		{
			if ($row['file'] == $row['test']) continue;

			(isset($files[$row['file']]))
				? $files[$row['file']][] = $row['test']
				: $files[$row['file']] = array($row['test']);
		}

		return $files;
	}

	protected function findRun($revision)
	{
		$q = "
			SELECT MAX(run_id)
			FROM run
			WHERE revision = {$revision}
		";
		$st = $this->db->query($q);
		return $st->fetchColumn(0);
	}
}

?>