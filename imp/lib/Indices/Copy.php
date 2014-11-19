<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Abstract object implementing non-mail server copy actions.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
abstract class IMP_Indices_Copy
{
    /**
     * Copy/move messages.
     *
     * @param string $mbox          The mailbox name to copy/move the task to.
     * @param IMP_Indices $indices  An indices object.
     * @param boolean $move         Move if true, copy if false.
     *
     * @return boolean  True on success.
     */
    public function copy($mbox, IMP_Indices $indices, $move)
    {
        global $injector;

        $success = true;

        foreach ($indices as $ob) {
            foreach ($ob->uids as $uid) {
                /* Fetch the message contents. */
                $imp_contents = $injector->getInstance('IMP_Factory_Contents')->create($ob->mbox->getIndicesOb($uid));

                /* Fetch the message headers. */
                $subject = strval(
                    $imp_contents->getHeader()->getHeader('Subject')
                );

                /* Re-flow the message for prettier formatting. */
                $body_part = $imp_contents->getMimePart(
                    $imp_contents->findBody()
                );
                if (!$body_part) {
                    $success = false;
                    continue;
                }
                $flowed = new Horde_Text_Flowed($body_part->getContents());
                if ($body_part->getContentTypeParameter('delsp') == 'yes') {
                    $flowed->setDelSp(true);
                }
                $body = $flowed->toFlowed(false);

                /* Convert to current charset */
                /* TODO: When Horde_Icalendar supports setting of charsets
                 * we need to set it there instead of relying on the fact
                 * that both Nag and IMP use the same charset. */
                $body = Horde_String::convertCharset(
                    $body,
                    $body_part->getCharset(),
                    'UTF-8'
                );

                if (!$this->_create($mbox, $subject, $body)) {
                    $success = false;
                }
            }
        }

        /* Delete the original messages if this is a "move" operation. */
        if ($move) {
            $indices->delete();
        }

        return $success;
    }

    /**
     * Copy/move messages.
     *
     * @param string $mbox     The mailbox name to copy/move the task to.
     * @param string $subject  Subject.
     * @param string $body     Message body.
     *
     * @return boolean  True on success.
     */
    abstract protected function _create($mbox, $subject, $body);

    /**
     * Does the mailbox name match this action?
     *
     * @param string $mbox  The mailbox name.
     *
     * @return boolean  True if the mailbox matches this action.
     */
    abstract public function match($mbox);

}
