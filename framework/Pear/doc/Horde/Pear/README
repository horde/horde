==========
 Overview
==========

This component delivers a small subset of the functionality required to deal with the data structures established by the `PEAR project`_. Currently it allows accessing the REST interface of a remote PEAR server and provides utilities to deal with package.xml files. The most important functionality concerning the packaging manifest is the automatic update of the XML data.

.. _`PEAR project`: http://pear.php.net

Further details can be found in the accompanying documents `REMOTE_PEAR_SERVER`_ and `PACKAGE_XML`_.

.. _`REMOTE_PEAR_SERVER`: REMOTE_PEAR_SERVER
.. _`PACKAGE_XML`: PACKAGE_XML

==============================
 Rationale for this component
==============================

This package reimplements some of the code that is being provided on `pear.php.net`_ via various PEAR_* packages.

.. _`pear.php.net`: http://pear.php.net

Of course it should not be necessary to write new code providing PEAR functionality given the available PEAR_* packages. Unfortunately none of these provide anything near a decent and usable developer API. One could hope for the newer `Pyrus`_ code to resolve some of the deeper structural problems the PEAR code has. The currently available code (as of 2011) does not look like a major improvement though.

.. _`Pyrus`: http://pear2.php.net/

The decision to reimplement PEAR code has not been taken lightly: originally the Horde components helper had been using the PEAR code. But the amount of hacks and workarounds required to sustain that basis was too much for a long term solution. As a consequence Horde_Pear was started in order to generate a backend providing the functionality specifically required by the Horde components helper.

