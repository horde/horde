<?php
/**
 * Copyright 2012-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2012-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Common code dealing with quota UI display.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Quota_Ui
{
    /** Session key for interval data. */
    const SESSION_INTERVAL_KEY = 'quota_interval';

    /**
     * Returns data needed to output quota.
     *
     * @param string $mailbox  Mailbox to query.
     * @param boolean $force   If true, ignore 'interval' config option and
     *                         force quota display.
     *
     * @return array|boolean  Array with these keys: class, message, percent.
     *                        Returns false if no updated quota information.
     */
    public function quota($mailbox = null, $force = true)
    {
        global $injector, $session;

        $qconfig = $injector->getInstance('IMP_Factory_Imap')->create()->config->quota;
        if (!$qconfig) {
            return false;
        }

        $qlist = array();

        if (!is_null($mailbox)) {
            $mailbox = IMP_Mailbox::get($mailbox);
            if ($mailbox->nonimap) {
                return false;
            }

            if (!$force) {
                $qlist = $session->get(
                    'imp',
                    self::SESSION_INTERVAL_KEY,
                    $session::TYPE_ARRAY
                );

                if (isset($qlist[strval($mailbox)]) &&
                    (time() < $qlist[strval($mailbox)])) {
                    return false;
                }
            }
        }

        try {
            $quotaDriver = $injector->getInstance('IMP_Quota');
            $quota = $quotaDriver->getQuota($mailbox);
        } catch (IMP_Exception $e) {
            Horde::log($e, 'ERR');
            return false;
        }

        $qlist[strval($mailbox)] = $qconfig['params']['interval'] + time();
        $session->set('imp', self::SESSION_INTERVAL_KEY, $qlist);

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
