====================
 Horde/Db Test Suite
====================

:Last update:   2009-01-04
:Authors:       Chuck Hagenbuch
:Contact:       dev@lists.horde.org

.. contents:: Contents
.. section-numbering::

Defining adapters
=================

As long as PHP has the PDO SQLite driver (which is enabled by default), the
SQLite tests will always be run. This is possible using the sqlite:memory
database; no file access or permissions are required.

For the other adapters, the Horde_Db test suite looks for environment variables
named DB_ADAPTER_$driverName_TEST_CONFIG. For the MySQLi driver, that would be
DB_ADAPTER_MYSQLI_TEST_CONFIG. For the PDO PostgreSQL driver, that would be
DB_ADAPTER_PDO_PGSQL_TEST_CONFIG, and so on. The value of the environment variable is
a JSON string with the configuration array for the adapter. Here is an example
for setting up a test DSN for the MySQL test database on localhost, connecting
as the user horde_db with no password:

{"username":"horde_db","dbname":"test","host":"localhost"}

When running the test suite, any adapter for which a DSN is not found, or for
which connecting to the defined DSN fails, a single instance of
Horde_Db_Adapter_MissingTest will be included in the test suite run, with
details on why the adapter was skipped.
