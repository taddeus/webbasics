WebBasics
=========

Summary
-------
WebBasics is a set of classes that provides the minimal functionalities of a
website. The core exists of a class autoloader, a template parser, a logger
and some array manipulation functions. No MVC 'model' implementation is
included, there are already many of these out there (PHPActiveRecord is
recommended).

Unit tests
----------
Unit tests are located in the 'tests/' directory. PHPUnit is used to run
tests. The PHP extension Xdebug needs to be installed in order to generate a
code coverage report. To run all unit tests, simply run 'phpunit' in the root
directory.

Documentation
-------------
PhpDocumentor can be used to generate documentation in the 'build/docs/'
directory. Just run 'phpdoc' in the root directory.

A build of the documentation is available [here](http://tkroes.nl/docs/webbasics/).