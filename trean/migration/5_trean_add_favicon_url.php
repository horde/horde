<?php
/**
 * Add favicon_url field.
 *
 * Copyright 2010-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsdl.php BSD
 * @package  Trean
 */
class TreanAddFaviconUrl extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->addColumn('trean_bookmarks', 'favicon_url', 'string', array('limit' => 255));
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->removeColumn('trean_bookmarks', 'favicon_url');
    }
}
