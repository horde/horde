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
class AnselUpgradeCategoriesToTags extends Horde_Db_Migration_Base
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
        /* Gallery tags */
        $t = $this->_connection->table('ansel_shares');
        $cols = $t->getColumns();
        if (in_array('attribute_category', array_keys($cols))) {
            $sql = 'SELECT share_id, attribute_category, share_owner FROM ansel_shares';
            $this->announce('Migrating gallery categories.');
            $rows = $this->_connection->selectAll($sql);
            foreach ($rows as $row) {
                $this->_tagger->tag(
                    $row['share_owner'],
                    array('object' => (string)$row['share_id'], 'type' => $this->_type_ids['gallery']),
                    $row['attribute_category']);
            }
            $this->announce('Gallery categories successfully migrated.');
            $this->removeColumn('ansel_shares', 'attribute_category');
        } else {
            $this->announce('Gallery categories ALREADY migrated.');
        }
    }

    public function down()
    {
        // Not supported, no way to tell which tags were categories.
    }

}