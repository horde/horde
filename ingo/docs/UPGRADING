================
 Upgrading Ingo
================

:Contact: ingo@lists.horde.org

.. contents:: Contents
.. section-numbering::


General instructions
====================

These are instructions to upgrade from earlier Ingo versions. Please backup
your existing data before running any of the steps described below. You can't
use the updated data with your old Ingo version anymore.

Upgrading Ingo is as easy as running::

   pear upgrade -a -B horde/ingo

If you want to upgrade Ingo with all binary dependencies, you need to remove
the ``-B`` flag. Please note that this might also try to install PHP extensions
through PECL that might need further configuration or activation in your PHP
configuration::

   pear upgrade -a horde/ingo

If you want to upgrade to an alpha or beta version of Ingo, you need to tell
the PEAR installer to prefer non-stable package versions. Please note that this
might also install pre-release 3rd-party PEAR packages::

   pear -d preferred_state=alpha upgrade -a horde/ingo

If you want to upgrade from an Ingo version prior to 2.0, please follow the
instructions in INSTALL_ to install the most recent Ingo version using the PEAR
installer.

After updating to a newer Ingo version, you **always** need to update
configurations and database schemes. Log in as an administrator, go to
Administration => Configuration and update anything that's highlighted as
outdated.


Upgrading Ingo From 3.x To 4.0
==============================

Configuration Options (conf.php)
--------------------------------

Added a NoSQL (MongoDB) driver for the storage backend.


Backend Configuration (backends.php)
------------------------------------

The Sieve driver now uses the 'date' and 'relational' extensions for timed
vacation messages by default. If using a Sieve version that doesn't support
those extensions, set 'date' to false in the sieve script parameters to use the
older regular expression parsing (see backends.php).


Upgrading Ingo From 3.1.x To 3.2
================================

API Changes
-----------

Added the 'newEmailFilter' API link.  Takes one argument: 'email', the
e-mail address to pre-populate into a new rule.


Backend Configuration (backends.php)
------------------------------------

The Sieve driver now uses the 'enotify' extension by default. If using an
old version of Sieve that only supports the deprecated 'notify' setting, set
'notify' to true in the sieve script parameters (see backends.php).

The Sieve driver now uses 'imap4flags' by default to set flags. If using an
old version of Sieve that only supports the deprecated 'imapflags' setting,
set 'imapflags' to true in the sieve script parameters (see backends.php).


Configuration Options (conf.php)
--------------------------------

The following options have been removed (see Permissions section for
replacement functionality)::

   $conf['storage']['maxblacklist']
   $conf['storage']['maxwhitelist']


Permissions
-----------

Ingo permissions are now applied per backend. To upgrade existing permissions,
an admin needs to run the following script::

   bin/ingo-admin-upgrade --task=backend_perms

The following permissions have been added::

   max_blacklist
   max_forward
   max_whitelist

The following permissions have been removed::

   allow_rules (replacement: set max_rules permission to 0)



Upgrading Ingo From 3.0.x To 3.1.x
==================================

Backend Configuration (backends.php)
------------------------------------

The 'script' and 'transport' settings of the backend configuration have been
changed from strings to arrays, to allow different backends for different
filter rules.


API Changes
-----------

The applyFilters() no longer returns a value.


Upgrading Ingo From 2.x To 3.x
==============================

Backend Configuration (backends.php)
------------------------------------

The 'hordeauth' parameter and the 'password' and 'username' parameters have
been removed. By default, the transport backend will use Horde authentication
credentials to access. To set a different username and/or password, you should
use the 'transport_auth' hook.



Upgrading Ingo From 1.x To 2.x
==============================

Configuration Options (conf.php)
--------------------------------

The following configuration options have been removed:

   usefolderapi


Sieve Backend
-------------

The port number for the timesieved daemon has been changed to the official
Sieve port 4190 in the default configuration. If your timesieved daemon is
still running on the former default port 2000, or any other port than 4190, you
need to create a ``config/backends.local.php`` file with the following
content::

   <?php
   $backends['sieve']['params']['port'] = 2000;
   $backends['sivtest']['params']['port'] = 2000;



Upgrading Ingo From 1.2.1 To 1.2.2
==================================

The group_uid field in the SQL share driver groups table has been changed from
an INT to a VARCHAR(255). Execute the provided SQL script to update your
database if you are using the native SQL share driver.

   mysql --user=root --password=<MySQL-root-password> <db name> < 1.2.1_to_1.2.2.sql


Upgrading Ingo From 1.2 To 1.2.1
================================

The share_owner field in the SQL share driver table has been changed from a
VARCHAR(32) to a VARCHAR(255). Execute the provided SQL script to update your
database if you are using the native SQL share driver.

   mysql --user=root --password=<MySQL-root-password>  <db name> < 1.2_to_1.2.1.sql


Upgrading Ingo From 1.1.x To 1.2
==================================


This is a non-exhaustive, quick explanation of what has changed between Ingo
version 1.1.x and 1.2.x.


SQL Backend
-----------

An SQL table has been added than can optionally be used as a storage backend
for the filter rules. Using this backend no longer limits the number and size
of rules.

Execute the provided SQL script to add the table to your database, e.g.::

   mysql --user=root --password=<MySQL-root-password> <db name> < scripts/sql/ingo.sql

You also have to execute the provided PHP script to migrate the existing rules
from the preferences backend to the new database table::

   ingo-convert-prefs-to-sql < filename

``filename`` is a file that contains a list of users, one username per line.
The username should be the same as how the preferences are stored in the
preferences backend (e.g. usernames may have to be in the form
user@example.com). You can create such a list with the following MySQL
command::

   mysql --user=root --password=<MySQL-root-password> --skip-column-names --batch --execute='SELECT DISTINCT pref_uid FROM horde_prefs' <db name>


New Beta SQL Share Driver Support
---------------------------------

A new beta-level SQL Horde_Share driver has been added in Horde 3.2. This
driver offers significant performance improvements over the existing Datatree
driver, but it has not received the same level of testing, thus the beta
designation.  In order to make use of this driver, you must be using Horde
3.2-RC3 or later. To migrate your existing share data, run
``ingo-convert-datatree-shares-to-sql``.  Be sure to read the entry above and
create the new SQL tables before running the migration script.


Upgrading Ingo From 1.0.x To 1.1.x
==================================

This is a non-exhaustive, quick explanation of what has changed between Ingo
version 1.0.x and 1.1.x.


Backends parameter changes - procmail driver
--------------------------------------------

In ``config/backends.php``, the ``procmailrc`` parameter in the procmail
entry has been deprecated.  It has been replaced by the ``filename``
parameter.


.. _INSTALL: INSTALL
