<?php
/**
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 */

require_once __DIR__ . '/../lib/Kronolith.php';

/**
 * Add hierarchcal related columns to the legacy sql share driver
 *
 * Copyright 2011-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 */
class KronolithUpgradeResourcesToShares extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        // shares ng
        $this->addColumn('kronolith_sharesng', 'attribute_email','text');
        $this->addColumn('kronolith_sharesng', 'attribute_members','text');
        $this->addColumn('kronolith_sharesng', 'attribute_response_type','integer');
        $this->addColumn('kronolith_sharesng', 'attribute_type', 'integer', array('default' => Kronolith::SHARE_TYPE_USER));
        $this->addColumn('kronolith_sharesng', 'attribute_isgroup', 'integer', array('default' => 0));

        // legacy shares
        $this->addColumn('kronolith_shares', 'attribute_email','text');
        $this->addColumn('kronolith_shares', 'attribute_members','text');
        $this->addColumn('kronolith_shares', 'attribute_response_type','integer');
        $this->addColumn('kronolith_shares', 'attribute_type', 'integer', array('default' => Kronolith::SHARE_TYPE_USER));
        $this->addColumn('kronolith_shares', 'attribute_isgroup', 'integer', array('default' => 0));

        /** Migrate existing resources to shares */
        $columns = $this->_connection->columns('kronolith_resources');

        /** Try to get existing data charset **/
        $config = Horde::getDriverConfig('resource', 'sql');
        $charset = empty($config['charset']) ? 'utf-8' : $config['charset'];

        $rows = $this->_connection->select('SELECT * FROM kronolith_resources');
        $shares = $GLOBALS['injector']
             ->getInstance('Horde_Core_Factory_Share')
             ->create('kronolith');

        foreach ($rows as $row) {
            $share = $shares->newShare(
                null,
                $row['resource_calendar'],
                Horde_String::convertCharset($row['resource_name'], $charset, 'utf-8')
            );
            $share->set('desc', $columns['resource_description']->binaryToString($row['resource_description']));
            $share->set('email', $row['resource_email']);
            $share->set('response_type', $row['resource_response_type']);
            $share->set('calendar_type', Kronolith::SHARE_TYPE_RESOURCE);
            $share->set('isgroup', $row['resource_type'] == 'Group');
            $share->set('members', $columns['resource_members']->binaryToString($row['resource_members']));

            /* Perms to match existing behavior */
            $share->addDefaultPermission(Horde_Perms::SHOW);
            $share->addDefaultPermission(Horde_Perms::READ);
            $share->addDefaultPermission(Horde_Perms::EDIT);

            $share->save();
        }
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $shares = $GLOBALS['injector']
             ->getInstance('Horde_Core_Factory_Share')
             ->create('kronolith');

        $resources = $shares->listAllShares();
        foreach ($resources as $resource) {
            if ($resource->get('calendar_type') == Kronolith::SHARE_TYPE_RESOURCE) {
                $shares->removeShare($resource);
            }
        }

        $this->removeColumn('kronolith_sharesng', 'attribute_email');
        $this->removeColumn('kronolith_sharesng', 'attribute_members');
        $this->removeColumn('kronolith_sharesng', 'attribute_response_type');
        $this->removeColumn('kronolith_sharesng', 'attribute_type');
        $this->removeColumn('kronolith_sharesng', 'attribute_isgroup');

        $this->removeColumn('kronolith_shares', 'attribute_email');
        $this->removeColumn('kronolith_shares', 'attribute_members');
        $this->removeColumn('kronolith_shares', 'attribute_response_type');
        $this->removeColumn('kronolith_shares', 'attribute_type');
        $this->removeColumn('kronolith_shares', 'attribute_isgroup');
    }

}
