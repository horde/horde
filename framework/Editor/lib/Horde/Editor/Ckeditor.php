<?php
/**
 * This driver provides the code needed to initialize the CKeditor javascript
 * WYSIWYG editor.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Editor
 */
class Horde_Editor_Ckeditor extends Horde_Editor
{
    /**
     * @param array $params  The following configuration parameters:
     *   - basic: (boolean) Load "basic" editor (a small javascript stub that
     *            will download the full code on demand)?
     *   - config: (mixed) If an array, the javascript config hash used to
     *             indiciate the config for this editor instance. If a string,
     *             will be used directly as the javascript config name to use
     *             when loading (must exist elsewhere in page).
     *   - id: (string) The ID of the text area to turn into an editor. If
     *         empty, won't automatically load the editor.
     */
    public function initialize(array $params = array())
    {
        if (!$this->supportedByBrowser()) {
            return;
        }

        $ck_file = empty($params['basic'])
            ? 'ckeditor.js'
            : 'ckeditor_basic.js';

        if (isset($params['config'])) {
            if (is_array($params['config'])) {
                /* Globally disable spell check as you type. */
                $params['config']['scayt_autoStartup'] = false;
                $params['config'] = Horde_Serialize::serialize($params['config'], Horde_Serialize::JSON);
            }
        } else {
            $params['config'] = array();
        }

        $this->_js = array(
            'files' => array(
                $ck_file
            ),
            'script' => array()
        );

        if (isset($params['id'])) {
            $this->_js['script'] = array(
                'CKEDITOR.replace("' . $params['id'] . '",' . $params['config'] . ');',
                'CKEDITOR.config.toolbar_Full.push(["Code"]);'
            );
        }
    }

    /**
     * Does the current browser support this driver.
     *
     * @return boolean  True if the browser supports the editor.
     */
    public function supportedByBrowser()
    {
        if (!$this->_browser) {
            return true;
        }

        switch ($this->_browser->getBrowser()) {
        case 'webkit':
        case 'msie':
        case 'mozilla':
        case 'opera':
            // MSIE: 5.5+
            // Firefox: 1.5+
            // Opera: 9.5+
            // Safari: 3.0+
            return $this->_browser->hasFeature('rte');

        default:
            return false;
        }
    }
}
