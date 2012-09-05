<?php
/**
 * Represents a MIME part with Kolab content.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Represents a MIME part with Kolab content.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Data_Object_Part
{
    /**
     * Embed the Kolab content into a new MIME Part.
     *
     * @param Horde_Kolab_Storage_Data_Object_Content $content The Kolab content.
     *
     * @return Horde_Mime_Part The MIME part that encapsules the Kolab content.
     */
    public function setContents(Horde_Kolab_Storage_Data_Object_Content $content)
    {
        $part = new Horde_Mime_Part();

        $part->setCharset('utf-8');
        $part->setDisposition('inline');
        $part->setDispositionParameter('x-kolab-type', 'xml');
        $part->setName('kolab.xml');

        $part->setType($content->getMimeType());
        $part->setContents(
            $content->toString(), array('encoding' => 'quoted-printable')
        );

        return $part;
    }
}