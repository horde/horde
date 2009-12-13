<?php

class GiveMeBigNumbers extends Horde_Db_Migration_Base
{
    public function up()
    {
        $table = $this->createTable('big_numbers');
            $table->column('bank_balance',        'decimal', array('precision' => 10, 'scale' => 2));
            $table->column('big_bank_balance',    'decimal', array('precision' => 15, 'scale' => 2));
            $table->column('world_population',    'decimal', array('precision' => 10));
            $table->column('my_house_population', 'decimal', array('precision' => 2));
            $table->column('value_of_e',          'decimal');
        $table->end();
    }

    public function down()
    {
        $this->dropTable('big_numbers');
    }
}