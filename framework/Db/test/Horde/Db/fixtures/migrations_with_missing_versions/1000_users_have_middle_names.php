<?php

class UsersHaveMiddleNames extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->addColumn('users', 'middle_name', 'string');
    }

    public function down()
    {
        $this->removeColumn('users', 'middle_name');
    }
}