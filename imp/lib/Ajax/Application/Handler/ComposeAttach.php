<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Defines AJAX actions used to attach files to a compose message.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ajax_Application_Handler_ComposeAttach extends Horde_Core_Ajax_Application_Handler
{
    /**
     * AJAX action: Add an attachment to a compose message.
     *
     * Variables used:
     *   - composeCache: (string) The IMP_Compose cache identifier.
     *   - img_tag: (boolean) If true, return related image tag.
     *   - json_return: (boolean) If true, returns JSON. Otherwise, JSON-HTML.
     *
     * @return object  False on failure, or an object with the following
     *                 properties:
     *   - action: (string) The action.
     *   - atc_id: (integer) The attachment ID.
     *   - img: (string) The image tag to replace the data with, if 'img_tag'
     *          is set.
     *   - success: (integer) 1 on success, 0 on failure.
     */
    public function addAttachment()
    {
        global $injector, $notification;

        $result = new stdClass;
        $result->action = 'addAttachment';
        $result->success = 0;

        /* A max POST size failure will result in ALL HTTP parameters being
         * empty. Catch that here. */
        if (!isset($this->vars->composeCache)) {
            $notification->push(_("Your attachment was not uploaded. Most likely, the file exceeded the maximum size allowed by the server configuration."), 'horde.warning');
        } else {
            $imp_compose = $injector->getInstance('IMP_Factory_Compose')->create($this->vars->composeCache);

            if ($imp_compose->canUploadAttachment()) {
                try {
                    $atc_ob = $imp_compose->addAttachmentFromUpload('file_upload');
                    $result->atc_id = $atc_ob->id;
                    $result->success = 1;

                    /* This currently only occurs when pasting/dropping image
                     * into HTML editor. */
                    if ($this->vars->img_tag) {
                        $dom_doc = new DOMDocument();
                        $img = $dom_doc->createElement('img');
                        $img->setAttribute('src', strval($atc_ob->viewUrl()->setRaw(true)));
                        $imp_compose->addRelatedAttachment($atc_ob, $img, 'src');

                        /* Complicated to grab single element from a
                         * DOMDocument object, so build tag ourselves. */
                        $img_tag = '<img';
                        foreach ($img->attributes as $node) {
                            $img_tag .= ' ' . $node->name . '="' . htmlspecialchars($node->value) . '"';
                        }
                        $result->img = $img_tag . '/>';
                    } else {
                        $this->_base->queue->attachment($atc_ob);
                        $notification->push(sprintf(_("Added \"%s\" as an attachment."), $atc_ob->getPart()->getName()), 'horde.success');
                    }

                    $this->_base->queue->compose($imp_compose);
                } catch (IMP_Compose_Exception $e) {
                    $notification->push($e, 'horde.error');
                }
            } else {
                $notification->push(_("Uploading attachments has been disabled on this server."), 'horde.error');
            }
        }

        return $this->vars->json_return
            ? $result
            : new Horde_Core_Ajax_Response_HordeCore_JsonHtml($result);
    }

}
