<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Interface for storage backends that support linked attachments.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
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
