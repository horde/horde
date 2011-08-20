<?php
/**
 * This class implements the personal message flag.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Flag_System_Personal extends IMP_Flag_System_Match_Address
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
