<?php
/**
 * Common code dealing with quota handling.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Ui_Quota
{
    /**
     * Returns data needed to output quota.
     *
     * @return array  Array with these keys: class, message, percent.
     */
    public function quota()
    {
        global $injector, $session;

        if (!$session->get('imp', 'imap_quota')) {
            return false;
        }

        try {
            $quotaDriver = $injector->getInstance('IMP_Quota');
            $quota = $quotaDriver->getQuota();
        } catch (IMP_Exception $e) {
            Horde::log($e, 'ERR');
            return false;
        }

        if (empty($quota)) {
            return false;
        }

        $strings = $quotaDriver->getMessages();
        list($calc, $unit) = $quotaDriver->getUnit();
        $ret = array(
            'class' => '',
            'percent' => 0
        );

        if ($quota['limit'] != 0) {
            $quota['usage'] = $quota['usage'] / $calc;
            $quota['limit'] = $quota['limit'] / $calc;
            $ret['percent'] = ($quota['usage'] * 100) / $quota['limit'];
            if ($ret['percent'] >= 90) {
                $ret['class'] = 'quotaalert';
            } elseif ($ret['percent'] >= 75) {
                $ret['class'] = 'quotawarn';
            }

            $ret['message'] = sprintf($strings['short'], $ret['percent'], $quota['limit'], $unit);
            $ret['percent'] = sprintf("%.2f", $ret['percent']);
        } else {
            if ($quota['usage'] != 0) {
                $quota['usage'] = $quota['usage'] / $calc;

                $ret['message'] = sprintf($strings['nolimit_short'], $quota['usage'], $unit);
            } else {
                $ret['message'] = _("No limit");
            }
        }

        return $ret;
    }

}
