<?php
/**
 * This class implements the CKeditor in the Horde Core framework.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Core
 */
class Horde_Core_Editor_Ckeditor extends Horde_Editor_Ckeditor
{
    /**
     */
    public function initialize(array $params = array())
    {
        global $page_output, $registry;

        parent::initialize($params);

        if (!empty($this->_js)) {
            $ck_path = $registry->get('jsuri', 'horde');

            foreach ($this->_js['files'] as $val) {
                $page_output->addScriptFile(
                    new Horde_Script_File_External($ck_path . '/' . $val)
                );
            }

            $page_output->addInlineScript($this->_js['script'], true);
        }
    }

}
