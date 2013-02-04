<?php
/**
 * @copyright 2008-2013 Horde LLC (http://www.horde.org)
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Ansel
 */
/**
 * Ansel_View_GalleryRenderer_Gallery:: Class wraps display of the traditional
 * Gallery View.
 *
 * Copyright 2008-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @copyright 2008-2013 Horde LLC (http://www.horde.org)
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Ansel
 */
class Ansel_View_GalleryRenderer_Gallery extends Ansel_View_GalleryRenderer_Base
{

    public function __construct($view)
    {
        parent::__construct($view);
        $this->title = _("Standard Gallery");
        Ansel_ActionHandler::imageActions(Horde_Util::getFormData('actionID'));
    }

    /**
     * Return the HTML representing this view.
     *
     * @return string  The HTML.
     */
    public function html()
    {
        $view = $this->_getHordeView();
        if (!empty($this->view->api)) {
            Horde::startBuffer();
            $prototypejs = new Horde_Script_File_JsDir('prototype.js', 'horde');
            echo $prototypejs->tag_full;
            $html = Horde::endBuffer();

            return $html . $view->render('gallery');
        }

        return $view->render('gallery');
    }

}
