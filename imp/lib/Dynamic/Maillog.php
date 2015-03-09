<?php
/**
 * Copyright 2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Maillog message ID lookup page for dynamic view.
 *
 * Expects two URL parameters:
 *   - msgid: Message-ID
 *   - type: Maillog type
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Dynamic_Maillog extends IMP_Dynamic_Base
{
    /**
     * @throws IMP_Exception
     */
    protected function _init()
    {
        global $notification, $page_output;

        if (isset($this->vars->msgid) &&
            isset($this->vars->type) &&
            isset(IMP_Maillog_Storage_History::$drivers[$this->vars->type])) {
            $log = new IMP_Maillog_Storage_History::$drivers[$this->vars->type](array(
                'msgid' => $this->vars->msgid
            ));

            $query = $log->searchQuery();

            foreach ($log->searchMailboxes() as $val) {
                if ($indices = $val->runSearchQuery($query)) {
                    list($mbox, $uid) = $indices->getSingle();
                    $url = IMP_Dynamic_Message::url();
                    $url->add($mbox->urlParams($uid));
                    $url->redirect();
                }
            }
        }

        $page_output->addScriptFile('maillog.js');

        $page_output->addInlineJsVars(array(
            'ImpMaillog.error_msg' => _("Could not load message.")
        ));
    }

    /**
     */
    public static function url(array $opts = array())
    {
        return Horde::url('dynamic.php')->add('page', 'maillog');
    }

}
