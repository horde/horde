<?php
/**
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2012-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Common code dealing with quota UI display.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Quota_Ui
{
    /**
     * Returns data needed to output quota.
     *
     * @param string $mailbox  Mailbox to query.
     *
     * @return array  Array with these keys: class, message, percent.
     */
    public function quota($mailbox = null)
    {
        global $injector;

        if (!$injector->getInstance('IMP_Imap')->config->quota) {
            return false;
        }

        if (!is_null($mailbox)) {
            $mailbox = IMP_Mailbox::get($mailbox);
            if ($mailbox->nonimap) {
                return false;
            }
        }

        try {
            $quotaDriver = $injector->getInstance('IMP_Quota');
            $quota = $quotaDriver->getQuota($mailbox);
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
        } elseif ($quotaDriver->isHiddenWhenUnlimited()) {
            return false;
        } elseif ($quota['usage'] != 0) {
            $quota['usage'] = $quota['usage'] / $calc;

            $ret['message'] = sprintf($strings['nolimit_short'], $quota['usage'], $unit);
        } else {
            $ret['message'] = _("No limit");
        }

        return $ret;
    }

}
