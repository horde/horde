<?php
/**
 * Copyright 2010-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * A Horde_Injector based IMP_Imap factory.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Factory_Imap
extends Horde_Core_Factory_Base
implements Horde_Shutdown_Task, SplObserver
{
    const BASE_OB = "base\0";

    /**
     * List of IMP_Imap instances.
     *
     * @var array
     */
    private $_instance = array();

    /**
     */
    public function __construct(Horde_Injector $injector)
    {
        parent::__construct($injector);

        Horde_Shutdown::add($this);
    }

    /**
     * Return the IMP_Imap instance for a given mailbox/identifier.
     *
     * @param string $id  Mailbox/identifier.
     *
     * @return IMP_Imap  IMP_Imap object.
     */
    public function create($id = null)
    {
        global $registry, $session;

        if (!is_null($id) &&
            ($registry->getAuth() !== false) &&
            ($base = $this->_injector->getInstance('IMP_Remote')->getRemoteById($id))) {
            $id = strval($base);
        } else {
            $base = null;
            $id = self::BASE_OB;
        }

        if (!isset($this->_instance[$id])) {
            $ob = null;
            try {
                $ob = $session->get('imp', 'imap_ob/' . $id);
            } catch (Exception $e) {
                // This indicates an unserialize() error.  This is fatal, so
                // logout.
                $failure = new Horde_Exception_AuthenticationFailure(
                    'Cached IMP session data has become invalid; expiring session.',
                    Horde_Auth::REASON_SESSION
                );
                $failure->application = 'imp';
                throw $failure;
            }

            if (!is_object($ob)) {
                $ob = (is_null($base))
                    ? new IMP_Imap($id)
                    : new IMP_Imap_Remote($id);
            }

            /* Attach IMAP alert handler. */
            if ($c_ob = $ob->client_ob) {
                $c_ob->alerts_ob->attach($this);
            }

            $this->_instance[$id] = $ob;
        }

        return $this->_instance[$id];
    }

    /**
     * Destroys an IMP_Imap instance.
     *
     * @param string $id  Mailbox/identifier.
     */
    public function destroy($id)
    {
        global $session;

        $id = strval($id);

        if ($id != self::BASE_OB) {
            $session->remove('imp', 'imap_ob/' . $id);
            unset($this->_instance[$id]);
        }
    }

    /**
     * Saves IMP_Imap instance to the session on shutdown.
     */
    public function shutdown()
    {
        global $registry, $session;

        if ($registry->getAuth() !== false) {
            foreach ($this->_instance as $key => $val) {
                if ($val->changed) {
                    $session->set('imp', 'imap_ob/' . $key, $val);
                }
            }
        }
    }

    /* SplObserver method */

    /**
     */
    public function update(SplSubject $subject)
    {
        global $notification;

        if ($subject instanceof Horde_Imap_Client_Base_Alerts) {
            $notification->push($subject->getLast()->alert, 'horde.warning');
        }
    }

}
