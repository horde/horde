<?php
/**
 * This class implements the personal message flag.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Flag_System_Personal extends IMP_Flag_System
{
    /**
     */
    protected $_css = 'flagPersonal';

    /**
     */
    protected $_id = 'personal';

    /**
     */
    protected function _getLabel()
    {
        return _("Personal");
    }

    /**
     * @param mixed $data  Either an array of To addresses as returned by
     *                     Horde_Mime_Address::getAddressesFromObject() or the
     *                     identity that matched the address list.
     */
    public function match($data)
    {
        if (is_array($data)) {
            $identity = $GLOBALS['injector']->getInstance('IMP_Identity');

            foreach ($data as $val) {
                if ($identity->hasAddress($val['inner'])) {
                    return true;
                }
            }
        } else if (!is_null($data)) {
            return true;
        }

        return false;
    }

}
