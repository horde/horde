<?php
/**
 * Copyright 2012-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Attach javascript used to process a Vcard import request from IMP.
 *
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ajax_Imple_VcardImport extends Horde_Core_Ajax_Imple
{
    /**
     */
    protected $_observe = 'submit';

    /**
     * @param array $params  Configuration parameters:
     *   - mime_id: (string) The MIME ID of the message part with the key.
     *   - muid: (string) MUID of the message.
     */
    public function __construct(array $params = array())
    {
        // The mime id of the form created by Horde_Core_Mime_Viewer_Vcard
        // Don't like hard coding this, but since it's in Horde_Core we don't
        // have access to change it/read it.
        $params['id'] = 'vcard_import';
        parent::__construct($params);
    }

    // /**
    //  */
    protected function _attach($init)
    {
        return array(
            'mime_id' => $this->_params['mime_id'],
            'muid' => $this->_params['muid']
        );
    }

    /**
     * Variables required in form input:
     *   - imple_submit: vcard action. Contains import and source properties
     *   - mime_id
     *   - muid
     *
     * @return boolean  True on success.
     */
    protected function _handle(Horde_Variables $vars)
    {
        global $registry, $injector, $notification;

        $iCal = new Horde_Icalendar();
        try {
            $contents = $injector->getInstance('IMP_Factory_Contents')
                ->create(new IMP_Indices_Mailbox($vars));
            $mime_part = $contents->getMIMEPart($vars->mime_id);
            if (empty($mime_part)) {
                throw new IMP_Exception(_("Cannot retrieve vCard data from message."));
            } elseif (!$iCal->parsevCalendar($mime_part->getContents(), 'VCALENDAR', $mime_part->getCharset())) {
                throw new IMP_Exception(_("Error reading the contact data."));
            }
            $components = $iCal->getComponents();
        } catch (Exception $e) {
            $notification->push($e, 'horde.error');
            $actions = array();
        }

        $import = !empty($vars->imple_submit->import)
            ? $vars->imple_submit->import
            : false;
        $source = !empty($vars->imple_submit->source)
            ? $vars->imple_submit->source
            : false;

        if ($import && $source && $registry->hasMethod('contacts/import')) {
            $count = 0;
            foreach ($components as $c) {
                if ($c->getType() == 'vcard') {
                    try {
                        $registry->call('contacts/import', array($c, null, $source));
                        ++$count;
                    } catch (Horde_Exception $e) {
                        $notification->push(Horde_Core_Translation::t("There was an error importing the contact data:") . ' ' . $e->getMessage(), 'horde.error');
                    }
                }
            }
            $notification->push(sprintf(Horde_Core_Translation::ngettext(
                "%d contact was successfully added to your address book.",
                "%d contacts were successfully added to your address book.",
                $count),
                                        $count),
                                'horde.success');
        }
    }

}
