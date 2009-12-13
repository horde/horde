<?php

class UsersHaveLastNames3 extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->addColumn('users', 'last_name', 'string');
    }

    public function down()
    {
        $this->removeColumn('users', 'last_name');
    }
}