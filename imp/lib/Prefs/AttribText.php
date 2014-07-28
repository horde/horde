<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * This class manages the attrib_text preference.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Prefs_AttribText
{
    /**
     * Attribution text.
     *
     * @var string
     */
    protected $_text;

    /**
     * Constructor.
     *
     * @param string|Horde_Mail_Rfc822_Object $from  The email address of the
     *                                               original sender.
     * @param Horde_Mime_Headers $h           The headers object for the
     *                                        message.
     */
    public function __construct($from, Horde_Mime_Headers $h)
    {
        global $prefs;

        $addressList = $nameList = array();
        $addr_list = IMP::parseAddressList($from);

        foreach ($addr_list as $addr) {
            if (!is_null($addr->mailbox)) {
                $addressList[] = $addr->bare_address;
            }

            if (!is_null($addr->personal)) {
                $nameList[] = $addr->personal;
            } elseif (!is_null($addr->mailbox)) {
                $nameList[] = $addr->mailbox;
            }
        }

        /* Define the macros. */
        if (is_array($message_id = $h->getValue('message_id'))) {
            $message_id = reset($message_id);
        }
        if (!($subject = $h->getValue('subject'))) {
            $subject = _("[No Subject]");
        }
        $udate = strtotime($h->getValue('date'));

        $match = array(
            /* New line. */
            '/%n/' => "\n",

            /* The '%' character. */
            '/%%/' => '%',

            /* Name and email address of original sender. */
            '/%f/' => $from,

            /* Senders email address(es). */
            '/%a/' => implode(', ', $addressList),

            /* Senders name(s). */
            '/%p/' => implode(', ', $nameList),

            /* RFC 822 date and time. */
            '/%r/' => $h->getValue('date'),

            /* Date as ddd, dd mmm yyyy. */
            '/%d/' => strftime("%a, %d %b %Y", $udate),

            /* Date in locale's default. */
            '/%x/' => strftime("%x", $udate),

            /* Date and time in locale's default. */
            '/%c/' => strftime("%c", $udate),

            /* Message-ID. */
            '/%m/' => $message_id,

            /* Message subject. */
            '/%s/' => $subject
        );

        $this->_text = preg_replace(
            array_keys($match),
            array_values($match),
            $prefs->getValue('attrib_text')
        );
    }

    /**
     */
    public function __toString()
    {
        return $this->_text;
    }

}
