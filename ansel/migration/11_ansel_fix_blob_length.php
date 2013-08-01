<?php
class AnselFixBlobLength extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->changeColumn('ansel_faces', 'face_signature', 'binary');
        $this->changeColumn('ansel_faces_index', 'index_part', 'binary');
    }

    public function down()
    {
    }
}
