<?php
/**
 * Move tags from ansel to content storage.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Ansel
 */
class AnselUpgradeTagsToContent extends Horde_Db_Migration_Base
{
    public function up()
    {
        $tableList = $this->tables();
        if (in_array('ansel_galleries_tags', $tableList)) {
            $GLOBALS['registry']->pushApp('ansel');

            /* Gallery tags */
            $sql = 'SELECT gallery_id, tag_name, share_owner FROM ansel_shares RIGHT JOIN '
                . 'ansel_galleries_tags ON ansel_shares.share_id = ansel_galleries_tags.gallery_id '
                . 'LEFT JOIN ansel_tags ON ansel_tags.tag_id = ansel_galleries_tags.tag_id;';

            // Maybe iterate over results and aggregate them by user and gallery so we can
            // tag all tags for a single gallery at once. Probably not worth it for a one
            // time upgrade script.
            $this->announce('Migrating gallery tags. This may take a while.');
            $rows = $this->_connection->selectAll($sql);
            foreach ($rows as $row) {
                $GLOBALS['injector']->getInstance('Ansel_Tagger')->tag($row['gallery_id'], $row['tag_name'], $row['share_owner'], 'gallery');
            }
            $this->announce('Gallery tags finished.');
            $sql = 'SELECT ansel_images.image_id iid, tag_name, share_owner FROM ansel_images '
                . 'RIGHT JOIN ansel_images_tags ON ansel_images.image_id = ansel_images_tags.image_id '
                . 'LEFT JOIN ansel_shares ON ansel_shares.share_id = ansel_images.gallery_id '
                . 'LEFT JOIN ansel_tags ON ansel_tags.tag_id = ansel_images_tags.tag_id';
            $this->announce('Migrating image tags. This may take even longer...');
            $rows = $this->_connection->selectAll($sql);
            foreach ($rows as $row) {
                $GLOBALS['injector']->getInstance('Ansel_Tagger')->tag($row['iid'], $row['tag_name'], $row['share_owner'], 'image');
            }
            $this->announce('Image tags finished.');

            $this->announce('Dropping ansel tag tables');
            $this->dropTable('ansel_galleries_tags');
            $this->dropTable('ansel_images_tags');
            $this->dropTable('ansel_tags');
        } else {
            $this->announce('Tags ALREADY migrated to content system.');
        }
    }

    public function down()
    {
        // Not supported. One way upgrade.
    }

}