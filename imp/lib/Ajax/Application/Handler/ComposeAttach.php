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
     *   - file_id: (integer) Browser ID of file.
     *   - img_data: (boolean) If true, return image data.
     *   - json_return: (boolean) If true, returns JSON. Otherwise, JSON-HTML.
     *
     * @return object  False on failure, or an object with the following
     *                 properties:
     *   - action: (string) The action.
     *   - file_id: (integer) Browser ID of file.
     *   - img: (object) Image data, if 'img_data' is set. Properties:
     *          height, related, src, width
     *   - success: (integer) 1 on success (at least one successful attached
     *              file), 0 on failure.
     */
    public function addAttachment()
    {
        global $injector, $notification;

        $result = new stdClass;
        $result->action = 'addAttachment';
        if (isset($this->vars->file_id)) {
            $result->file_id = $this->vars->file_id;
        }
        $result->success = 0;

        /* A max POST size failure will result in ALL HTTP parameters being
         * empty. Catch that here. */
        if (!isset($this->vars->composeCache)) {
            $notification->push(_("Your attachment was not uploaded. Most likely, the file exceeded the maximum size allowed by the server configuration."), 'horde.warning');
        } else {
            $imp_compose = $injector->getInstance('IMP_Factory_Compose')->create($this->vars->composeCache);

            if ($imp_compose->canUploadAttachment()) {
                try {
                    foreach ($imp_compose->addAttachmentFromUpload('file_upload') as $val) {
                        if ($val instanceof IMP_Compose_Exception) {
                            $notification->push($e, 'horde.error');
                        } else {
                            $result->success = 1;

                            /* This currently only occurs when
                             * pasting/dropping image into HTML editor. */
                            if ($this->vars->img_data) {
                                $result->img = new stdClass;
                                $result->img->src = strval($val->viewUrl()->setRaw(true));

                                $temp1 = new DOMDocument();
                                $temp2 = $temp1->createElement('span');
                                $imp_compose->addRelatedAttachment($val, $temp2, 'src');
                                $result->img->related = array(
                                    $imp_compose::RELATED_ATTR,
                                    $temp2->getAttribute($imp_compose::RELATED_ATTR)
                                );

                                try {
                                    $img_ob = $injector->getInstance('Horde_Core_Factory_Image')->create();
                                    $img_ob->loadString($val->storage->read()->getString(0));
                                    $d = $img_ob->getDimensions();
                                    $result->img->height = $d['height'];
                                    $result->img->width = $d['width'];
                                } catch (Exception $e) {}
                            } else {
                                $this->_base->queue->attachment($val);
                                $notification->push(sprintf(_("Added \"%s\" as an attachment."), $val->getPart()->getName()), 'horde.success');
                            }
                        }
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
