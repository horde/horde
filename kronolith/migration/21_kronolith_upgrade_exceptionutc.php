<?php
/**
 * Fix exceptionoriginaldate field to be UTC time.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 */
class KronolithUpgradeExceptionUtc extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        // Ensure we can run the migration. If registry is not available,
        // it's a new install and does not need this migration anyway.
        try {
            $registry = $GLOBALS['injector']->getInstance('Horde_Registry');
            if (!($registry instanceof Horde_Registry)) {
                return;
            }
            $registry->importConfig('kronolith');
            if (!$GLOBALS['conf']['calendar']['params']['utc']) {
                return;
            }
        } catch (Exception $e) {
            return;
        }

        $sql = 'SELECT event_creator_id, event_uid, event_exceptionoriginaldate FROM kronolith_events WHERE event_exceptionoriginaldate IS NOT NULL';
        $update = 'UPDATE kronolith_events SET event_exceptionoriginaldate = ? WHERE event_uid = ?';
        $rows = $this->selectAll($sql);
        $creator = null;
        $utc = new DateTimeZone('UTC');
        foreach ($rows as $row) {
            if ($row['event_creator_id'] != $creator) {
                $prefs = $GLOBALS['injector']
                    ->getInstance('Horde_Core_Factory_Prefs')
                    ->create('horde', array(
                        'cache' => false,
                        'user' => $row['event_creator_id'])
                );

                $tz = $prefs->getValue('timezone');
                if (empty($tz)) {
                    $tz = date_default_timezone_get();
                }
                $tz = new DateTimeZone($tz);
                $creator = $row['event_creator_id'];
            }

            $eod = new DateTime($row['event_exceptionoriginaldate'], $tz);
            $eod->setTimezone($utc);

            try {
                $this->update($update, array(
                    $eod->format('Y-m-d H:i:s'),
                    $row['event_uid']));
            } catch (Horde_Db_Exception $e) {
                throw new Horde_Exception($e);
            }
        }
    }

    /**
     * Downgrade
     */
    public function down()
    {
        // Ensure we can run the migration. If registry is not available,
        // it's a new install and does not need this migration anyway.
        try {
            $registry = $GLOBALS['injector']->getInstance('Horde_Registry');
            if (!($registry instanceof Horde_Registry)) {
                return;
            }
            $registry->importConfig('kronolith');
            if (!$GLOBALS['conf']['calendar']['params']['utc']) {
                return;
            }
        } catch (Exception $e) {
            return;
        }
        $sql = 'SELECT event_creator_id, event_uid, event_exceptionoriginaldate FROM kronolith_events WHERE event_exceptionoriginaldate IS NOT NULL;';
        $update = 'UPDATE kronolith_events SET event_exceptionoriginaldate = ? WHERE event_uid = ?';
        $rows = $this->selectAll($sql);
        $creator = null;
        $utc = new DateTimeZone('UTC');
        foreach ($rows as $row) {
            if ($row['event_creator_id'] != $creator) {
                $prefs = $GLOBALS['injector']
                    ->getInstance('Horde_Core_Factory_Prefs')
                    ->create('horde', array(
                        'cache' => false,
                        'user' => $row['event_creator_id'])
                );
                $tz = $prefs->getValue('timezone');
                if (empty($tz)) {
                    $tz = date_default_timezone_get();
                }
                $tz = new DateTimeZone($tz);
                $creator = $row['event_creator_id'];
            }

            $eod = new DateTime($row['event_exceptionoriginaldate'], $utc);
            $eod->setTimezone($tz);

            try {
                $this->update($update, array(
                    $eod->format('Y-m-d H:i:s'),
                    $row['event_uid']));
            } catch (Horde_Db_Exception $e) {
                throw new Horde_Exception($e);
            }
        }
    }

}
