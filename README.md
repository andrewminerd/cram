Cram uses PHPUnit test coverage information to monitor project files and selectively run test cases in response to
file modifications. Additionally, Cram can use desktop notifications from libnotify to alert you to test failures.

Originally inspired by autotest & c., this provides similar functionality for PHP unit testing:
* Processes code coverage reports to run only those tests covering the modified file (or, if the modified file IS a test, it runs only that test)
* Alerts you to test failures via libnotify (for Linux users)
* Requires no specific configuration (uses PHPUnit XML configuration files), so you're probably ready to go

See an early version [in action](http://www.youtube.com/watch?v=Aq1T1Qm6ZI4)!

Running Cram
--------------------

Cram takes PHPUnit XML configuration files as parameters. At start up, it executes all the tests specified
in each configuration file and then uses the PHPUnit coverage report to determine which source files are
covered by each test file.

To start Cram, just invoke it with your PHPUnit configuration file:

	php ./bin/monitor.php ~/myproject/tests/config.xml


