<?php
/**
 * Provides logic to format compose message data for delivery to the browser.
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
class IMP_Compose_View
{
    protected $_compose;

    public function __construct($cache_id)
    {
        $this->_compose = $GLOBALS['injector']->getInstance('IMP_Factory_Compose')->create($cache_id);
    }

    /**
     * @throws IMP_Exception
     */
    public function composeAttachPreview($id, $autodetect = false,
                                         $ctype = null)
    {
        if (!$mime = $this->_compose->buildAttachment($id)) {
            $e = new IMP_Exception(_("Could not display attachment data."));
            $e->logged = true;
            throw $e;
        }
        $mime->setMimeId($id);

        $contents = new IMP_Contents($mime);
        $render = $contents->renderMIMEPart($id, $contents::RENDER_RAW_FALLBACK, array(
            'autodetect' => $autodetect,
            'type' => $ctype
        ));

        if (!empty($render)) {
            return reset($render);
        } elseif ($autodetect) {
            $e = new IMP_Exception(_("Could not auto-determine data type."));
            $e->logged = true;
            throw $e;
        }

        return array();
    }

}
