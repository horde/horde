<?php
/**
 * Move tags from nag categories to content storage.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package Nag
 */
class NagUpgradeCategoriesToTags extends Horde_Db_Migration_Base
{
    public function __construct(Horde_Db_Adapter $connection, $version = null)
    {
        parent::__construct($connection, $version);

        // Can't use Nag's tagger since we can't init Nag.
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
        $types = $type_mgr->ensureTypes(array('task'));
        $this->_type_ids = array('task' => (int)$types[0]);
        $this->_tagger = $GLOBALS['injector']->getInstance('Content_Tagger');
        $this->_shares = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Share')->create('nag');
    }

    public function up()
    {
        $sql = 'SELECT task_uid, task_category, task_creator, task_owner FROM nag_tasks';
        $this->announce('Migrating task categories to tags.');
        $rows = $this->select($sql);
        foreach ($rows as $row) {
            $this->_tagger->tag(
                $row['task_creator'],
                array('object' => (string)$row['task_uid'],
                      'type' => $this->_type_ids['task']),
                $row['task_category']
            );

            // Do we need to tag the task again, but as the share owner?
            try {
                $list = $this->_shares->getShare($row['task_owner']);
                if ($list->get('owner') != $row['task_creator']) {
                    $this->_tagger->tag(
                        $list->get('owner'),
                        array('object' => (string)$row['task_uid'],
                              'type' => $this->_type_ids['task']),
                        $row['task_category']
                    );
                }
            } catch (Exception $e) {
                $this->announce('Unable to find Share: ' . $row['task_owner'] . ' Skipping.');
            }
        }
        $this->announce('Task categories successfully migrated.');
        $this->removeColumn('nag_tasks', 'task_category');
    }

    public function down()
    {
        $this->addColumn('nag_tasks', 'task_category', 'string', array('limit' => 80));
        $this->announce('Migrating task tags to categories.');
        $sql = 'UPDATE nag_tasks SET task_category = ? WHERE task_uid = ?';
        $rows = $this->select('SELECT task_uid, task_category, task_creator, task_owner FROM nag_tasks');
        foreach ($rows as $row) {
            $tags = $this->_tagger->getTagsByObjects(
                $row['task_uid'],
                $this->_type_ids['task']);
            if (!count($tags) || !count($tags[$row['task_uid']])) {
                continue;
            }
            $this->update($sql, array(reset($tags[$row['task_uid']]), (string)$row['task_uid']));
        }
        $this->announce('Task tags successfully migrated.');
    }

}