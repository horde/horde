====================
 Upgrading Horde_Db
====================

:Contact: dev@lists.horde.org

.. contents:: Contents
.. section-numbering::


This lists the API changes between releases of the package.


Upgrading to 2.1.0
==================

  - Horde_Db_Adapter_Base

    - The execute(), select(), insert(), beginDbTransaction(),
      commitDbTransaction(), and rollbackDbTransaction() methods are abstract
      now.

    - The execute() method has been deprecated for external usage. Use the
      select() method instead or one of the other query methods.

    - The select() method always returns a Horde_Db_Adapter_Base_Result
      sub-class now.

    - The addIndex() method returns the used index name now.

    - The writeCache(), readCache(), insertBlob(), and column() methods have
      been added.


Upgrading to 2.2.0
==================

  - Horde_Db_Adapter_Base

    - The updateBlob() method has been added.

  - Horde_Db_Value_Text has been added
