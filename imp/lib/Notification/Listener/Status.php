<?php
/**
 * The IMP_Notification_Listener_Status:: class extends the
 * Horde_Notification_Listener_Status:: class to display the messages for
 * IMP's special message types.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Notification
 */
class IMP_Notification_Listener_Status extends Horde_Notification_Listener_Status
{
    /**
     * The view mode.
     *
     * @var string
     */
    protected $_viewmode;

    /**
     * Constructor.
     *
     * @param array $options  One option required: 'viewmode'.
     */
    public function __construct($options)
    {
        parent::__construct();

        $this->_viewmode = $options['viewmode'];

        $image_dir = $GLOBALS['registry']->getImageDir();

        $this->_handles['imp.reply'] = array($image_dir . '/mail_answered.png', _("Reply"));
        $this->_handles['imp.forward'] = array($image_dir . '/mail_forwarded.png', _("Reply"));
        $this->_handles['imp.redirect'] = array($image_dir . '/mail_forwarded.png', _("Redirect"));
    }

    /**
     * Handle every message of type dimp.*; otherwise delegate back to
     * the parent.
     *
     * @param string $type  The message type in question.
     *
     * @return boolean  Whether this listener handles the type.
     */
    public function handles($type)
    {
        return (($this->_viewmode == 'dimp') &&
                (substr($type, 0, 5) == 'dimp.')) ||
                parent::handles($type);
    }

    /**
     * Returns all status message if there are any on the 'status' message
     * stack.
     *
     * @param array &$messageStack  The stack of messages.
     * @param array $options        An array of options.
     */
    public function notify(&$messageStack, $options = array())
    {
        /* For dimp, don't capture notification messages if we are logging
         * out. */
        if (($this->_viewmode == 'dimp') && Horde_Auth::getAuth()) {
            $options['store'] = true;
        }
        parent::notify($messageStack, $options);
    }

}
