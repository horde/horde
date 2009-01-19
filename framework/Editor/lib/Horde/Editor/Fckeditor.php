<?php
/**
 * The Horde_Editor_fckeditor:: class provides an WYSIWYG editor for use
 * in the Horde Framework.
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Nuno Loureiro <nuno@co.sapo.pt>
 * @author  Jan Schneider <jan@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Editor
 */
class Horde_Editor_Fckeditor extends Horde_Editor
{
    /**
     * Constructor.
     *
     * @param array $params  The following configuration parameters:
     * <pre>
     * 'id' - The ID of the text area to turn into an editor.
     * 'no_notify' - Don't output JS code via notification library. Code will
     *               be stored for access via getJS().
     * </pre>
     */
    public function __construct($params = array())
    {
        $fck_path = $GLOBALS['registry']->get('webroot', 'horde') . '/services/editor/fckeditor/';
        $js = "var oFCKeditor = new FCKeditor('" . $params['id'] . "'); oFCKeditor.BasePath = '" . $fck_path . "';";

        if (!empty($params['no_notify'])) {
            $this->_js = '<script type="text/javascript" src="' . $fck_path . 'fckeditor.js"></script><script type="text/javascript">' . $js . '</script>';
        } else {
            Horde::addScriptFile('prototype.js', 'horde', true);
            $GLOBALS['notification']->push('Event.observe(window, \'load\', function() {' . $js . ' oFCKeditor.ReplaceTextarea();});', 'javascript');
            $GLOBALS['notification']->push($fck_path . 'fckeditor.js', 'javascript-file');
        }
    }

}
