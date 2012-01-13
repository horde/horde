<?php
/**
 * Move tags from ansel to content storage.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Ansel
 */
class AnselUpgradeTagsToContent extends Horde_Db_Migration_Base
{

    public function __construct(Horde_Db_Adapter $connection, $version = null)
    {
        parent::__construct($connection, $version);

        $GLOBALS['injector']->getInstance('Horde_Autoloader')->addClassPathMapper(new Horde_Autoloader_ClassPathMapper_Prefix('/^Content_/', $GLOBALS['registry']->get('fileroot', 'content') . '/lib/'));
        if (!class_exists('Content_Tagger')) {
            throw new Horde_Exception('The Content_Tagger class could not be found. Make sure the Content application is installed.');
        }
        $type_mgr = $GLOBALS['injector']->getInstance('Content_Types_Manager');
        $types = $type_mgr->ensureTypes(array('gallery', 'image'));
        $this->_type_ids = array(
            'gallery' => (int)$types[0],
            'image' => (int)$types[1]);
        $this->_tagger = $GLOBALS['injector']->getInstance('Content_Tagger');
        $this->_shares = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Share')->create('ansel');

    }

    public function up()
    {
        $tableList = $this->tables();
        if (in_array('ansel_galleries_tags', $tableList)) {
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
                $this->_tagger->tag(
                    $row['share_owner'],
                    array('object' => (string)$row['gallery_id'], 'type' => $this->_type_ids['gallery']),
                    $row['tag_name']);
            }
            $this->announce('Gallery tags finished.');
            $sql = 'SELECT ansel_images.image_id AS iid, tag_name, share_owner FROM ansel_images '
                . 'RIGHT JOIN ansel_images_tags ON ansel_images.image_id = ansel_images_tags.image_id '
                . 'LEFT JOIN ansel_shares ON ansel_shares.share_id = ansel_images.gallery_id '
                . 'LEFT JOIN ansel_tags ON ansel_tags.tag_id = ansel_images_tags.tag_id';
            $this->announce('Migrating image tags. This may take even longer...');
            $rows = $this->_connection->selectAll($sql);
            foreach ($rows as $row) {
                $this->_tagger->tag(
                    $row['share_owner'],
                    array('object' => (string)$row['gallery_id'], 'type' => $this->_type_ids['image']),
                    $row['tag_name']);
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