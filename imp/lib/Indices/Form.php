<?php
/**
 * This class extends the base indices class by automatically converting
 * base64 encoded form data into the mailbox format internally understood by
 * the IMP server code.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Indices_Form extends IMP_Indices
{
    /**
     * Default name is base64url encoded version of INBOX.
     */
    protected $_default = 'SU5CT1g';

    /**
     */
    public function __construct()
    {
        if (func_num_args()) {
            $args = func_get_args();
            call_user_func_array(array($this, 'add'), $args);

            if ((count($args) == 1) && is_string($args[0])) {
                $converted = array();
                foreach ($this->_indices as $key => $val) {
                    $converted[strval(IMP_Mailbox::formFrom($key))] = $val;
                }
                $this->_indices = $converted;
            }
        }
    }

}
