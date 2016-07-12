<?php
/**
 * Copyright 2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */

/**
 * The IMP_Mime_Viewer_Syntaxhighlighter class extends
 * Horde_Core_Mime_Viewer_Syntaxhighlighter in order to catch when the preview
 * pane is updated.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Mime_Viewer_Syntaxhighlighter extends Horde_Core_Mime_Viewer_Syntaxhighlighter
{
    public function __construct(Horde_Mime_Part $part, array $conf = array())
    {
        parent::__construct($part, $conf);
        $GLOBALS['page_output']->addScriptfile('syntaxhighlighter.js');
    }

}
