<?php
/**
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * A factory to allow for IMP's mail configuration to override the default
 * Horde configuration.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Factory_Mail extends Horde_Core_Factory_Mail
{
    /**
     */
    public function create($config = null)
    {
        global $injector;

        list($transport, $params) = $this->getConfig();

        if ($transport == 'smtp') {
            $params = array_merge(
                $params,
                $injector->getInstance('IMP_Factory_Imap')->create()->config->smtp
            );
        }

        return parent::create(array(
            'params' => $params,
            'transport' => $transport
        ));
    }

}
