<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Forward log entry.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 *
 * @property-read string $recipients  List of recipients.
 */
class IMP_Maillog_Log_Forward extends IMP_Maillog_Log_Base
{
    /**
     */
    protected $_action = 'forward';

    /**
     * List of recipients.
     *
     * @var string
     */
    protected $_recipients;

    /**
     * Constructor.
     *
     * @param string $recipients  Recipient list.
     */
    public function __construct($recipients = null)
    {
        $this->_recipients = strval($recipients);
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'recipients':
            return $this->_recipients;
        }

        return parent::__get($name);
    }

    /**
     */
    public function addData()
    {
        return array(
            'recipients' => $this->recipients
        );
    }

    /**
     */
    public function parseData(array $data)
    {
        $this->_recipients = $data['recipients'];
    }

    /**
     */
    protected function _getMessage()
    {
        return sprintf(
            _("You forwarded this message on %s to: %s."),
            $this->date,
            $this->recipients
        );
    }

}
