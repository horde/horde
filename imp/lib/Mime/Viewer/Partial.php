<?php
/**
 * The IMP_Mime_Viewer_Partial class allows message/partial messages
 * to be displayed (RFC 2046 [5.2.2]).
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Mime_Viewer_Partial extends Horde_Mime_Viewer_Base
{
    /**
     * This driver's display capabilities.
     *
     * @var array
     */
    protected $_capability = array(
        'full' => false,
        'info' => true,
        'inline' => false,
        'raw' => false
    );

    /**
     * Metadata for the current viewer/data.
     *
     * @var array
     */
    protected $_metadata = array(
        'compressed' => false,
        'embedded' => true,
        'forceinline' => true
    );

    /**
     * Cached data.
     *
     * @var array
     */
    static protected $_statuscache = array();

    /**
     * Return the rendered information about the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _renderInfo()
    {
        $id = $this->_mimepart->getMimeId();

        if (isset(self::$_statuscache[$id])) {
            return array(
                $id => array(
                    'data' => null,
                    'status' => self::$_statuscache[$id],
                    'type' => 'text/plain; charset=' . $this->getConfigParam('charset')
                )
            );
        } else {
            return array($id => null);
        }
    }

    /**
     * If this MIME part can contain embedded MIME part(s), and those part(s)
     * exist, return a representation of that data.
     *
     * @return mixed  A Horde_Mime_Part object representing the embedded data.
     *                Returns null if no embedded MIME part(s) exist.
     */
    protected function _getEmbeddedMimeParts()
    {
        $id = $this->_mimepart->getContentTypeParameter('id');
        $number = $this->_mimepart->getContentTypeParameter('number');
        $total = $this->_mimepart->getContentTypeParameter('total');

        if (is_null($id) || is_null($number) || is_null($total)) {
            return null;
        }

        /* Perform the search to find the other parts of the message. */
        $query = new Horde_Imap_Client_Search_Query();
        $query->headerText('Content-Type', $id);
        $indices = $this->getConfigParam('imp_contents')->getMailbox()->runSearchQuery($query);

        /* If not able to find the other parts of the message, prepare a
         * status message. */
        $msg_count = count($indices);
        if ($msg_count != $total) {
            $status = new IMP_Mime_Status(sprintf(_("Cannot display message - found only %s of %s parts of this message in the current mailbox."), $msg_count, $total));
            $status->action(IMP_Mime_Status::ERROR);
            self::$_statuscache[$this->_mimepart->getMimeId()] = $status;
            return null;
        }

        /* Get the contents of each of the parts. */
        $parts = array();
        foreach ($indices as $ob) {
            foreach ($ob->uids as $val) {
                /* No need to fetch the current part again. */
                if ($val == $number) {
                    $parts[$number] = $this->_mimepart->getContents();
                } else {
                    $ic = $GLOBALS['injector']->getInstance('IMP_Factory_Contents')->create($ob->mbox->getIndicesOb($val));
                    $parts[$ic->getMIMEMessage()->getContentTypeParameter('number')] = $ic->getBody();
                }
            }
        }

        /* Sort the parts in numerical order. */
        ksort($parts, SORT_NUMERIC);

        /* Combine the parts. */
        $mime_part = Horde_Mime_Part::parseMessage(implode('', $parts), array('forcemime' => true));

        return ($mime_part === false)
            ? null
            : $mime_part;
    }

}
