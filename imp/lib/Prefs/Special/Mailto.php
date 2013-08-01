<?php
/**
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2012-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Special prefs handling for the 'mailto_handler' preference.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
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
        global $page_output, $registry;

        $name = $registry->get('name');

        $page_output->addInlineScript(array(
            'if (!Object.isUndefined(navigator.registerProtocolHandler))' .
            '$("mailto_handler").show().down("A").observe("click", function() {' .
                'navigator.registerProtocolHandler("mailto","' .
                IMP_Basic_Compose::url(array('full' => true))->setRaw(true)->add(array(
                    'actionID' => 'mailto_link',
                    'to' => ''
                )) .
                '=%s","' . $name . '");' .
            '})'
        ), true);

        $view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/prefs'
        ));
        $view->addHelper('Horde_Core_View_Helper_Image');

        $view->name = $name;

        return $view->render('mailto');
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        return false;
    }

}
