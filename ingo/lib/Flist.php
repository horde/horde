<?php
/**
 * Copyright 2002-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */

/**
 * Generate folder lists for use in UI elements.
 *
 * @author    Mike Cochrane <mike@graftonhall.co.nz>
 * @author    Jan Schneider <jan@horde.org>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */
class Ingo_Flist
{
    /**
     * Generates a folder widget.
     *
     * If an application is available that provides a mailboxList method
     * then a &lt;select&gt; input is created. Otherwise a simple text field
     * is returned.
     *
     * @param string $value    The current value for the field.
     * @param string $tagname  The label for the select tag.
     *
     * @return string  The HTML to render the field.
     */
    static public function select($value = null, $tagname = 'actionvalue')
    {
        global $injector, $page_output, $registry;

        $view = $injector->createInstance('Horde_View');
        $view->addHelper('FormTag');
        $view->addHelper('Tag');

        $view->tagname = $tagname;
        $view->val = $value;

        if ($registry->hasMethod('mail/mailboxList')) {
            try {
                $view->create = $registry->hasMethod('mail/createMailbox');
                $view->mboxes = $registry->call('mail/mailboxList');

                $page_output->addScriptFile('new_folder.js');
                $page_output->addInlineJsVars(array(
                    'IngoNewFolder.folderprompt' => _("Please enter the name of the new folder:")
                ));

                return $view->render('flist/select');
            } catch (Horde_Exception $e) {}
        }

        return $view->render('flist/input');
    }

}
