<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * A Horde_Injector based factory for the IMP_Maillog object.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Factory_Maillog extends Horde_Core_Factory_Injector
{
    /**
     * Return the IMP_Maillog instance.
     *
     * @return IMP_Maillog  The singleton instance.
     */
    public function create(Horde_Injector $injector)
    {
        global $conf, $injector, $registry;

        $storage = array();

        if ($injector->getInstance('IMP_Factory_Imap')->create()->isImap()) {
            $storage[] = new IMP_Maillog_Storage_Mdnsent();
        }

        $driver = isset($conf['maillog']['driver'])
            ? $conf['maillog']['driver']
            // @todo BC support for 'use_maillog'
            : (empty($conf['maillog']['use_maillog']) ? 'none' : 'history');

        switch ($driver) {
        case 'history':
            $storage[] = new IMP_Maillog_Storage_History(
                $injector->getInstance('Horde_History'),
                $registry->getAuth()
            );
            break;

        case 'none':
        default:
            $storage[] = new IMP_Maillog_Storage_Null();
            break;
        }

        return new IMP_Maillog(
            (count($storage) > 1)
                ? new IMP_Maillog_Storage_Composite($storage)
                : reset($storage)
        );
    }

}
