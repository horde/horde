<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   IMP
 */

/**
 * Interface for storage backends that support linked attachments.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   IMP
 */
interface IMP_Compose_Attachment_Linked
{
    /**
     * Retrieve the attachment's metadata.
     *
     * @return IMP_Compose_Attachment_Linked_Metadata  Metadata object.
     */
    public function getMetadata();

    /**
     * Save the attachment's metadata.
     *
     * @param IMP_Compose_Attachment_Linked_Metadata $md  Metadata object to
     *                                                    save. If null,
     *                                                    deletes entry.
     */
    public function saveMetadata($md = null);

}
