=================
 Upgrading Ansel
=================

:Contact: horde@lists.horde.org

.. contents:: Contents
.. section-numbering::


General instructions
====================

These are instructions to upgrade from earlier Ansel versions. Please backup
your existing data before running any of the steps described below. You can't
use the updated data with your old Ansel version anymore.

Upgrading Ansel is as easy as running::

   pear upgrade -a -B horde/ansel

If you want to upgrade Ansel with all binary dependencies, you need to remove
the ``-B`` flag. Please note that this might also try to install PHP extensions
through PECL that might need further configuration or activation in your PHP
configuration::

   pear upgrade -a horde/ansel

If you want to upgrade to an alpha or beta version of Ansel, you need to tell
the PEAR installer to prefer non-stable package versions. Please note that this
might also install pre-release 3rd-party PEAR packages::

   pear -d preferred_state=alpha upgrade -a horde/ansel

If you want to upgrade from a Ansel version prior to 2.0, please follow the
instructions in INSTALL_ to install the most recent Ansel version using the
PEAR installer.

After updating to a newer Ansel version, you **always** need to update
configurations and database schemes. Log in as an administrator, go to
Administration => Configuration and update anything that's highlighted as
outdated.


Upgrading Ansel from 3.x to 4.x
===============================


Configuration
-------------

The 'maps' configuration options have been moved to the ``horde`` application
configuration.



.. _INSTALL: INSTALL
