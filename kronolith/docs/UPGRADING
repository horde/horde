=====================
 Upgrading Kronolith
=====================

:Contact: kronolith@lists.horde.org

.. contents:: Contents
.. section-numbering::


General instructions
====================

These are instructions to upgrade from earlier Kronolith versions. Please
backup your existing data before running any of the steps described below. You
can't use the updated data with your old Kronolith version anymore.

Upgrading Kronolith is as easy as running::

   pear upgrade -a -B horde/kronolith

If you want to upgrade Kronolith with all binary dependencies, you need to
remove the ``-B`` flag. Please note that this might also try to install PHP
extensions through PECL that might need further configuration or activation in
your PHP configuration::

   pear upgrade -a horde/kronolith

If you want to upgrade to an alpha or beta version of Kronolith, you need to
tell the PEAR installer to prefer non-stable package versions. Please note that
this might also install pre-release 3rd-party PEAR packages::

   pear -d preferred_state=alpha upgrade -a horde/kronolith

If you want to upgrade from a Kronolith version prior to 3.0, please follow the
instructions in INSTALL_ to install the most recent Kronolith version using the
PEAR installer.

After updating to a newer Kronolith version, you **always** need to update
configurations and database schemes. Log in as an administrator, go to
Administration => Configuration and update anything that's highlighted as
outdated.


Upgrading Kronolith from 4.x to 5.x
===================================


Configuration
-------------

The 'maps' configuration options have been moved to the ``horde`` application
configuration.

The 'table' configuration options have been removed, the database tables have
fixed names now.

Resources are now share-based and no longer require an explicit driver
selection. The configuration options have been changed to reflect this.


Upgrading Kronolith from 2.3.x to 3.x
=====================================


Preferences
-----------

The last_kronolith_maintenance preference has been removed.


UTC timestamps
--------------

Kronolith 3.0 using an SQL backend stores all event dates in UTC by
default. This allows to share events across different timezones. If you are
upgrading from earlier Kronolith versions, you can either turn this feature
off and keep working with your existing event data, or convert the existing
data to the UTC timezone.

If you want to convert the data, make sure that you have run all other upgrade
scripts first, so that your database tables are up to date. Then, to convert
the event times, execute the provided PHP script::

   kronolith-convert-to-utc


Daily agendas
-------------

There is a new script in ``kronolith-agenda`` to send out daily agendas to all
users. It should be run once a day. This replaces the ``scripts/reminders.php``
script from older versions, but you have to take care yourself now that the
script isn't run more than once per day.


Upgrading Kronolith from 2.3 to 2.3.x
=====================================

Kronolith requires at least version 0.21.0 of Date_Holidays now, which has
much better support for translations.


Upgrading Kronolith from 2.2 to 2.3.x
=====================================

Some fields in the SQL share driver tables have been changed. Execute the
provided SQL script to update your database if you are using the native SQL
share driver.

   mysql --user=root --password=<MySQL-root-password>  <db name> < scripts/upgrades/2.2_to_2.3.sql


Upgrading Kronolith from 2.1.x to 2.2.x
=======================================


SQL Backends
------------

Two new fields have been added to the default SQL table layout.

Execute the provided SQL script to update your data to the new Kronolith
version, e.g.::

   mysql --user=root --password=<MySQL-root-password> <db name> < scripts/upgrades/2.1_to_2.2.sql


New Beta SQL Share Driver Support
---------------------------------

A new beta-level SQL Horde_Share driver has been added in Horde 3.2. This driver
offers significant performance improvements over the existing Datatree driver,
but it has not received the same level of testing, thus the beta designation.
In order to make use of this driver, you must be using Horde 3.2-RC3 or
later. The new tables needed for this driver already should have been created
by the step above.

If you want to use the new SQL Share driver, you must also execute the
provided PHP script to migrate your existing share data to the new format::

   kronolith-convert-datatree-shares-to-sql


Preferences
-----------

The preference that stores the address books that are searched for attendees
with free/busy urls has changed both the name and the format. The preference
used to be called "search_abook" and contained a serialized PHP array. The new
preference is called "search_sources", contains a tab-separated list, and goes
along with the "search_fields" preference.


.. _INSTALL: INSTALL
