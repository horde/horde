<?php
/**
 * Copyright 2005-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2005-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Save an image to a registry-defined application.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2005-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Basic_Saveimage extends IMP_Basic_Base
{
    /**
     */
    protected function _init()
    {
        global $injector, $notification, $page_output, $registry;

        if (!$registry->hasMethod('images/selectGalleries') ||
            !$registry->hasMethod('images/saveImage')) {
            $e = new IMP_Exception('Image saving is not available.');
            $e->logged = true;
            throw $e;
        }

        /* Run through the action handlers. */
        switch ($this->vars->actionID) {
        case 'save_image':
            $contents = $injector->getInstance('IMP_Factory_Contents')->create($this->indices);
            if (!($mime_part = $contents->getMIMEPart($this->vars->id))) {
                $notification->push(_("Could not load message."));
                break;
            }
            $image_data = array(
                'data' => $mime_part->getContents(),
                'description' => $mime_part->getDescription(true),
                'filename' => $mime_part->getName(true),
                'type' => $mime_part->getType()
            );
            try {
                $registry->images->saveImage($this->vars->gallery, $image_data);
            } catch (Horde_Exception $e) {
                $notification->push($e);
                break;
            }
            echo Horde::wrapInlineScript(array('window.close();'));
            exit;
        }

        /* Build the view. */
        $view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/saveimage'
        ));
        $view->addHelper('Horde_Core_View_Helper_Image');
        $view->addHelper('Text');

        $view->action = self::url();
        $view->gallerylist = $registry->images->selectGalleries(array(
            'perm' => Horde_Perms::EDIT
        ));
        $view->id = $this->vars->id;
        $view->muid = strval($this->indices);

        $page_output->topbar = $page_output->sidebar = false;

        $page_output->addInlineScript(array(
            '$$("INPUT.horde-cancel").first().observe("click", function() { window.close(); })'
        ), true);

        $this->title = _("Save Image");
        $this->output = $view->render('saveimage');
    }

    /**
     */
    public static function url(array $opts = array())
    {
        return Horde::url('basic.php')->add('page', 'saveimage');
    }

}
