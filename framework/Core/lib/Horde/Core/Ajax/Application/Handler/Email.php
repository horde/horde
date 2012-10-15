<?php
/**
 * Defines AJAX calls used to manipulate e-mail addresses.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Core_Ajax_Application_Handler_Email extends Horde_Core_Ajax_Application_Handler
{
    /**
     * Default domain.
     *
     * @var string
     */
    public $defaultDomain = null;

    /**
     * Parses a valid email address out of a complete address string.
     *
     * Variables used:
     *   - mbox: (string) The name of the new mailbox.
     *   - parent: (string) The parent mailbox.
     *
     * @return object  Object with the following properties:
     *   - email: (string) The parsed email address.
     *
     * @throws Horde_Exception
     * @throws Horde_Mail_Exception
     */
    public function parseEmailAddress()
    {
        $ob = new Horde_Mail_Rfc822_Address($this->vars->email);
        if (is_null($ob->mailbox)) {
            throw new Horde_Exception(Horde_Core_Translation::t("No valid email address found"));
        }

        if (!is_null($this->defaultDomain)) {
            $ob->host = $this->defaultDomain;
        }

        $ret = new stdClass;
        $ret->email = $ob->bare_address;

        return $ret;
    }

}
