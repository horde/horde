<?php
/**
 * The Horde_Mime_Viewer_Tnef class allows MS-TNEF attachments to be
 * displayed.
 *
 * Copyright 2002-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Mime_Viewer
 */
class Horde_Mime_Viewer_Tnef extends Horde_Mime_Viewer_Base
{
    /**
     * This driver's display capabilities.
     *
     * @var array
     */
    protected $_capability = array(
        'full' => false,
        'info' => false,
        'inline' => false,
        'raw' => false
    );

    /**
     * Metadata for the current viewer/data.
     *
     * @var array
     */
    protected $_metadata = array(
        'compressed' => true,
        'embedded' => true,
        'forceinline' => true
    );

    /**
     * Constructor.
     *
     * @param Horde_Mime_Part $mime_part  The object with the data to be
     *                                    rendered.
     * @param array $conf                 Configuration:
     * <pre>
     * 'tnef' - (Horde_Compress_Tnef) TNEF object.
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct(Horde_Mime_Part $part, array $conf = array())
    {
        parent::__construct($part, $conf);
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
        /* Get the data from the attachment. */
        try {
            if (!($tnef = $this->getConfigParam('tnef'))) {
                $tnef = Horde_Compress::factory('Tnef');
                $this->setConfigParam('tnef', $tnef);
            }
            $tnefData = $tnef->decompress($this->_mimepart->getContents());
        } catch (Horde_Compress_Exception $e) {
            $tnefData = array();
        }

        if (!count($tnefData)) {
            return null;
        }

        $mixed = new Horde_Mime_Part();
        $mixed->setType('multipart/mixed');

        reset($tnefData);
        while (list(,$data) = each($tnefData)) {
            $temp_part = new Horde_Mime_Part();
            $temp_part->setName($data['name']);
            $temp_part->setDescription($data['name']);
            $temp_part->setContents($data['stream']);
            $temp_part->setType($data['type'] . '/' . $data['subtype']);

            /* Short-circuit MIME-type guessing for winmail.dat parts;
             * we're showing enough entries for them already. */
            if (in_array($temp_part->getType(), array('application/octet-stream', 'application/base64'))) {
                $temp_part->setType(
                    Horde_Mime_Magic::filenameToMIME($data['name'])
                );
            }

            /* Set text parts to be displayed inline. */
            if ($temp_part->getPrimaryType() === 'text') {
                $temp_part->setDisposition('inline');
            }

            $mixed->addPart($temp_part);
        }

        return $mixed;
    }

}
