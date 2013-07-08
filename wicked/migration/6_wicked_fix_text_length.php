<?php
class WickedFixTextLength extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->changeColumn('wicked_pages', 'page_text', 'longtext');
        $this->changeColumn('wicked_history', 'page_text', 'longtext');
    }

    public function down()
    {
    }
}
