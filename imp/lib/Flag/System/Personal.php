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
 * This class implements the personal message flag.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
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
     * @param string $data  E-mail address.
     */
    public function match($data)
    {
        return ($data instanceof Horde_Mail_Rfc822_List)
            ? $GLOBALS['injector']->getInstance('IMP_Identity')->hasAddress($data)
            : !is_null($data);
    }

}
