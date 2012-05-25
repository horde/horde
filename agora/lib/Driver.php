<?php
/**
 * Agora_Driver:: provides the functions to access both threads and
 * individual messages.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Marko Djukic <marko@oblo.com>
 * @author  Jan Schneider <jan@horde.org>
 * @author  Duck <duck@obala.net>
 * @package Agora
 */
class Agora_Driver {

    /**
     * Charset
     *
     * @var string
     */
    protected $_charset;

    /**
     * The database connection object.
     *
     * @var Horde_Db_Adapter
     */
    protected $_db;

    /**
     * The forums scope.
     *
     * @var string
     */
    protected $_scope;

    /**
     * Current forum data
     *
     * @var array
     */
    public $_forum;

    /**
     * Current forum ID
     *
     * @var string
     */
    public $_forum_id;

    /**
     * Scope theads table name
     *
     * @var string
     */
    protected $_threads_table = 'agora_messages';

    /**
     * Scope theads table name
     *
     * @var string
     */
    protected $_forums_table = 'agora_forums';

    /**
     * Cache object
     *
     * @var Horde_Cache
     */
    protected $_cache;

    /**
     * Constructor
     */
    public function __construct($scope, $params)
    {
        if (empty($params['db'])) {
            throw new InvalidArgumentException('Missing required connection parameter(s).');
        }

        /* Set parameters. */
        $this->_scope = $scope;
        $this->_db = $params['db'];
        $this->_charset = $params['charset'];

        /* Initialize the Cache object. */
        $this->_cache = $GLOBALS['injector']->getInstance('Horde_Cache');
    }

    /**
     * Checks if attachments are allowed in messages for the current forum.
     *
     * @return boolean  Whether attachments allowed or not.
     */
    public function allowAttachments()
    {
        return ($GLOBALS['conf']['forums']['enable_attachments'] == '1' ||
                ($GLOBALS['conf']['forums']['enable_attachments'] == '0' &&
                 $this->_forum['forum_attachments']));
    }

    /**
     * Saves the message.
     *
     * @param array $info  Array containing all the message data to save.
     *
     * @return mixed  Message ID on success or PEAR_Error on failure.
     * @throws Agora_Exception
     */
    public function saveMessage($info)
    {
        /* Check if the thread is locked before changing anything. */
        if ($info['message_parent_id'] &&
            $this->isThreadLocked($info['message_parent_id'])) {
            return PEAR::raiseError(_("This thread has been locked."));
        }

        /* Check post permissions. */
        if (!$this->hasPermission(Horde_Perms::EDIT)) {
            return PEAR::raiseError(sprintf(_("You don't have permission to post messages in forum %s."), $this->_forum_id));
        }

        if (empty($info['message_id'])) {
            /* Get thread parents */
            // TODO message_thread is always parent root, probably can use it here.
            if ($info['message_parent_id'] > 0) {
                $parents = $this->_db->selectValue('SELECT parents FROM ' . $this->_threads_table . ' WHERE message_id = ?',
                                                    array($info['message_parent_id']));
                $info['parents'] = $parents . ':' . $info['message_parent_id'];
                $info['message_thread'] = $this->getThreadRoot($info['message_parent_id']);
            } else {
                $info['parents'] = '';
                $info['message_thread'] = 0;
            }

            /* Create new message */
            $sql = 'INSERT INTO ' . $this->_threads_table
                . ' (forum_id, message_thread, parents, '
                . 'message_author, message_subject, body, attachments, '
                . 'message_timestamp, message_modifystamp, ip) '
                . ' VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?)';

            $author = $GLOBALS['registry']->getAuth() ? $GLOBALS['registry']->getAuth() : $info['posted_by'];
            $values = array($this->_forum_id,
                            $info['message_thread'],
                            $info['parents'],
                            $author,
                            $this->convertToDriver($info['message_subject']),
                            $this->convertToDriver($info['message_body']),
                            $_SERVER['REQUEST_TIME'],
                            $_SERVER['REQUEST_TIME'],
                            $_SERVER['REMOTE_ADDR']);

            try {
                $info['message_id'] = $this->_db->insert($sql, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Agora_Exception($e->getMessage());
            }

            /* Update last message in forum, but only if it is not moderated */
            if (!$this->_forum['forum_moderated']) {
                // Send the new post to the distribution address
                if ($this->_forum['forum_distribution_address']) {
                    Agora::distribute($info['message_id']);
                }
                /* Update cached message/thread counts and last poster */
                $this->_lastInForum($this->_forum_id, $info['message_id'], $author, $_SERVER['REQUEST_TIME']);
                $this->_forumSequence($this->_forum_id, 'message', '+');
                if ($info['message_thread']) {
                    $this->_sequence($info['message_thread'], '+');
                    $this->_lastInThread($info['message_thread'], $info['message_id'], $author, $_SERVER['REQUEST_TIME']);
                } else {
                    $this->_forumSequence($this->_forum_id, 'thread', '+');
                }
            }

        } else {
            // TODO clearing cache for editing doesn't work
            /* Update message data */
            $sql = 'UPDATE ' . $this->_threads_table . ' SET ' .
                   'message_subject = ?, body = ?, message_modifystamp = ? WHERE message_id = ?';
            $values = array($this->convertToDriver($info['message_subject']),
                            $this->convertToDriver($info['message_body']),
                            $_SERVER['REQUEST_TIME'],
                            $info['message_id']);

            try {
                $this->_db->execute($sql, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Agora_Exception($e->getMessage());
            }

            /* Get message thread for cache expiration */
            $info['message_thread'] = $this->getThreadRoot($info['message_id']);
        }

        /* Handle attachment saves or deletions. */
        if (!empty($info['message_attachment']) ||
            !empty($info['attachment_delete'])) {
            $vfs = Agora::getVFS();
            if ($vfs instanceof PEAR_Error) {
                return $vfs;
            }
            $vfs_dir = Agora::VFS_PATH . $this->_forum_id . '/' . $info['message_id'];

            /* Check if delete requested or new attachment loaded, and delete
             * any existing one. */
            if (!empty($info['attachment_delete'])) {
                $sql = 'SELECT file_id FROM agore_files WHERE message_id = ?';
                foreach ($this->_db->selectValues($sql, array($info['message_id'])) as $file_id) {
                    if ($vfs->exists($vfs_dir, $file_id)) {
                        $delete = $vfs->deleteFile($vfs_dir, $file_id);
                        if ($delete instanceof PEAR_Error) {
                            return $delete;
                        }
                    }
                }
                try {
                    $this->_db->execute('DELETE FROM agore_files WHERE message_id = ?', array($info['message_id']));
                } catch (Horde_Db_Exception $e) {
                    throw new Agora_Exception($e->getMessage());
                }
                $attachments = 0;
            }

            /* Save new attachment information. */
            if (!empty($info['message_attachment'])) {
                $file_sql = 'INSERT INTO agora_files (file_name, file_type, file_size, message_id) VALUES (?, ?, ?, ?)';
                $file_data = array($info['message_attachment']['name'],
                                   $info['message_attachment']['type'],
                                   $info['message_attachment']['size'],
                                   $info['message_id']);

                try {
                    $file_id = $this->_db->insert($file_sql, $file_data);
                } catch (Horde_Db_Exception $e) {
                    throw new Agora_Exception($e->getMessage());
                }

                $result = $vfs->write($vfs_dir, $file_id, $info['message_attachment']['file'], true);
                if ($result instanceof PEAR_Error) {
                    return $result;
                }
                $attachments = 1;
            }

            $sql = 'UPDATE ' . $this->_threads_table . ' SET attachments = ? WHERE message_id = ?';
            try {
                $this->_db->execute($sql, array($attachments, $info['message_id']));
            } catch (Horde_Db_Exception $e) {
                throw new Agora_Exception($e->getMessage());
            }
        }

        /* Update cache */
        $this->_updateCacheState($info['message_thread']);

        return $info['message_id'];
    }

    /**
     * Moves a thread to another forum.
     *
     * @todo Update the number of messages in the old/new forum
     *
     * @param integer $thread_id  The ID of the thread to move.
     * @param integer $forum_id   The ID of the destination forum.
     *
     * @throws Agora_Exception
     */
    public function moveThread($thread_id, $forum_id)
    {
        $sql = 'SELECT forum_id FROM ' . $this->_threads_table . ' WHERE message_id = ?';
        try {
            $old_forum = $this->_db->selectValue($sql, array($thread_id));
        } catch (Horde_Db_Exception $e) {
            throw new Agora_Exception($e->getMessage());
        }

        $sql = 'UPDATE ' . $this->_threads_table . ' SET forum_id = ? WHERE message_thread = ? OR message_id = ?';
        try {
            $this->_db->execute($sql, array($forum_id, $thread_id, $thread_id));
        } catch (Horde_Db_Exception $e) {
            throw new Agora_Exception($e->getMessage());
        }

        $this->_forumSequence($old_forum, 'thread', '-');
        $this->_forumSequence($forum_id, 'thread', '+');

        /* Update last message */
        $this->_lastInForum($old_forum);
        $this->_lastInForum($forum_id);

        /* Update cache */
        $this->_updateCacheState($thread_id);

        return true;
    }

    /**
     * Splits a thread on message id.
     *
     * @param integer $message_id  The ID of the message to split at.
     *
     * @throws Agora_Exception
     */
    public function splitThread($message_id)
    {
        $sql = 'SELECT message_thread FROM ' . $this->_threads_table . ' WHERE message_id = ?';
        try {
            $thread_id = $this->_db->selectValue($sql, array($message_id));
        } catch (Horde_Db_Exception $e) {
            throw new Agora_Exception($e->getMessage());
        }

        $sql = 'UPDATE ' . $this->_threads_table . ' SET message_thread = ?, parents = ? WHERE message_id = ?';
        try {
            $this->_db->execute($sql, array(0, '', $message_id));
        } catch (Horde_Db_Exception $e) {
            throw new Agora_Exception($e->getMessage());
        }

        $sql = 'SELECT message_thread, parents, message_id FROM ' . $this->_threads_table . ' WHERE parents LIKE ?';
        try {
            $children = $this->_db->selectAll($sql, array(":$thread_id:%$message_id%"));
        } catch (Horde_Db_Exception $e) {
            throw new Agora_Exception($e->getMessage());
        }

        if (!empty($children)) {
            $pos = strpos($children[0]['parents'], ':' . $message_id);
            foreach ($children as $i => $message) {
                $children[$i]['message_thread'] = (int)$message_id;
                $children[$i]['parents'] = substr($message['parents'], $pos);
            }

            $sql = 'UPDATE ' . $this->_threads_table . ' SET message_thread = ?, parents = ? WHERE message_id = ?';
            try {
                $this->_db->execute($sql, $children);
            } catch (Horde_Db_Exception $e) {
                throw new Agora_Exception($e->getMessage());
            }
        }

        /* Update count on old thread */
        $count = $this->countThreads($thread_id);
        $sql = 'UPDATE ' . $this->_threads_table . ' SET message_seq = ? WHERE message_id = ?';
        try {
            $this->_db->execute($sql, array($count, $thread_id));
        } catch (Horde_Db_Exception $e) {
            throw new Agora_Exception($e->getMessage());
        }

        /* Update count on new thread */
        $count = $this->countThreads($message_id);
        $sql = 'UPDATE ' . $this->_threads_table . ' SET message_seq = ? WHERE message_id = ?';
        try {
            $this->_db->execute($sql, array($count, $message_id));
        } catch (Horde_Db_Exception $e) {
            throw new Agora_Exception($e->getMessage());
        }

        /* Update last message */
        $this->_lastInForum($this->_forum_id);
        $this->_lastInThread($thread_id);
        $this->_lastInThread($message_id);

        $this->_forumSequence($this->_forum_id, 'thread', '+');

        /* Update cache */
        $this->_updateCacheState($thread_id);
    }

    /**
     * Merges two threads.
     *
     * @param integer $thread_id   The ID of the thread to merge.
     * @param integer $message_id  The ID of the message to merge to.
     *
     * @throws Agora_Exception
     */
    public function mergeThread($thread_from, $message_id)
    {
        $sql = 'SELECT message_thread, parents FROM ' . $this->_threads_table . ' WHERE message_id = ?';
        try {
            $destination = $this->_db->selectOne($sql, array($message_id));
        } catch (Horde_Db_Exception $e) {
            throw new Agora_Exception($e->getMessage());
        }

        /* Merge to the top level */
        if ($destination['message_thread'] == 0) {
            $destination['message_thread'] = $message_id;
        }

        $sql = 'SELECT message_thread, parents, message_id FROM ' . $this->_threads_table . ' WHERE message_id = ? OR message_thread = ?';
        try {
            $children = $this->_db->selectAll($sql, array($thread_from, $thread_from));
        } catch (Horde_Db_Exception $e) {
            throw new Agora_Exception($e->getMessage());
        }

        /* TODO: merging more than one message breaks parent/child relations,
         * also merging to deeper level than thread root doesn't work. */
        if (!empty($children)) {
            $sql = 'UPDATE ' . $this->_threads_table . ' SET message_thread = ?, parents = ? WHERE message_id = ?';

            foreach ($children as $i => $message) {
                $children[$i]['message_thread'] = $destination['message_thread'];
                if (!empty($destination['parents'])) {
                    $children[$i]['parents'] = $destination['parents'] . $message['parents'];
                } else {
                    $children[$i]['parents'] = ':' . $message_id;
                }

                try {
                    $this->_db->execute($sql, $children[$i]);
                } catch (Horde_Db_Exception $e) {
                    throw new Agora_Exception($e->getMessage());
                }
            }
        }

        $count = $this->countThreads($destination['message_thread']);
        $sql = 'UPDATE ' . $this->_threads_table . ' SET message_seq = ? WHERE message_id = ?';
        try {
            $this->_db->execute($sql, array($count, $destination['message_thread']));
        } catch (Horde_Db_Exception $e) {
            throw new Agora_Exception($e->getMessage());
        }

        /* Update last message */
        $this->_lastInForum($this->_forum_id);
        $this->_lastInThread($destination['message_thread']);

        $this->_forumSequence($this->_forum_id, 'thread', '-');

        /* Update cache */
        $this->_updateCacheState($destination['message_thread']);
    }

    /**
     * Fetches a message.
     *
     * @param integer $message_id  The ID of the message to fetch.
     *
     * @throws Horde_Exception_NotFound
     * @throws Agora_Exception
     */
    public function getMessage($message_id)
    {
        $message = $this->_cache->get('agora_msg' . $message_id, $GLOBALS['conf']['cache']['default_lifetime']);
        if ($message) {
            return unserialize($message);
        }

        $sql = 'SELECT message_id, forum_id, message_thread, parents, '
            . 'message_author, message_subject, body, message_seq, '
            . 'message_timestamp, view_count, locked, attachments FROM '
            . $this->_threads_table . ' WHERE message_id = ?';
        try {
            $message = $this->_db->selectOne($sql, array($message_id));
        } catch (Horde_Db_Exception $e) {
            throw new Agora_Exception($e->getMessage());
        }

        if (empty($message)) {
            throw new Horde_Exception_NotFound(sprintf(_("Message ID \"%d\" not found"), $message_id));
        }

        $message['message_subject'] = $this->convertFromDriver($message['message_subject']);
        $message['body'] = $this->convertFromDriver($message['body']);
        if ($message['message_thread'] == 0) {
            $message['message_thread'] = $message_id;
        }

        /* Is author a moderator? */
        if (isset($this->_forum['moderators']) &&
            in_array($message['message_author'], $this->_forum['moderators'])) {
            $message['message_author_moderator'] = 1;
        }

        $this->_cache->set('agora_msg' . $message_id, serialize($message));

        return $message;
    }

    /**
     * Returns a hash with all information necessary to reply to a message.
     *
     * @param mixed $message  The ID of the parent message to reply to, or arry of its data.
     *
     * @return array  A hash with all relevant information.
     * @throws Horde_Exception_NotFound
     * @throws Agora_Exception
     */
    public function replyMessage($message)
    {
        if (!is_array($message)) {
            $message = $this->getMessage($message);
        }

        /* Set up the form subject with the parent subject. */
        if (Horde_String::lower(Horde_String::substr($message['message_subject'], 0, 3)) != 're:') {
            $message['message_subject'] = 'Re: ' . $message['message_subject'];
        } else {
            $message['message_subject'] = $message['message_subject'];
        }

        /* Prepare the message quite body . */
        $message['body'] = sprintf(_("Posted by %s on %s"),
                                   htmlspecialchars($message['message_author']),
                                   strftime($GLOBALS['prefs']->getValue('date_format'), $message['message_timestamp']))
            . "\n-------------------------------------------------------\n"
            . $message['body'];
        $message['body'] = "\n> " . Horde_String::wrap($message['body'], 60, "\n> ");

        return $message;
    }

    /**
     * Deletes a message and all replies.
     *
     * @todo Detele all related attachments from VFS.
     *
     * @param integer $message_id  The ID of the message to delete.
     *
     * @return string  Thread ID on success.
     * @throws Agora_Exception
     */
    public function deleteMessage($message_id)
    {
        /* Check delete permissions. */
        if (!$this->hasPermission(Horde_Perms::DELETE)) {
            return PEAR::raiseError(sprintf(_("You don't have permission to delete messages in forum %s."), $this->_forum_id));
        }

        $sql = 'SELECT message_thread FROM ' . $this->_threads_table . ' WHERE message_id = ?';
        try {
            $thread_id = $this->_db->selectValue($sql, array($message_id));
        } catch (Horde_Db_Exception $e) {
            throw new Agora_Exception($e->getMessage());
        }

        $sql = 'DELETE FROM ' . $this->_threads_table . ' WHERE message_id = ' . (int)$message_id;
        if ($thread_id == 0) {
            $sql .= ' OR message_thread = ' . (int)$message_id;
        }

        try {
            $this->_db->execute($sql);
        } catch (Horde_Db_Exception $e) {
            throw new Agora_Exception($e->getMessage());
        }

        /* Update counts */
        // TODO message count is not correctly decreased after deleting more than one message.
        $this->_forumSequence($this->_forum_id, 'message', '-');
        if ($thread_id) {
            $this->_sequence($thread_id, '-');
        } else {
            $this->_forumSequence($this->_forum_id, 'thread', '-');
        }

        $this->_lastInForum($this->_forum_id);
        $this->_lastInThread($thread_id);

        /* Update cache */
        $this->_updateCacheState($thread_id);

        return $thread_id;
    }

    /**
     * Update lastMessage in a Forum
     *
     * @param integer $forum_id          Forum to update
     * @param integer $message_id        Last message id
     * @param string  $message_author    Last message author
     * @param integer $message_timestamp Last message timestamp
     *
     * @throws Agora_Exception
     */
    private function _lastInForum($forum_id, $message_id = 0, $message_author = '', $message_timestamp = 0)
    {
        /* Get the last message in form or thread - when managing threads */
        if ($message_id == 0) {
            $sql = $this->_db->addLimitOffset('SELECT message_id, message_author, message_timestamp FROM ' . $this->_threads_table
                . ' WHERE forum_id = ' . (int)$forum_id . ' ORDER BY message_id DESC', array('limit' => 1));
            try {
                $last = $this->_db->selectOne($sql);
            } catch (Horde_Db_Execution $e) {
                throw new Agora_Exception($e->getMessage());
            }
            if (!empty($last)) {
                extract($last);
            }
        }

        $sql = 'UPDATE ' . $this->_forums_table
            . ' SET last_message_id = ?, last_message_author = ?, last_message_timestamp = ? WHERE forum_id = ?';
        $values = array($message_id, $message_author, $message_timestamp, $forum_id);

        try {
            $this->_db->execute($sql, $values);
        } catch (Horde_Db_Execution $e) {
            throw new Agora_Exception($e->getMessage());
        }

        $this->_cache->expire('agora_forum_' . $forum_id, $GLOBALS['conf']['cache']['default_lifetime']);
    }

    /**
     * Update lastMessage in Thread
     *
     * @param integer $thread_id         Thread to update
     * @param integer $message_id        Last message id
     * @param string  $message_author    Last message author
     * @param integer $message_timestamp Last message timestamp
     *
     * @throws Agora_Exception
     */
    private function _lastInThread($thread_id, $message_id = 0, $message_author = '', $message_timestamp = 0)
    {
        /* Get the last message in form or thread - when managing threads */
        if ($message_id == 0) {
            $sql = $this->_db->addLimitOffset('SELECT message_id, message_author, message_timestamp FROM ' . $this->_threads_table
                . ' WHERE message_thread = ' . (int)$thread_id . ' ORDER BY message_id DESC', array('limit' => 1));
            try {
                $last = $this->_db->selectOne($sql);
            } catch (Horde_Db_Execution $e) {
                throw new Agora_Exception($e->getMessage());
            }
            if (!empty($last)) {
                extract($last);
            }
        }

        $sql = 'UPDATE ' . $this->_threads_table
            . ' SET last_message_id = ?, last_message_author = ?, message_modifystamp = ? WHERE message_id = ?';
        $values = array($message_id, $message_author, $message_timestamp, $thread_id);

        try {
            $this->_db->execute($sql, $values);
        } catch (Horde_Db_Execution $e) {
            throw new Agora_Exception($e->getMessage());
        }
    }

    /**
     * Increments or decrements a forum's message count.
     *
     * @param integer $forum_id     Forum to update
     * @param string  $type         What to increment message, thread or view.
     * @param integer|string $diff  Incremental or decremental step, either a
     *                              positive or negative integer, or a plus or
     *                              minus sign.
     */
    public function _forumSequence($forum_id, $type = 'message', $diff = '+')
    {
        $t = $type . '_count';
        $sql = 'UPDATE ' . $this->_forums_table . ' SET ' . $t . ' = ';

        switch ($diff) {
        case '+':
        case '-':
            $sql .= $t . ' ' . $diff . ' 1';
            break;

        default:
            $sql .= (int)$diff;
            break;
        }

        $sql .= ' WHERE forum_id = ' . (int)$forum_id;

        // TODO do we really need this return?
        return $this->_db->execute($sql);
    }

    /**
     * Increments or decrements a thread's message count.
     *
     * @param integer $thread_id    Thread to update.
     * @param integer|string $diff  Incremental or decremental step, either a
     *                              positive or negative integer, or a plus or
     *                              minus sign.
     */
    private function _sequence($thread_id, $diff = '+')
    {
        $sql = 'UPDATE ' . $this->_threads_table . ' SET message_seq = ';

        switch ($diff) {
        case '+':
        case '-':
            $sql .= 'message_seq ' . $diff . ' 1';
            break;

        default:
            $sql .= (int)$diff;
            break;
        }

        $sql .= ', message_modifystamp = ' . $_SERVER['REQUEST_TIME'] . '  WHERE message_id = ' . (int)$thread_id;
        // TODO do we really need this return?
        return $this->_db->execute($sql);
    }

    /**
     * Deletes an entire message thread.
     *
     * @param integer $thread_id  The ID of the thread to delete. If not
     *                            specified will delete all the threads for the
     *                            current forum.
     *
     * @throws Agora_Exception
     */
    public function deleteThread($thread_id = 0)
    {
        /* Check delete permissions. */
        if (!$this->hasPermission(Horde_Perms::DELETE)) {
            return PEAR::raiseError(sprintf(_("You don't have permission to delete messages in forum %s."), $this->_forum_id));
        }

        if ($thread_id > 0) {
            $sql = 'DELETE FROM ' . $this->_threads_table . ' WHERE message_thread = ' . (int)$thread_id;
            try {
                $this->_db->execute($sql);
            } catch (Horde_Db_Exception $e) {
                throw new Agora_Exception($e->getMessage());
            }

            $sql = 'SELECT COUNT(*) FROM ' . $this->_threads_table . ' WHERE forum_id = ' . (int)$this->_forum_id;
            $messages = $this->_db->selectValue($sql);

            $this->_forumSequence($this->_forum_id, 'thread', '-');
            $this->_forumSequence($this->_forum_id, 'message', $messages);

            /* Update cache */
            $this->_updateCacheState($thread_id);

        } else {
            $sql = 'DELETE FROM ' . $this->_threads_table . ' WHERE forum_id = ' . (int)$this->_forum_id;
            try {
                $this->_db->execute($sql);
            } catch (Horde_Db_Exception $e) {
                throw new Agora_Exception($e->getMessage());
            }

            $this->_forumSequence($this->_forum_id, 'thread', '0');
            $this->_forumSequence($this->_forum_id, 'message', '0');
        }

        /* Update last message */
        $this->_lastInForum($this->_forum_id);

        return true;
    }

    /**
     * Returns a list of threads.
     *
     * @param integer $thread_root   Message at which to start the thread.
     *                               If null get all forum threads
     * @param boolean $all_levels    Show all child levels or just one level.
     * @param string  $sort_by       The column by which to sort.
     * @param integer $sort_dir      The direction by which to sort:
     *                                   0 - ascending
     *                                   1 - descending
     * @param boolean $message_view
     * @param string  $link_back     A url to pass to the reply script which
     *                               will be returned to after an insertion of
     *                               a post. Useful in cases when this thread
     *                               view is used in blocks to return to the
     *                               original page rather than to Agora.
     * @param string  $base_url      An alternative URL where edit/delete links
     *                               point to. Mainly for api usage. Takes "%p"
     *                               as a placeholder for the parent message ID.
     * @param string  $from          The thread to start listing at.
     * @param string  $count         The number of threads to return.
     * @param boolean $nofollow      Whether to set the 'rel="nofollow"'
     *                               attribute on linked URLs in the messages.
     */
    public function getThreads($thread_root = 0,
                        $all_levels = false,
                        $sort_by = 'message_timestamp',
                        $sort_dir = 0,
                        $message_view = false,
                        $link_back = '',
                        $base_url = null,
                        $from = null,
                        $count = null,
                        $nofollow = false)
    {
        /* Check read permissions */
        if (!$this->hasPermission(Horde_Perms::SHOW)) {
            return PEAR::raiseError(sprintf(_("You don't have permission to read messages in forum %s."), $this->_forum_id));
        }

        /* Get messages data */
        $messages = $this->_getThreads($thread_root, $all_levels, $sort_by, $sort_dir, $message_view, $from, $count);
        if ($messages instanceof PEAR_Error || empty($messages)) {
            return $messages;
        }

        /* Moderators */
        if (isset($this->_forum['moderators'])) {
            $moderators = array_flip($this->_forum['moderators']);
        }

        /* Set up the base urls for actions. */
        $view_url = Horde::url('messages/index.php');
        if ($base_url) {
            $edit_url = $base_url;
            $del_url = Horde_Util::addParameter($base_url, 'delete', 'true');
        } else {
            $edit_url = Horde::url('messages/edit.php');
            $del_url = Horde::url('messages/delete.php');
        }

        // Get needed prefs
        $per_page = $GLOBALS['prefs']->getValue('thread_per_page');
        $view_bodies = $GLOBALS['prefs']->getValue('thread_view_bodies');
        $abuse_url = Horde::url('messages/abuse.php');
        $hot_img = Horde::img('hot.png', _("Hot thread"), array('title' => _("Hot thread")));
        $new_img = Horde::img('required.png', _("New posts"), array('title' => _("New posts")));
        $is_moderator = $this->hasPermission(Horde_Perms::DELETE);

        /* Loop through the threads and set up the array. */
        foreach ($messages as &$message) {

            /* Add attachment link */
            if ($message['attachments']) {
                $message['message_attachment'] = $this->getAttachmentLink($message['message_id']);
            }

            /* Get last message link */
            if ($thread_root == 0 && $message['last_message_id'] > 0) {
                $url = Agora::setAgoraId($message['forum_id'], $message['last_message_id'], $view_url, $this->_scope);
                $message['message_url'] = Horde::link($url);
                $last_timestamp = $message['last_message_timestamp'];
            } else {
                $last_timestamp = $message['message_timestamp'];
            }

            /* Check if thread is hot */
            if ($this->isHot($message['view_count'], $last_timestamp)) {
                $message['hot'] = $hot_img;
            }

            /* Check if has new posts since user last visit */
            if ($thread_root == 0 && $this->isNew($message['message_id'], $last_timestamp)) {
                $message['new'] = $new_img;
            }

            /* Mark moderators */
            if (isset($this->_forum['moderators']) && array_key_exists($message['message_author'], $moderators)) {
                $message['message_author_moderator'] = 1;
            }

            /* Link to view the message. */
            $url = Agora::setAgoraId($message['forum_id'], $message['message_id'], $view_url, $this->_scope);
            $message['link'] = Horde::link($url, $message['message_subject'], '', '', '', $message['message_subject']);

            /* Set up indenting for threads. */
            if ($sort_by != 'message_thread') {
                unset($message['indent'], $message['parent']);

                /* Links to pages */
                if ($thread_root == 0 && $message['message_seq'] > $per_page && $view_bodies == 2) {
                    $sub_pages = $message['message_seq'] / $per_page;
                    for ($i = 0; $i < $sub_pages; $i++) {
                        $page_title = sprintf(_("Page %d"), $i+1);
                        $message['pages'][] = Horde::link(Horde_Util::addParameter($url, 'thread_page', $i), $page_title, '', '', '', $page_title) . ($i+1) . '</a>';
                    }
                }
            }

            /* Button to post a reply to the message. */
            if (!$message['locked']) {
                if ($base_url) {
                    $url = $base_url;
                    if (strpos($url, '%p') !== false) {
                        $url = str_replace('%p', $message['message_id'], $url);
                    } else {
                        $url = Horde_Util::addParameter($url, 'message_parent_id', $message['message_id']);
                    }
                    if (!empty($link_back)) {
                        $url = Horde_Util::addParameter($url, 'url', $link_back);
                    }
                } else {
                    $url = Agora::setAgoraId($message['forum_id'], $message['message_id'], $view_url, $this->_scope);
                }
                $url = Horde_Util::addParameter($url, 'reply_focus', 1) . '#messageform';
                $message['reply'] = Horde::link($url, _("Reply to message"), '', '', '', _("Reply to message")) . _("Reply") . '</a>';
            }

            /* Link to edit the message. */
            if ($thread_root > 0 && isset($this->_forum['moderators'])) {
                $url = Agora::setAgoraId($message['forum_id'], $message['message_id'], $abuse_url);
                $message['actions'][] = Horde::link($url, _("Report as abuse")) . _("Report as abuse") . '</a>';
            }

            if ($is_moderator) {
                /* Link to edit the message. */
                $url = Agora::setAgoraId($message['forum_id'], $message['message_id'], $edit_url, $this->_scope);
                $message['actions'][] = Horde::link($url, _("Edit"), '', '', '', _("Edit message")) . _("Edit") . '</a>';

                /* Link to delete the message. */
                $url = Agora::setAgoraId($message['forum_id'], $message['message_id'], $del_url, $this->_scope);
                $message['actions'][] = Horde::link($url, _("Delete"), '', '', '', _("Delete message")) . _("Delete") . '</a>';

                /* Link to lock/unlock the message. */
                $url = Agora::setAgoraId($this->_forum_id, $message['message_id'], Horde::url('messages/lock.php'), $this->_scope);
                $label = ($message['locked']) ? _("Unlock") : _("Lock");
                $message['actions'][] = Horde::link($url, $label, '', '', '', $label) . $label . '</a>';

                /* Link to move thread to another forum. */
                if ($this->_scope == 'agora') {
                    if ($message['message_thread'] == $message['message_id']) {
                        $url = Agora::setAgoraId($this->_forum_id, $message['message_id'], Horde::url('messages/move.php'), $this->_scope);
                        $message['actions'][] = Horde::link($url, _("Move"), '', '', '', _("Move")) . _("Move") . '</a>';

                        /* Link to merge a message thred with anoter thread. */
                        $url = Agora::setAgoraId($this->_forum_id, $message['message_id'], Horde::url('messages/merge.php'), $this->_scope);
                        $message['actions'][] = Horde::link($url, _("Merge"), '', '', '', _("Merge")) . _("Merge") . '</a>';
                    } elseif ($message['message_thread'] != 0) {

                        /* Link to split thread to two threads, from this message after. */
                        $url = Agora::setAgoraId($this->_forum_id, $message['message_id'], Horde::url('messages/split.php'), $this->_scope);
                        $message['actions'][] = Horde::link($url, _("Split"), '', '', '', _("Split")) . _("Split") . '</a>';
                    }
                }
            }
        }

        return $messages;
    }

    /**
     * Formats a message body.
     *
     * @param string $messages  Messages to format
     * @param string $sort_by   List format order
     * @param boolean $format     Format messages body
     * @param integer $thread_root      Thread root
     */
    protected function _formatThreads($messages, $sort_by = 'message_modifystamp',
                            $format = false, $thread_root = 0)
    {
        /* Loop through the threads and set up the array. */
        foreach ($messages as &$message) {
            $message['message_author'] = htmlspecialchars($message['message_author']);
            $message['message_subject'] = htmlspecialchars($this->convertFromDriver($message['message_subject']));
            $message['message_date'] = $this->dateFormat($message['message_timestamp']);
            if ($format) {
                $message['body'] = $this->formatBody($this->convertFromDriver($message['body']));
            }

            /* If we are on the top, thread id is message itself. */
            if ($message['message_thread'] == 0) {
                $message['message_thread'] = $message['message_id'];
            }

            /* Get last message. */
            if ($thread_root == 0 && $message['last_message_id'] > 0) {
                $message['last_message_date'] = $this->dateFormat($message['last_message_timestamp']);
            }

            /* Set up indenting for threads. */
            if ($sort_by == 'message_thread') {
                $indent = explode(':', $message['parents']);
                $message['indent'] = count($indent) - 1;
                $last = array_pop($indent);

                /* TODO: this won't work because array_search doesn't search in
                 * multi-dimensional arrays.
                 *
                 * From what I see this is only needed because there is a bug in message
                 * deletion anyway. Parents should always be in array, because when
                 * deleting we should delete all sub-messages. We even state this in GUI,
                 * but we actually don't do it so.
                 *
                /*if (array_search($last, $messages) != 'message_id') {
                    $message['indent'] = 1;
                    $last = null;
                }*/
                $message['parent'] = $last ? $last : null;
            }
        }

        return $messages;
    }

    /**
     * Formats a message body.
     *
     * @param string $body           Text to format.
     */
    public function formatBody($body)
    {
        static $filters, $filters_params;

        if ($filters == null) {
            $filters = array('text2html', 'bbcode', 'highlightquotes', 'emoticons');
            $filters_params = array(array('parselevel' => Horde_Text_Filter_Text2html::MICRO),
                                    array(),
                                    array('citeblock' => true),
                                    array('entities' => true));

            // TODO: file doesn't exist anymore
            $config_dir = $GLOBALS['registry']->get('fileroot', 'agora') . '/config/';
            $config_file = 'words.php';
            if (file_exists($config_dir . $config_file)) {
                if (!empty($GLOBALS['conf']['vhosts'])) {
                    $v_file = substr($config_file, 0, -4) . '-' . $GLOBALS['conf']['server']['name'] . '.php';
                    if (file_exists($config_dir . $config_file)) {
                        $config_file = $v_file;
                    }
                }

                $filters[] = 'words';
                $filters_params[] = array('words_file' => $config_dir . $config_file,
                                        'replacement' => false);
            }
        }

        if (($hasBBcode = strpos($body, '[')) !== false &&
                strpos($body, '[/', $hasBBcode) !== false) {
            $filters_params[0]['parselevel'] = Horde_Text_Filter_Text2html::NOHTML;
        }

        return $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($body, $filters, $filters_params);
    }

    /**
     * Returns true if the message is hot.
     */
    public function isHot($views, $last_post)
    {
        if (!$GLOBALS['conf']['threads']['track_views']) {
            return false;
        }

        return ($views > $GLOBALS['prefs']->getValue('threads_hot')) && $last_post > ($_SERVER['REQUEST_TIME'] - 86400);
    }

    /**
     * Returns true, has new posts since user last visit
     */
    public function isNew($thread_id, $last_post)
    {
        if (!isset($_COOKIE['agora_viewed_threads']) ||
            ($pos1 = strpos($_COOKIE['agora_viewed_threads'], ':' . $thread_id . '|')) === false ||
            ($pos2 = strpos($_COOKIE['agora_viewed_threads'], '|', $pos1)) === false ||
             substr($_COOKIE['agora_viewed_threads'], $pos2+1, 10) > $last_post
            ) {
            return false;
        }

        return true;
    }

    /**
     * Fetches a list of messages awaiting moderation. Selects all messages,
     * irrespective of the thread root, which have the 'moderate' flag set in
     * the attributes.
     *
     * @param string  $sort_by   The column by which to sort.
     * @param integer $sort_dir  The direction by which to sort:
     *                           0 - ascending
     *                           1 - descending
     *
     * @throws Agora_Exception
     */
    public function getModerateList($sort_by, $sort_dir)
    {
        $sql = 'SELECT forum_id, forum_name FROM ' . $this->_forums_table . ' WHERE forum_moderated = ?';
        $values = array(1);

        /* Check permissions */
        if ($GLOBALS['registry']->isAdmin(array('permission' => 'agora:admin')) ||
            ($GLOBALS['injector']->getInstance('Horde_Perms')->exists('agora:forums:' . $this->_scope) &&
             $GLOBALS['injector']->getInstance('Horde_Perms')->hasPermission('agora:forums:' . $this->_scope, $GLOBALS['registry']->getAuth(), Horde_Perms::DELETE))) {
                $sql .= ' AND scope = ? ';
                $values[] = $this->_scope;
        } else {
            // Get only author forums
            $sql .= ' AND scope = ? AND author = ?';
            $values[] = $this->_scope;
            $values[] = $GLOBALS['registry']->getAuth();
        }

        /* Get moderate forums and their names */
        try {
            $forums_list = $this->_db->selectAssoc($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Agora_Exception($e->getMessage());
        }
        if (empty($forums_list)) {
            return $forums_list;
        }

        /* Get message waiting for approval */
        $sql = 'SELECT message_id, forum_id, message_subject, message_author, '
            . 'body, message_timestamp, attachments FROM ' . $this->_threads_table . ' WHERE forum_id IN ('
            . implode(',', array_keys($forums_list)) . ')'
            . ' AND approved = ? ORDER BY ' . $sort_by . ' '
            . ($sort_dir ? 'DESC' : 'ASC');

        try {
            $messages = $this->_db->selectAll($sql, array(0));
        } catch (Horde_Db_Exception $e) {
            throw new Agora_Exception($e->getMessage());
        }

        /* Loop through the messages and set up the array. */
        $approve_url = Horde_Util::addParameter(Horde::url('moderate.php'), 'approve', true);
        $del_url  = Horde::url('messages/delete.php');
        foreach ($messages as &$message) {
            $message['forum_name'] = $this->convertFromDriver($forums_list[$message['forum_id']]);
            $message['message_author'] = htmlspecialchars($message['message_author']);
            $message['message_subject'] = htmlspecialchars($this->convertFromDriver($message['message_subject']));
            $message['message_body'] = $GLOBALS['injector']
                ->getInstance('Horde_Core_Factory_TextFilter')
                ->filter($this->convertFromDriver($message['body']), 'highlightquotes');
            if ($message['attachments']) {
                $message['message_attachment'] = $this->getAttachmentLink($message['message_id']);
            }
            $message['message_date'] = $this->dateFormat($message['message_timestamp']);
        }

        return $messages;
    }

    /**
     * Get banned users from the current forum
     */
    public function getBanned()
    {
        $perm_name = 'agora:forums:' . $this->_scope . ':' . $this->_forum_id;
        if (!$GLOBALS['injector']->getInstance('Horde_Perms')->exists($perm_name)) {
            return array();
        }

        $forum_perm = $GLOBALS['injector']->getInstance('Horde_Perms')->getPermission($perm_name);
        if (!($forum_perm instanceof Horde_Perms_Permission)) {
            return $forum_perm;
        }

        $permissions = $forum_perm->getUserPermissions();
        if (empty($permissions)) {
            return $permissions;
        }

        /* Filter users moderators */
        $filter = Horde_Perms::EDIT | Horde_Perms::DELETE;
        foreach ($permissions as $user => $level) {
            if ($level & $filter) {
                unset($permissions[$user]);
            }
        }

        return $permissions;
    }

    /**
     * Ban user on a specific forum.
     *
     * @param string  $user      Moderator username.
     * @param integer $forum_id  Forum to add moderator to.
     * @param string  $action    Action to peform ('add' or 'delete').
     */
    public function updateBan($user, $forum_id = null, $action = 'add')
    {
        if ($forum_id == null) {
            $forum_id = $this->_forum_id;
        }

        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');
        $perm_name = 'agora:forums:' . $this->_scope . ':' . $forum_id;
        if (!$perms->exists($perm_name)) {
            $forum_perm = $GLOBALS['injector']
                ->getInstance('Horde_Core_Perms')
                ->newPermission($perm_name);
            $perms->addPermission($forum_perm);
        } else {
            $forum_perm = $perms->getPermission($perm_name);
            if ($forum_perm instanceof PEAR_Error) {
                return $forum_perm;
            }
        }

        if ($action == 'add') {
            // Allow to only read posts
            $forum_perm->removeUserPermission($user, Horde_Perms::ALL, true);
            $forum_perm->addUserPermission($user, Horde_Perms::READ, true);
        } else {
            // Remove all acces to user
            $forum_perm->removeUserPermission($user, Horde_Perms::ALL, true);
        }

        return true;
    }

    /**
     * Updates forum moderators.
     *
     * @param string  $moderator  Moderator username.
     * @param integer $forum_id   Forum to add moderator to.
     * @param string  $action     Action to peform ('add' or 'delete').
     *
     * @throws Agora_Exception
     */
    public function updateModerator($moderator, $forum_id = null, $action = 'add')
    {
        if ($forum_id == null) {
            $forum_id = $this->_forum_id;
        }

        switch ($action) {
        case 'add':
            $sql = 'INSERT INTO agora_moderators (forum_id, horde_uid) VALUES (?, ?)';
            break;

        case 'delete':
            $sql = 'DELETE FROM agora_moderators WHERE forum_id = ? AND horde_uid = ?';
            break;
        }

        try {
            $this->_db->execute($sql, array($forum_id, $moderator));
        } catch (Horde_Db_Exception $e) {
            throw new Agora_Exception($e->getMessage());
        }

        /* Update permissions */
        $perm_name = 'agora:forums:' . $this->_scope . ':' . $forum_id;
        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');
        if (!$perms->exists($perm_name)) {
            $forum_perm = $GLOBALS['injector']
                ->getInstance('Horde_Core_Perms')
                ->newPermission($perm_name);
            $perms->addPermission($forum_perm);
        } else {
            $forum_perm = $perms->getPermission($perm_name);
            if ($forum_perm instanceof PEAR_Error) {
                return $forum_perm;
            }
        }

        switch ($action) {
        case 'add':
            $forum_perm->addUserPermission($moderator, Horde_Perms::DELETE, true);
            break;

        case 'delete':
            $forum_perm->removeUserPermission($moderator, Horde_Perms::DELETE, true);
            break;
        }

        $this->_cache->expire('agora_forum_' . $forum_id, $GLOBALS['conf']['cache']['default_lifetime']);
    }

    /**
     * Approves one or more ids.
     *
     * @param string $action  Whether to 'approve' or 'delete' messages.
     * @param array $ids      Array of message IDs.
     *
     * @throws Agora_Exception
     */
    public function moderate($action, $ids)
    {
        switch ($action) {
        case 'approve':

            /* Get message thread to expire cache */
            $sql = 'SELECT message_thread FROM ' . $this->_threads_table
                    . ' WHERE message_id IN (' . implode(',', $ids) . ')';
            try {
                $threads = $this->_db->selectValues($sql);
            } catch (Horde_Db_Exception $e) {
                throw new Agora_Exception($e->getMessage());
            }
            $this->_updateCacheState($threads);

            $sql = 'UPDATE ' . $this->_threads_table . ' SET approved = 1'
                 . ' WHERE message_id IN (' . implode(',', $ids) . ')';
            try {
                $this->_db->execute($sql);
            } catch (Horde_Db_Exception $e) {
                throw new Agora_Exception($e->getMessage());
            }

            /* Save original forum_id for later resetting */
            $orig_forum_id = $this->_forum_id;
            foreach ($ids as $message_id) {
                /* Update cached message and thread counts */
                $message = $this->getMessage($message_id);
                $this->_forum_id = $message['forum_id'];

                /* Update cached last poster */
                $this->_lastInForum($this->_forum_id);
                $this->_forumSequence($this->_forum_id, 'message', '+');
                if (!empty($message['parents'])) {
                    $this->_sequence($message['message_thread'], '+');
                    $this->_lastInThread($message['message_thread'], $message_id, $message['message_author'], $_SERVER['REQUEST_TIME']);
                } else {
                    $this->_forumSequence($this->_forum_id, 'thread', '+');
                }

                /* Send the new post to the distribution address */
                Agora::distribute($message_id);
            }

            /* Restore original forum ID */
            $this->_forum_id = $orig_forum_id;
            break;

        case 'delete':
            foreach ($ids as $id) {
                $this->deleteMessage($id);
            }
            break;
        }
    }

    /**
     * Returns the number of replies on a thread, or threads in a forum
     *
     * @param integer $thread_root  Thread to count.
     *
     * @return integer  The number of messages in thread or PEAR_Error on
     *                  failure.
     */
    public function countThreads($thread_root = 0)
    {
        $sql = 'SELECT COUNT(*) FROM ' . $this->_threads_table . ' WHERE message_thread = ?';
        if ($thread_root) {
            return $this->_db->selectValue($sql, array($thread_root));
        } else {
            return $this->_db->selectValue($sql . ' AND forum_id = ?', array(0, $this->_forum_id));
        }
    }

    /**
     * Returns the number of all messages (threads and replies) in a forum
     *
     * @return integer  The number of messages in forum or PEAR_Error on
     *                  failure.
     */
    public function countMessages()
    {
        $sql = 'SELECT COUNT(*) FROM ' . $this->_threads_table . ' WHERE forum_id = ?';
        return $this->_db->selectValue($sql, array($this->_forum_id));
    }

    /**
     * Returns a table showing the specified message list.
     *
     * @param array $threads         A hash with the thread messages as
     *                               returned by {@link
     *                               Agora_Driver::getThreads}.
     * @param array $col_headers     A hash with the column headers.
     * @param boolean $bodies        Display the message bodies?
     * @param string $template_file  Template to use.
     *
     * @return string  The rendered message table.
     */
    public function getThreadsUi($threads, $col_headers, $bodies = false,
                                 $template_file = false)
    {
        if (!count($threads)) {
            return '';
        }

        /* Render threaded lists with Horde_Tree. */
        if (!$template_file && isset($threads[0]['indent'])) {
            $tree = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Tree')->create('threads', 'Html', array(
                'multiline' => $bodies,
                'lines' => !$bodies
            ));

            $tree->setHeader(array(
                array(
                    'class' => $col_headers['message_thread_class_plain'],
                    'html' => '<strong>' . $col_headers['message_thread'] . '</strong>'
                ),
                array(
                    'class' => $col_headers['message_author_class_plain'],
                    'html' => '<strong>' . $col_headers['message_author'] . '</strong>'
                ),
                array(
                    'class' => $col_headers['message_timestamp_class_plain'],
                    'html' => '<strong>' . $col_headers['message_timestamp'] . '</strong>'
                )
            ));

            foreach ($threads as &$thread) {
                if ($bodies) {
                    $text = '<strong>' . $thread['message_subject'] . '</strong><small>[';
                    if (isset($thread['reply'])) {
                        $text .= ' ' . $thread['reply'];
                    }
                    if (!empty($thread['actions'])) {
                        $text .= ', ' . implode(', ', $thread['actions']);
                    }
                    $text .= ']</small><br />' .
                        str_replace(array("\r", "\n"), '', $thread['body'] . ((isset($thread['message_attachment'])) ? $thread['message_attachment'] : ''));
                } else {
                    $text = '<strong>' . $thread['link'] . $thread['message_subject'] . '</a></strong> ';
                    if (isset($thread['actions'])) {
                        $text .= '<small>[' . implode(', ', $thread['actions']) . ']</small>';
                    }
                }

                $tree->addNode(array(
                    'id' => $thread['message_id'],
                    'parent' => $thread['parent'],
                    'label' => $text,
                    'params' => array(
                        'class' => 'linedRow',
                        'icon' => false
                    ),
                    'right' => array(
                        $thread['message_author'],
                        $thread['message_date']
                    )
                ));
            }

            return $tree->getTree(true);
        }

        /* Set up the thread template tags. */
        $view = new Agora_View();
        $view->threads_list = $threads;
        $view->col_headers = $col_headers;
        $view->thread_view_bodies = $bodies;

        /* Render template. */
        if (!$template_file) {
            $template_file = 'messages/threads';
        }

        return $view->render($template_file);
    }

    /**
     * @throws Agora_Exception
     */
    public function getThreadRoot($message_id)
    {
        $sql = 'SELECT message_thread FROM ' . $this->_threads_table . ' WHERE message_id = ?';
        try {
            $thread_id = $this->_db->selectValue($sql, array($message_id));
        } catch (Horde_Db_Exception $e) {
            throw new Agora_Exception($e->getMessage());
        }
        return $thread_id ? $thread_id : $message_id;
    }

    /**
     */
    public function setThreadLock($message_id, $lock)
    {
        $sql = 'UPDATE ' . $this->_threads_table . ' SET locked = ? WHERE message_id = ? OR message_thread = ?';
        $values = array($lock, $message_id, $message_id);
        return $this->_db->execute($sql, $values);
    }

    /**
     * @return boolean
     */
    public function isThreadLocked($message_id)
    {
        $sql = 'SELECT message_thread FROM ' . $this->_threads_table . ' WHERE message_id = ?';
        $thread = $this->_db->selectValue($sql, array($message_id));

        return $this->_db->selectValue('SELECT locked FROM ' . $this->_threads_table . ' WHERE message_id = ?', array($thread));
    }

    /**
     */
    public function getThreadActions()
    {
        /* Actions. */
        $actions = array();

        $url = Agora::setAgoraId($this->_forum_id, null, Horde::url('messages/edit.php'));
        if ($this->hasPermission(Horde_Perms::EDIT)) {
            $actions[] = array('url' => $url, 'label' => _("Post message"));
        }

        if ($this->hasPermission(Horde_Perms::DELETE)) {
            if ($this->_scope == 'agora') {
                $url = Agora::setAgoraId($this->_forum_id, null, Horde::url('editforum.php'));
                $actions[] = array('url' => $url, 'label' => _("Edit Forum"));
            }
            $url = Agora::setAgoraId($this->_forum_id, null, Horde::url('deleteforum.php'), $this->_scope);
            $actions[] = array('url' => $url, 'label' => _("Delete Forum"));
            $url = Agora::setAgoraId($this->_forum_id, null, Horde::url('ban.php'), $this->_scope);
            $actions[] = array('url' => $url, 'label' => _("Ban"));
        }

        return $actions;
    }

    /**
     */
    public function getForm($vars, $title, $editing = false, $new_forum = false)
    {
        global $conf;

        $form = new Agora_Form_Message($vars, $title);
        $form->setButtons($editing ? _("Save") : _("Post"));
        $form->addHidden('', 'url', 'text', false);

        /* Figure out what to do with forum IDs. */
        if ($new_forum) {
            /* This is a new forum to be created, create the var to hold the
             * full path for the new forum. */
            $form->addHidden('', 'new_forum', 'text', false);
        } else {
            /* This is an existing forum so create the forum ID variable. */
            $form->addHidden('', 'forum_id', 'int', false);
        }

        $form->addHidden('', 'scope', 'text', false);
        $form->addHidden('', 'message_id', 'int', false);
        $form->addHidden('', 'message_parent_id', 'int', false);

        if (!$GLOBALS['registry']->getAuth()) {
            $form->addVariable(_("From"), 'posted_by', 'text', true);
        }

        /* We are replaying, so display the quote button */
        if ($vars->get('message_parent_id')) {
            $desc = '<input type="button" value="' . _("Quote") . '" class="button" '
                  . 'onClick="this.form.message_body.value=this.form.message_body.value + this.form.message_body_old.value; this.form.message_body_old.value = \'\';" />';
            $form->addVariable(_("Subject"), 'message_subject', 'text', true, false, $desc);
            $form->addHidden('', 'message_body_old', 'longtext', false);
        } else {
            $form->addVariable(_("Subject"), 'message_subject', 'text', true);
        }

        $form->addVariable(_("Message"), 'message_body', 'longtext', true);

        /* Check if an attachment is available and set variables for deleting
         * and previewing. */
        if ($vars->get('attachment_preview')) {
            $form->addVariable(_("Delete the existing attachment?"), 'attachment_delete', 'boolean', false);
            $form->addVariable(_("Current attachment"), 'attachment_preview', 'html', false);
        }

        if ($this->allowAttachments()) {
            $form->addVariable(_("Attachment"), 'message_attachment', 'file', false);
        }

        if (!empty($conf['forums']['captcha']) && !$GLOBALS['registry']->getAuth()) {
            $form->addVariable(_("Spam protection"), 'captcha', 'figlet', true, null, null, array(Agora::getCAPTCHA(!$form->isSubmitted()), $conf['forums']['figlet_font']));
        }

        return $form;
    }

    /**
     * Formats time according to user preferences.
     *
     * @param int $timestamp  Message timestamp.
     *
     * @return string  Formatted date.
     */
    public function dateFormat($timestamp)
    {
        return strftime($GLOBALS['prefs']->getValue('date_format'), $timestamp)
            . ' '
            . (date($GLOBALS['prefs']->getValue('twentyFour') ? 'G:i' : 'g:ia', $timestamp));
    }

    /**
     * Logs a message view.
     *
     * @return boolean True, if the view was logged, false if the message was aleredy seen
     * @throws Agora_Exception
     */
    public function logView($thread_id)
    {
        if (!$GLOBALS['conf']['threads']['track_views']) {
            return false;
        }

        if ($GLOBALS['browser']->isRobot()) {
            return false;
        }

        /* We already read this thread? */
        if (isset($_COOKIE['agora_viewed_threads']) &&
            strpos($_COOKIE['agora_viewed_threads'], ':' . $thread_id . '|') !== false) {
            return false;
        }

        /* Rembember when we see a thread */
        if (!isset($_COOKIE['agora_viewed_threads'])) {
            $_COOKIE['agora_viewed_threads'] = ':';
        }
        $_COOKIE['agora_viewed_threads'] .= $thread_id . '|' . $_SERVER['REQUEST_TIME'] . ':';

        setcookie('agora_viewed_threads', $_COOKIE['agora_viewed_threads'], $_SERVER['REQUEST_TIME']+22896000,
                    $GLOBALS['conf']['cookie']['path'], $GLOBALS['conf']['cookie']['domain'],
                    $GLOBALS['conf']['use_ssl'] == 1 ? 1 : 0);

        /* Update the count */
        $sql = 'UPDATE ' . $this->_threads_table . ' SET view_count = view_count + 1 WHERE message_id = ?';
        try {
            $this->_db->execute($sql, array($thread_id));
        } catch (Horde_Db_Exception $e) {
            throw new Agora_Exception($e->getMessage());
        }

        return true;
    }

    /**
     * Constructs message attachments link.
     *
     * @throws Agora_Exception
     */
    public function getAttachmentLink($message_id)
    {
        if (!$this->allowAttachments()) {
            return '';
        }

        $sql = 'SELECT file_id, file_name, file_size, file_type FROM agora_files WHERE message_id = ?';
        try {
            $files = $this->_db->selectAll($sql, array($message_id));
        } catch (Horde_Db_Exception $e) {
            throw new Agora_Exception($e->getMessage());
        }
        if (empty($files)) {
            return $files;
        }

        /* Constuct the link with a tooltip for further info on the download. */
        $html = '<br />';
        $view_url = Horde::url('view.php');
        foreach ($files as $file) {
            $mime_icon = $GLOBALS['injector']->getInstance('Horde_Core_Factory_MimeViewer')->getIcon($file['file_type']);
            $title = _("download") . ': ' . $file['file_name'];
            $tooltip = $title . "\n" . sprintf(_("size: %s"), $this->formatSize($file['file_size'])) . "\n" . sprintf(_("type: %s"), $file['file_type']);
            $url = Horde_Util::addParameter($view_url, array('forum_id' => $this->_forum_id,
                                                       'message_id' => $message_id,
                                                       'file_id' => $file['file_id'],
                                                       'file_name' => $file['file_name'],
                                                       'file_type' => $file['file_type']));
            $html .= Horde::linkTooltip($url, $title, '', '', '', $tooltip) .
                     Horde::img($mime_icon, $title, 'align="middle"', '') . '&nbsp;' . $file['file_name'] . '</a>&nbsp;&nbsp;<br />';
        }

        return $html;
    }

    /**
     * Formats file size.
     *
     * @param int $filesize
     *
     * @return string  Formatted filesize.
     */
    public function formatSize($filesize)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $pass = 0; // set zero, for Bytes
        while($filesize >= 1024) {
            $filesize /= 1024;
            $pass++;
        }

        return round($filesize, 2) . ' ' . $units[$pass];
    }


    /**
     * Fetches a forum data.
     *
     * @param integer $forum_id  The ID of the forum to fetch.
     *
     * @return array  The forum hash or a PEAR_Error on failure.
     * @throws Horde_Exception_NotFound
     * @throws Agora_Exception
     */
    public function getForum($forum_id = 0)
    {
        if (!$forum_id) {
            $forum_id = $this->_forum_id;
        } elseif ($forum_id instanceof PEAR_Error) {
            return $forum_id;
        }

        // Make the requested forum the current forum
        $this->_forum_id = $forum_id;

        /* Check if we can read messages in this forum */
        if (!$this->hasPermission(Horde_Perms::SHOW, $forum_id)) {
            return PEAR::raiseError(sprintf(_("You don't have permission to access messages in forum %s."), $forum_id));
        }

        $forum = $this->_cache->get('agora_forum_' . $forum_id, $GLOBALS['conf']['cache']['default_lifetime']);
        if ($forum) {
            return unserialize($forum);
        }

        $sql = 'SELECT forum_id, forum_name, scope, active, forum_description, '
            . 'forum_parent_id, forum_moderated, forum_attachments, '
            . 'forum_distribution_address, author, message_count, thread_count '
            . 'FROM ' . $this->_forums_table . ' WHERE forum_id = ?';
        try {
            $forum = $this->_db->selectOne($sql, array($forum_id));
        } catch (Horde_Db_Exception $e) {
            throw new Agora_Exception($e->getMessage());
        }
        if (empty($forum)) {
            throw new Horde_Exception_NotFound(sprintf(_("Forum %s does not exist."), $forum_id));
        }

        $forum['forum_name'] = $this->convertFromDriver($forum['forum_name']);
        $forum['forum_description'] = $this->convertFromDriver($forum['forum_description']);
        $forum['forum_distribution_address'] = $this->convertFromDriver($forum['forum_distribution_address']);

        /* Get moderators */
        $sql = 'SELECT horde_uid FROM agora_moderators WHERE forum_id = ?';
        try {
            $moderators = $this->_db->selectValues($sql, array($forum_id));
        } catch (Horde_Db_Exception $e) {
            throw new Agora_Exception($e->getMessage());
        }
        if (!empty($moderators)) {
            $forum['moderators'] = $moderators;
        }

        $this->_cache->set('agora_forum_' . $forum_id, serialize($forum));

        return $forum;
    }

    /**
     * Returns the number of forums.
     */
    public function countForums()
    {
        $sql = 'SELECT COUNT(*) FROM ' . $this->_forums_table . ' WHERE active = ? AND scope = ?';
        return $this->_db->selectValue($sql, array(1, $this->_scope));
    }

    /**
     * Fetches a list of forums.
     *
     * @todo This function needs refactoring, as it doesn't return consistent
     * results. For example when running with $formatted = false it will return
     * an indexed array, but when running with $formatted = true the result is
     * associative array.
     *
     * @param integer $root_forum  The first level forum.
     * @param boolean $formatted   Whether to return the list formatted or raw.
     * @param string  $sort_by     The column to sort by.
     * @param integer $sort_dir    Sort direction, 0 = ascending,
     *                             1 = descending.
     * @param boolean $add_scope   Add parent forum if forum for another
     *                             scopelication.
     * @param string  $from        The forum to start listing at.
     * @param string  $count       The number of forums to return.
     *
     * @return mixed  An array of forums or PEAR_Error on failure.
     * @throws Agora_Exception
     */
    public function getForums($root_forum = 0, $formatted = true,
                       $sort_by = 'forum_name', $sort_dir = 0,
                       $add_scope = false, $from = 0, $count = 0)
    {
        /* Get messages data */
        $forums = $this->_getForums($root_forum, $formatted, $sort_by,
                                    $sort_dir, $add_scope, $from, $count);
        if ($forums instanceof PEAR_Error || empty($forums) || !$formatted) {
            return $forums;
        }

        $user = $GLOBALS['registry']->getAuth();
        $edit_url =  Horde::url('messages/edit.php');
        $editforum_url =  Horde::url('editforum.php');
        $delete_url = Horde::url('deleteforum.php');

        foreach ($forums as $key => &$forum) {
            if (!$this->hasPermission(Horde_Perms::SHOW, $forum['forum_id'], $forum['scope'])) {
                unset($forums[$key]);
                continue;
            }

            $forum['indentn'] =  0;
            $forum['indent'] = '';
            if (!$this->hasPermission(Horde_Perms::READ, $forum['forum_id'], $forum['scope'])) {
                continue;
            }

            $forum['url'] = Agora::setAgoraId($forum['forum_id'], null, Horde::url('threads.php'), $forum['scope'], true);
            $forum['message_count'] = number_format($forum['message_count']);
            $forum['thread_count'] = number_format($forum['thread_count']);

            if ($forum['last_message_id']) {
                $forum['last_message_date'] = $this->dateFormat($forum['last_message_timestamp']);
                $forum['last_message_url'] = Agora::setAgoraId($forum['forum_id'], $forum['last_message_id'], Horde::url('messages/index.php'), $forum['scope'], true);
            }

            $forum['actions'] = array();

            /* Post message button. */
            if ($this->hasPermission(Horde_Perms::EDIT, $forum['forum_id'], $forum['scope'])) {
                /* New Post forum button. */
                $url = Agora::setAgoraId($forum['forum_id'], null, $edit_url, $forum['scope'], true);
                $forum['actions'][] = Horde::link($url, _("Post message")) . _("New Post") . '</a>';

                if ($GLOBALS['registry']->isAdmin(array('permission' => 'agora:admin'))) {
                    /* Edit forum button. */
                    $url = Agora::setAgoraId($forum['forum_id'], null, $editforum_url, $forum['scope'], true);
                    $forum['actions'][] = Horde::link($url, _("Edit forum")) . _("Edit") . '</a>';
                }
            }

            if ($GLOBALS['registry']->isAdmin(array('permission' => 'agora:admin'))) {
                /* Delete forum button. */
                $url = Agora::setAgoraId($forum['forum_id'], null, $delete_url, $forum['scope'], true);
                $forum['actions'][] = Horde::link($url, _("Delete forum")) . _("Delete") . '</a>';
            }

            /* User is a moderator */
            if (isset($forum['moderators']) && in_array($user, $forum['moderators'])) {
                $sql = 'SELECT COUNT(forum_id) FROM ' . $this->_threads_table
                    . ' WHERE forum_id = ? AND approved = ?'
                    . ' GROUP BY forum_id';
                try {
                    $unapproved = $this->_db->selectValue($sql, array($forum['forum_id'], 0));
                } catch (Horde_Db_Exception $e) {
                    throw new Agora_Exception($e->getMessage());
                }

                $url = Horde::link(Horde::url('moderate.php', true), _("Moderate")) . _("Moderate") . '</a>';
                $forum['actions'][] = $url . ' (' . $unapproved . ')' ;
            }
        }

        return $forums;
    }

    /**
     * Fetches a list of forums.
     *
     * @param integer $root_forum  The first level forum.
     * @param boolean $formatted   Whether to return the list formatted or raw.
     * @param string  $sort_by     The column to sort by.
     * @param integer $sort_dir    Sort direction, 0 = ascending,
     *                             1 = descending.
     * @param boolean $add_scope   Add parent forum if forum for another
     *                             scopelication.
     * @param string  $from        The forum to start listing at.
     * @param string  $count       The number of forums to return.
     *
     * @return mixed  An array of forums or PEAR_Error on failure.
     */
    protected function _getForums($root_forum = 0, $formatted = true,
                        $sort_by = 'forum_name', $sort_dir = 0,
                        $add_scope = false,  $from = 0, $count = 0)
    {
        return array();
    }

    /**
     * Fetches a list of forums.
     *
     * @param integer $forums      Forums to format
     *
     * @return array  An array of forums.
     * @throws Agora_Exception
     */
    protected function _formatForums($forums)
    {
        /* Get moderators */
        foreach ($forums as $forum) {
            $forums_list[] = $forum['forum_id'];
        }
        $sql = 'SELECT forum_id, horde_uid'
            . ' FROM agora_moderators WHERE forum_id IN (' . implode(',', array_values($forums_list)) . ')';
        try {
            $moderators = $this->_db->selectAll($sql);
        } catch (Horde_Db_Exception $e) {
            throw new Agora_Exception($e->getMessage());
        }

        foreach ($forums as $key => $forum) {
            $forums[$key]['forum_name'] = $this->convertFromDriver($forums[$key]['forum_name']);
            $forums[$key]['forum_description'] = $this->convertFromDriver($forums[$key]['forum_description']);
            foreach ($moderators as $moderator) {
                if ($moderator['forum_id'] == $forum['forum_id']) {
                    $forums[$key]['moderators'][] = $moderator['horde_uid'];
                }
            }
        }

        return $forums;
    }

    /**
     * Get forums ids and titles
     *
     * @return array  An array of forums and form names.
     */
    public function getBareForums()
    {
        return array();
    }

    /**
     * Creates a new forum.
     *
     * @param string $forum_name  Forum name.
     * @param string $forum_owner Forum owner.
     *
     * @return integer ID of the new generated forum.
     * @throws Agora_Exception
     */
    public function newForum($forum_name, $owner)
    {
        if (empty($forum_name)) {
            throw new Agora_Exception(_("Cannot create a forum with an empty name."));
        }

        $sql = 'INSERT INTO ' . $this->_forums_table . ' (scope, forum_name, active, author) VALUES (?, ?, ?, ?)';
        $values = array($this->_scope, $this->convertToDriver($forum_name), 1, $owner);
        try {
            $forum_id = $this->_db->insert($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Agora_Exception($e->getMessage());
        }

        return $forum_id;
    }

    /**
     * Saves a forum, either creating one if no forum ID is given or updating
     * an existing one.
     *
     * @param array $info  The forum information to save consisting of:
     *                       forum_id
     *                       forum_author
     *                       forum_parent_id
     *                       forum_name
     *                       forum_moderated
     *                       forum_description
     *                       forum_attachments
     *
     * @return integer  The forum ID on success.
     * @throws Agora_Exception
     */
    public function saveForum($info)
    {
        if (empty($info['forum_id'])) {
            if (empty($info['author'])) {
                $info['author'] = $GLOBALS['registry']->getAuth();
            }
            $info['forum_id'] = $this->newForum($info['forum_name'], $info['author']);
        }

        $sql = 'UPDATE ' . $this->_forums_table . ' SET forum_name = ?, forum_parent_id = ?, '
             . 'forum_description = ?, forum_moderated = ?, '
             . 'forum_attachments = ?, forum_distribution_address = ? '
             . 'WHERE forum_id = ?';

        $values = array($this->convertToDriver($info['forum_name']),
                        (int)$info['forum_parent_id'],
                        $this->convertToDriver($info['forum_description']),
                        (int)$info['forum_moderated'],
                        isset($info['forum_attachments']) ? (int)$info['forum_attachments'] : abs($GLOBALS['conf']['forums']['enable_attachments']),
                        isset($info['forum_distribution_address']) ? $info['forum_distribution_address'] : '',
                        $info['forum_id']);

        try {
            $this->_db->execute($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Agora_Exception($e->getMessage());
        }

        $this->_updateCacheState(0);
        $this->_cache->expire('agora_forum_' . $info['forum_id'], $GLOBALS['conf']['cache']['default_lifetime']);

        return $info['forum_id'];
    }

    /**
     * Deletes a forum, any subforums that are present and all messages
     * contained in the forum and subforums.
     *
     * @param integer $forum_id  The ID of the forum to delete.
     *
     * @return boolean  True on success.
     * @throws Agora_Exception
     */
    public function deleteForum($forum_id)
    {
        $this->deleteThread();

        /* Delete the forum itself. */
        try {
            $this->_db->delete('DELETE FROM ' . $this->_forums_table . ' WHERE forum_id = ' . (int)$forum_id);
        } catch (Horde_Db_Exception $e) {
            throw new Agora_Exception($e->getMessage());
        }

        return true;
    }

    /**
     * Searches forums for matching threads or posts.
     *
     * @param array $filter  Hash of filter criteria:
     *          'forums'         => Array of forum IDs to search.  If not
     *                              present, searches all forums.
     *          'keywords'       => Array of keywords to search for.  If not
     *                              present, finds all posts/threads.
     *          'allkeywords'    => Boolean specifying whether to find all
     *                              keywords; otherwise, wants any keyword.
     *                              False if not supplied.
     *          'message_author' => Name of author to find posts by.  If not
     *                              present, any author.
     *          'searchsubjects' => Boolean specifying whether to search
     *                              subjects.  True if not supplied.
     *          'searchcontents' => Boolean specifying whether to search
     *                              post contents.  False if not supplied.
     * @param string  $sort_by       The column by which to sort.
     * @param integer $sort_dir      The direction by which to sort:
     *                                   0 - ascending
     *                                   1 - descending
     * @param string  $from          The thread to start listing at.
     * @param string  $count         The number of threads to return.
     *
     * @return array  A search result hash where:
     *          'results'        => Array of messages.
     *          'total           => Total message number.
     * @throws Agora_Exception
     */
    public function search($filter, $sort_by = 'message_subject', $sort_dir = 0,
                    $from = 0, $count = 0)
    {
        if (!isset($filter['allkeywords'])) {
            $filter['allkeywords'] = false;
        }
        if (!isset($filter['searchsubjects'])) {
            $filter['searchsubjects'] = true;
        }
        if (!isset($filter['searchcontents'])) {
            $filter['searchcontents'] = false;
        }

        /* Select forums ids to search in */
        $sql = 'SELECT forum_id, forum_name FROM ' . $this->_forums_table . ' WHERE ';
        if (empty($filter['forums'])) {
            $sql .= ' active = ? AND scope = ?';
            $values = array(1, $this->_scope);
        } else {
            $sql .= ' forum_id IN (' . implode(',', $filter['forums']) . ')';
            $values = array();
        }
        try {
            $forums = $this->_db->selectAssoc($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Agora_Exception($e->getMessage());
        }

        /* Build query  */
        $sql = ' FROM ' . $this->_threads_table . ' WHERE forum_id IN (' . implode(',', array_keys($forums)) . ')';

        if (!empty($filter['keywords'])) {
            $sql .= ' AND (';
            if ($filter['searchsubjects']) {
                $keywords = '';
                foreach ($filter['keywords'] as $keyword) {
                    if (!empty($keywords)) {
                        $keywords .= $filter['allkeywords'] ? ' AND ' : ' OR ';
                    }
                    $keywords .= 'message_subject LIKE ' . $this->_db->quote('%' . $keyword . '%');
                }
                $sql .= '(' . $keywords . ')';
            }
            if ($filter['searchcontents']) {
                if ($filter['searchsubjects']) {
                    $sql .= ' OR ';
                }
                $keywords = '';
                foreach ($filter['keywords'] as $keyword) {
                    if (!empty($keywords)) {
                        $keywords .= $filter['allkeywords'] ? ' AND ' : ' OR ';
                    }
                    $keywords .= 'body LIKE ' . $this->_db->quote('%' . $keyword . '%');
                }
                $sql .= '(' . $keywords . ')';
            }
            $sql .= ')';
        }

        if (!empty($filter['author'])) {
            $sql .= ' AND message_author = ' . $this->_db->quote(Horde_String::lower($filter['author']));
        }

        /* Sort by result column. */
        $sql .= ' ORDER BY ' . $sort_by . ' ' . ($sort_dir ? 'DESC' : 'ASC');

        /* Slice directly in DB. */
        if ($count) {
            $total = $this->_db->selectValue('SELECT COUNT(*) '  . $sql);
            $sql = $this->_db->addLimitOffset($sql, array('limit' => $count, 'offset' => $from));
        }

        $sql = 'SELECT message_id, forum_id, message_subject, message_author, message_timestamp '  . $sql;
        try {
            $messages = $this->_db->select($sql);
        } catch (Horde_Db_Exception $e) {
            throw new Agora_Exception($e->getMessage());
        }
        if (empty($messages)) {
            return array('results' => array(), 'total' => 0);
        }

        $results = array();
        $msg_url = Horde::url('messages/index.php');
        $forum_url = Horde::url('threads.php');
        while ($message = $messages->fetch()) {
            if (!isset($results[$message['forum_id']])) {
                $index = array('agora' => $message['forum_id'], 'scope' => $this->_scope);
                $results[$message['forum_id']] = array('forum_id'   => $message['forum_id'],
                                                       'forum_url'  => Horde_Util::addParameter($forum_url, $index),
                                                       'forum_name' => $this->convertFromDriver($forums[$message['forum_id']]),
                                                       'messages'   => array());
            }
            $index = array('agora' => $message['forum_id']. '.' . $message['message_id'], 'scope' => $this->_scope);
            $results[$message['forum_id']]['messages'][] = array(
                'message_id' => $message['message_id'],
                'message_subject' => htmlspecialchars($this->convertFromDriver($message['message_subject'])),
                'message_author' => $message['message_author'],
                'message_date' => $this->dateFormat($message['message_timestamp']),
                'message_url' => Horde_Util::addParameter($msg_url, $index));
        }

        return array('results' => $results, 'total' => $total);
    }

    /**
     * Finds out if the user has the specified rights to the messages forum.
     *
     * @param integer $perm      The permission level needed for access.
     * @param integer $forum_id  Forum to check permissions for.
     * @param string $scope      Application scope to use.
     *
     * @return boolean  True if the user has the specified permissions.
     */
    public function hasPermission($perm = Horde_Perms::READ, $forum_id = null, $scope = null)
    {
        // Allow all admins
        if (($forum_id === null && isset($this->_forum['author']) && $this->_forum['author'] == $GLOBALS['registry']->getAuth()) ||
            $GLOBALS['registry']->isAdmin(array('permission' => 'agora:admin'))) {
            return true;
        }

        // Allow forum author
        if ($forum_id === null) {
            $forum_id = $this->_forum_id;
        }

        if ($scope === null) {
            $scope = $this->_scope;
        }

        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');
        if (!$perms->exists('agora:forums:' . $scope) &&
            !$perms->exists('agora:forums:' . $scope . ':' . $forum_id)) {
            return ($perm & Horde_Perms::DELETE) ? false : true;
        }

        return $perms->hasPermission('agora:forums:' . $scope, $GLOBALS['registry']->getAuth(), $perm) ||
            $perms->hasPermission('agora:forums:' . $scope . ':' . $forum_id, $GLOBALS['registry']->getAuth(), $perm);
    }

    /**
     * Converts a value from the driver's charset to the default charset.
     *
     * @param mixed $value  A value to convert.
     *
     * @return mixed  The converted value.
     */
    public function convertFromDriver($value)
    {
        return Horde_String::convertCharset($value, $this->_charset, 'UTF-8');
    }

    /**
     * Converts a value from the default charset to the driver's charset.
     *
     * @param mixed $value  A value to convert.
     *
     * @return mixed  The converted value.
     */
    public function convertToDriver($value)
    {
        return Horde_String::convertCharset($value, 'UTF-8', $this->_charset);
    }

    /**
     * Increment namespace
     */
    private function _updateCacheState($thread)
    {
        if (is_array($thread)) {
            foreach ($thread as $id) {
                $key = 'prefix_' . $this->_forum_id . '_' . $id;
                $prefix = $this->_cache->get($key, $GLOBALS['conf']['cache']['default_lifetime']);
                if ($prefix) {
                    $this->_cache->set($key, $prefix + 1);
                }
            }
        } else {
            $key = 'prefix_' . $this->_forum_id . '_' . $thread;
            $prefix = $this->_cache->get($key, $GLOBALS['conf']['cache']['default_lifetime']);
            if ($prefix) {
                $this->_cache->set($key, $prefix + 1);
            } else {
                $this->_cache->set($key, 2);
            }
        }
    }

    /**
     * Append namespace to cache key
     */
    private function _getCacheKey($key, $thread = 0)
    {
        static $prefix;

        if ($prefix == null) {
            $prefix = $this->_cache->get('prefix_' . $this->_forum_id . '_' . $thread,
                                        $GLOBALS['conf']['cache']['default_lifetime']);
            if (!$prefix) {
                $prefix = '1';
            }
        }

        return 's_' . $prefix . '_' . $thread . '_' . $key;
    }

    /**
     * Get cache value
     */
    protected function _getCache($key, $thread = 0)
    {
        $key = $this->_getCacheKey($key, $thread);

        return $this->_cache->get($key, $GLOBALS['conf']['cache']['default_lifetime']);
    }

    /**
     * Set cache value
     */
    protected function _setCache($key, $value, $thread = 0)
    {
        $key = $this->_getCacheKey($key, $thread);

        return $this->_cache->set($key, $value);
    }
}
