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
     * Is this the prefs screen?
     *
     * @var boolean
     */
    protected $_isPrefs = false;

    /**
     * The view mode.
     *
     * @var string
     */
    protected $_viewmode;

    /**
     * Constructor.
     *
     * @param array $options  Optional: 'prefs', 'viewmode'.
     */
    public function __construct($options = array())
    {
        parent::__construct();

        $this->_isPrefs = !empty($options['prefs']);
        $this->_viewmode = empty($options['viewmode'])
            ? IMP::getViewMode()
            : $options['viewmode'];

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
        if ($this->_viewmode == 'dimp') {
            $options['store'] = true;
        }

        /* Display IMAP alerts. */
        if (isset($GLOBALS['imp_imap']->ob)) {
            foreach ($GLOBALS['imp_imap']->ob()->alerts() as $alert) {
                $GLOBALS['notification']->push($alert, 'horde.warning');
            }
        }

        parent::notify($messageStack, $options);

        /* Preferences display. */
        if ($this->_isPrefs && ($msgs = $this->getStack())) {
            Horde::addInlineScript(array(
                'parent.DimpCore.showNotifications(' . Horde_Serialize::serialize($msgs, Horde_Serialize::JSON) . ')'
            ), 'dom');
        }
    }

}
