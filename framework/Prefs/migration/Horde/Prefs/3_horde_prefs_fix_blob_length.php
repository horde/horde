<?php
class HordePrefsFixBlobLength extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->changeColumn('horde_prefs', 'pref_value', 'binary');
    }

    public function down()
    {
    }
}
