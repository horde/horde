<?php

class UsersHaveLastNames2 extends Mad_Model_Migration_Base 
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