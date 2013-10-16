<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Extension of IMP_Compose_Exception that handles the situation of invalid
 * address input. Allows details of individual e-mail address errors to be
 * communicated to the user.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Compose_Exception_Address extends IMP_Compose_Exception
{
    /**
     * The list of addresses (keys) and Horde_Mail_Exception objects (values).
     *
     * @var array
     */
    public $addresses = array();

}
