Cram uses PHPUnit test coverage information to monitor project files and selectively run test cases in response to
file modifications. Additionally, Cram can use desktop notifications from libnotify to alert you to test failures.

Running Cram
--------------------

Cram takes PHPUnit XML configuration files as parameters. At start up, it executes all the tests specified
in each configuration file and then uses the PHPUnit coverage report to determine which source files are
covered by each test file.

To start Cram, just invoke it with your PHPUnit configuration file:

	php ./bin/monitor.php ~/myproject/tests/config.xml


