<?php
/**
 * Copyright 2013-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you did
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2013-2017 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Turba
 */

/**
 * Move tags from Turba categories to content storage.
 *
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2013-2017 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Turba
 */
class TurbaUpgradeCategoriesToTags extends Horde_Db_Migration_Base
{
    protected $_shares = null;

    protected function _init()
    {
        // Skip if run during unit tests when we don't need to migrate data.
        if (getenv('HORDE_UNIT_TEST')) {
            return;
        }

        // Can't use Turba's tagger since we can't init Turba.
        $GLOBALS['injector']->getInstance('Horde_Autoloader')
            ->addClassPathMapper(
                new Horde_Autoloader_ClassPathMapper_Prefix(
                    '/^Content_/',
                    $GLOBALS['registry']->get('fileroot', 'content') . '/lib/'
                )
        );

        if (!class_exists('Content_Tagger')) {
            throw new Horde_Exception('The Content_Tagger class could not be found. Make sure the Content application is installed.');
        }

        $type_mgr = $GLOBALS['injector']->getInstance('Content_Types_Manager');
        $types = $type_mgr->ensureTypes(array('contact'));
        $this->_type_ids = array('contact' => (int)$types[0]);
        $this->_tagger = $GLOBALS['injector']->getInstance('Content_Tagger');
        try {
            $this->_shares = $GLOBALS['injector']
                ->getInstance('Horde_Core_Factory_Share')
                ->create('turba');
        } catch (Exception $e) {
        }
    }

    public function up()
    {
        $this->_init();
        if ($this->_shares) {
            $sql = 'SELECT object_uid, object_category, owner_id FROM turba_objects';
            $this->announce('Migrating contact categories to tags.');
            $rows = $this->select($sql);
            foreach ($rows as $row) {
                try {
                    $owner = $this->_shares
                        ->getShare($row['owner_id'])
                        ->get('owner');
                } catch (Exception $e) {
                    $owner = $row['owner_id'];
                }
                if (strlen($owner)) {
                    $this->_tagger->tag(
                        $owner,
                        array('object' => (string)$row['object_uid'],
                              'type' => $this->_type_ids['contact']),
                        $row['object_category']
                    );
                }
            }
            $this->announce('Contact categories successfully migrated.');
        }
        $this->removeColumn('turba_objects', 'object_category');
    }

    public function down()
    {
        $this->_init();
        $this->addColumn('turba_objects', 'object_category', 'string', array('limit' => 80));
        $this->announce('Migrating contact tags to categories.');
        $sql = 'UPDATE turba_objects SET object_category = ? WHERE object_uid = ?';
        $rows = $this->select('SELECT object_uid FROM turba_objects');
        foreach ($rows as $row) {
            $tags = $this->_tagger->getTagsByObjects(
                $row['object_uid'],
                $this->_type_ids['contact']);
            if (!count($tags) || !count($tags[$row['object_uid']])) {
                continue;
            }
            $this->update($sql, array(reset($tags[$row['object_uid']]), (string)$row['object_uid']));
        }
        $this->announce('Contact tags successfully migrated.');
    }
}
