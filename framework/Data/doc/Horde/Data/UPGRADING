======================
 Upgrading Horde_Data
======================

:Contact: dev@lists.horde.org

.. contents:: Contents
.. section-numbering::


This lists the API changes between releases of the package.


Upgrading to 2.0
================

  - Horde_Data

    The factory method has been removed. Directly instantiate a transport
    driver instead.

  - Horde_Data_Base

    The constructor now requires a Horde_Data_Storage object to be passed in
    as the first argument.

  - Horde_Data_Csv

    The static helper method getCsv() was added.
    Removed the default charset member variable. The output charset is now
    always UTF-8.
    nextStep() now supports the 'check_charset' parameter, which throws a
    Horde_Data_Exception_Charset exception if the charset does not match up
    with the data.

  - Horde_Data_Storage

    Temporary data storage has been abstracted out to this class.
