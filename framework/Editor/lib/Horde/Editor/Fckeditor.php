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
        $js = array(
            'var oFCKeditor = new FCKeditor("' . $params['id'] . '")',
            'oFCKeditor.BasePath = "' . $fck_path . '"'
        );

        if (!empty($params['no_notify'])) {
            $this->_js = '<script type="text/javascript" src="' . $fck_path . 'fckeditor.js"></script><script type="text/javascript">' . implode(';', $js) . '</script>';
        } else {
            Horde::addScriptFile('prototype.js', 'horde');
            Horde::addScriptFile($fck_path . 'fckeditor.js', null, array('external' => true));
            $js[] = 'oFCKeditor.ReplaceTextarea()';
            Horde::addInlineScript($js, 'load');
        }
    }

    /**
     * Does the current browser support the Horde_Editor driver.
     *
     * @return boolean  True if the browser supports the editor.
     */
    public function supportedByBrowser()
    {
        global $browser;

        switch ($browser->getBrowser()) {
        case 'webkit':
        case 'msie':
        case 'mozilla':
        case 'opera':
            // MSIE: 5.5+
            // Firefox: 1.5+
            // Opera: 9.5+
            // Safari: 3.0+
            return $browser->hasFeature('rte');

        default:
            return false;
        }
    }

}
