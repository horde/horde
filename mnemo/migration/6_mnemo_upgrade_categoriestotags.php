<?php
/**
 * Move tags from mnemo categories to content storage.
 *
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Mnemo
 */
class MnemoUpgradeCategoriesToTags extends Horde_Db_Migration_Base
{
    protected function _init()
    {
        // Can't use Mnemo's tagger since we can't init Mnemo.
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
        $types = $type_mgr->ensureTypes(array('note'));
        $this->_type_ids = array('note' => (int)$types[0]);
        $this->_tagger = $GLOBALS['injector']->getInstance('Content_Tagger');
        try {
            $this->_shares = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Share')->create('mnemo');
        } catch (Exception $e) {
        }
    }

    public function up()
    {
        $this->_init();
        if ($this->_shares) {
            $sql = 'SELECT memo_uid, memo_category, memo_owner FROM mnemo_memos';
            $this->announce('Migrating note categories to tags.');
            $rows = $this->select($sql);
            foreach ($rows as $row) {
                try {
                    $list = $this->_shares->getShare($row['memo_owner']);
                    $this->_tagger->tag(
                        $list->get('owner'),
                        array('object' => (string)$row['memo_uid'],
                              'type' => $this->_type_ids['note']),
                        $row['memo_category']
                    );
                } catch (Exception $e) {
                    $this->announce('Unable to find Share: ' . $row['memo_owner'] . ' Skipping.');
                }
            }
            $this->announce('Note categories successfully migrated.');
        }
        $this->removeColumn('mnemo_memos', 'memo_category');
    }

    public function down()
    {
        $this->_init();
        $this->addColumn('mnemo_memos', 'memo_category', 'string', array('limit' => 80));
        $this->announce('Migrating note tags to categories.');
        $sql = 'UPDATE mnemo_memos SET memo_category = ? WHERE memo_uid = ?';
        $rows = $this->select('SELECT memo_uid FROM mnemo_memos');
        foreach ($rows as $row) {
            $tags = $this->_tagger->getTagsByObjects(
                $row['memo_uid'],
                $this->_type_ids['note']);
            if (!count($tags) || !count($tags[$row['memo_uid']])) {
                continue;
            }
            $this->update($sql, array(reset($tags[$row['memo_uid']]), (string)$row['memo_uid']));
        }
        $this->announce('Note tags successfully migrated.');
    }

}