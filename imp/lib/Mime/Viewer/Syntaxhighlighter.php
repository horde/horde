<?php
/**
 * Copyright 2003-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  IMP
 */
/**
 * The IMP_Mime_Viewer_Syntaxhighlighter class extends the
 * Horde_Core_Mime_Viewer_Syntaxhightler in order to catch when the
 * preview pane is updated.
 *
 * Copyright 2003-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
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
