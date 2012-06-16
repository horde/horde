<?php
/**
 * Special prefs handling for the 'mailto_handler' preference.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Prefs_Special_Mailto implements Horde_Core_Prefs_Ui_Special
{
    /**
     */
    public function init(Horde_Core_Prefs_Ui $ui)
    {
    }

    /**
     */
    public function display(Horde_Core_Prefs_Ui $ui)
    {
        global $injector, $page_output, $registry;

        $page_output->addInlineScript(array(
            'if (!Object.isUndefined(navigator.registerProtocolHandler))' .
            '$("mailto_handler").show().down("A").observe("click", function() {' .
                'navigator.registerProtocolHandler("mailto","' .
                Horde::url('compose.php', true)->setRaw(true)->add(array(
                    'actionID' => 'mailto_link',
                    'to' => ''
                )) .
                '=%s","' . $registry->get('name') . '");' .
            '})'
        ), true);

        $t = $injector->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        $t->set('desc', sprintf(_("Click here to open all mailto: links using %s."), $registry->get('name')));
        $t->set('img', Horde::img('compose.png'));

        return $t->fetch(IMP_TEMPLATES . '/prefs/mailto.html');
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        return false;
    }

}
