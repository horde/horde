<?php
/**
 * Agora_Messages:: provides the functions to access both threads and
 * individual messages.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Marko Djukic <marko@oblo.com>
 * @author  Jan Schneider <jan@horde.org>
 * @author  Duck <duck@obala.net>
 * @package Agora
 */
class Agora_Messages {

    /**
     * A hash containing any parameters for the current driver.
     *
     * @var array
     */
    protected $_params = array();

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
    protected $_forum_id;

    /**
     * Handle for the current database connection.
     *
     * @var DB
     */
    protected $_db;

    /**
     * Handle for the current database connection, used for writing. Defaults
     * to the same handle as $_db if a separate write database is not required.
     *
     * @var DB
     */
    protected $_write_db;

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
    public function __construct($scope)
    {
        /* Set parameters. */
        $this->_scope = $scope;
        $this->_connect();

        /* Initialize the Cache object. */
        $this->_cache = $GLOBALS['injector']->getInstance('Horde_Cache');
    }

    /**
     * Attempts to return a reference to a concrete Messages instance. It will
     * only create a new instance if no Messages instance currently exists.
     *
     * This method must be invoked as: $var = &Agora_Messages::singleton();
     *
     * @param string $scope     Application scope to use
     * @param int    $forum_id  Form to link to
     *
     * @return Forums  The concrete Messages reference, or false on error.
     */
    static public function &singleton($scope = 'agora', $forum_id = 0)
    {
        static $objects = array();

        if (!isset($objects[$scope])) {
            $driver = $GLOBALS['conf']['threads']['split'] ? 'split_sql' : 'sql';
            require_once AGORA_BASE . '/lib/Messages/' . $driver . '.php';
            $class_name = 'Agora_Messages_' . $driver;
            $objects[$scope] = new $class_name($scope);
        }

        if ($forum_id) {
            /* Check if there was a valid forum object to get. */
            $forum = $objects[$scope]->getForum($forum_id);
            if ($forum instanceof PEAR_Error) {
                return $forum;
            }

            /* Set curernt forum id and forum data */
            $objects[$scope]->_forum = $forum;
            $objects[$scope]->_forum_id = (int)$forum_id;
        }

        return $objects[$scope];
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
            if ($info['message_parent_id'] > 0) {
                $parents = $this->_db->getOne('SELECT parents FROM ' . $this->_threads_table . ' WHERE message_id = ?',
                                              null, array($info['message_parent_id']));
                $info['parents'] = $parents . ':' . $info['message_parent_id'];
                $info['message_thread'] = $this->getThreadRoot($info['message_parent_id']);
            } else {
                $info['parents'] = '';
                $info['message_thread'] = 0;
            }

            /* Create new message */
            $sql = 'INSERT INTO ' . $this->_threads_table
                . ' (message_id, forum_id, message_thread, parents, '
                . 'message_author, message_subject, body, attachments, '
                . 'message_timestamp, message_modifystamp, ip) '
                . ' VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?)';

            $author = $GLOBALS['registry']->getAuth() ? $GLOBALS['registry']->getAuth() : $info['posted_by'];
            $info['message_id'] = $this->_write_db->nextId('agora_messages');
            $params = array($info['message_id'],
                            $this->_forum_id,
                            $info['message_thread'],
                            $info['parents'],
                            $author,
                            $this->convertToDriver($info['message_subject']),
                            $this->convertToDriver($info['message_body']),
                            $_SERVER['REQUEST_TIME'],
                            $_SERVER['REQUEST_TIME'],
                            $_SERVER['REMOTE_ADDR']);

            $statement = $this->_write_db->prepare($sql);
            if ($statement instanceof PEAR_Error) {
                return $statement;
            }
            $result = $statement->execute($params);
            $statement->free();
            if ($result instanceof PEAR_Error) {
                Horde::logMessage($result, 'ERR');
                return $result;
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
            /* Update message data */
            $sql = 'UPDATE ' . $this->_threads_table . ' SET ' .
                   'message_subject = ?, body = ?, message_modifystamp = ? WHERE message_id = ?';
            $params = array($this->convertToDriver($info['message_subject']),
                            $this->convertToDriver($info['message_body']),
                            $_SERVER['REQUEST_TIME'],
                            $info['message_id']);

            $statement = $this->_write_db->prepare($sql);
            if ($statement instanceof PEAR_Error) {
                return $statement;
            }
            $result = $statement->execute($params);
            $statement->free();
            if ($result instanceof PEAR_Error) {
                Horde::logMessage($result, 'ERR');
                return $result;
            }

            /* Get message thread for cache expiration */
            $info['message_thread'] = $this->getThreadRoot($info['message_id']);
            if ($info['message_thread'] instanceof PEAR_Error) {
                return $info['message_thread'];
            }
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
                foreach ($this->_db->getCol($sql, null, array($info['message_id'])) as $file_id) {
                    if ($vfs->exists($vfs_dir, $file_id)) {
                        $delete = $vfs->deleteFile($vfs_dir, $file_id);
                        if ($delete instanceof PEAR_Error) {
                            return $delete;
                        }
                    }
                }
                $this->_write_db->query('DELETE FROM agore_files WHERE message_id = ' . (int)$info['message_id']);
                $attachments = 0;
            }

            /* Save new attachment information. */
            if (!empty($info['message_attachment'])) {
                $file_id = $this->_write_db->nextId('agora_files');
                $result = $vfs->write($vfs_dir, $file_id, $info['message_attachment']['file'], true);
                if ($result instanceof PEAR_Error) {
                    return $result;
                }

                $file_sql = 'INSERT INTO agora_files (file_id, file_name, file_type, file_size, message_id) VALUES (?, ?, ?, ?, ?)';
                $file_data = array($file_id,
                                   $info['message_attachment']['name'],
                                   $info['message_attachment']['type'],
                                   $info['message_attachment']['size'],
                                   $info['message_id']);

                $statement = $this->_write_db->prepare($file_sql);
                if ($statement instanceof PEAR_Error) {
                    return $statement;
                }

                $result = $statement->execute($file_data);
                $statement->free();
                if ($result instanceof PEAR_Error) {
                    Horde::logMessage($result, 'ERR');
                    return $result;
                }
                $attachments = 1;
            }

            $sql = 'UPDATE ' . $this->_threads_table . ' SET attachments = ' . $attachments
                    . ' WHERE message_id = ' . (int)$info['message_id'];
            $result = $this->_write_db->query($sql);
            if ($result instanceof PEAR_Error) {
                Horde::logMessage($result, 'ERR');
                return $result;
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
     */
    public function moveThread($thread_id, $forum_id)
    {
        $sql = 'SELECT forum_id FROM ' . $this->_threads_table . ' WHERE message_id = ' . (int)$thread_id;
        $old_forum = $this->_db->getOne($sql);
        if ($old_forum instanceof PEAR_Error) {
            return $old_forum;
        }

        $sql = 'UPDATE ' . $this->_threads_table . ' SET forum_id = ' . (int)$forum_id
                . ' WHERE message_thread = ' . (int)$thread_id .' OR message_id = ' . (int)$thread_id;
        $result = $this->_write_db->query($sql);
        if ($result instanceof PEAR_Error) {
            return $result;
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
     */
    public function splitThread($message_id)
    {
        $sql = 'SELECT message_thread FROM ' . $this->_threads_table . ' WHERE message_id = ' . (int)$message_id;
        $thread_id = $this->_db->getOne($sql);
        if ($thread_id instanceof PEAR_Error) {
            return $thread_id;
        }

        $sql = 'UPDATE ' . $this->_threads_table . ' SET message_thread = ?, parents = ? WHERE message_id = ?';
        $statement = $this->_write_db->prepare($sql);
        if ($statement instanceof PEAR_Error) {
            return $statement;
        }

        $result = $statement->execute(array(0, '', (int)$message_id));
        $statement->free();
        if ($result instanceof PEAR_Error) {
            return $result;
        }

        $sql = 'SELECT message_thread, parents, message_id FROM ' . $this->_threads_table . ' WHERE parents LIKE ?';
        $children = $this->_db->getAll($sql, null, array(":$thread_id:%$message_id%"));
        if ($children instanceof PEAR_Error) {
            return $children;
        }

        if (!empty($children)) {
            $pos = strpos($children[0]['parents'], ':' . $message_id);
            foreach ($children as $i => $message) {
                $children[$i]['message_thread'] = (int)$message_id;
                $children[$i]['parents'] = substr($message['parents'], $pos);
            }

            $sql = 'UPDATE ' . $this->_threads_table . ' SET message_thread = ?, parents = ? WHERE message_id = ?';
            $statement = $this->_write_db->prepare($sql);
            if ($statement instanceof PEAR_Error) {
                return $statement;
            }
            $result = $this->_write_db->executeMultiple($statement, $children);
            $statement->free();
            if ($result instanceof PEAR_Error) {
                return $result;
            }
        }

        // Update count on old thread
        $count = $this->countThreads($thread_id);
        $sql = 'UPDATE ' . $this->_threads_table . ' SET message_seq = ' . $count . ' WHERE message_id = ' . (int)$thread_id;
        $result = $this->_write_db->query($sql);
        if ($result instanceof PEAR_Error) {
            return $result;
        }

        // Update count on new thread
        $count = $this->countThreads($message_id);
        $sql = 'UPDATE ' . $this->_threads_table . ' SET message_seq = ' . $count . ' WHERE message_id = ' . (int)$thread_id;
        $result = $this->_write_db->query($sql);
        if ($result instanceof PEAR_Error) {
            return $result;
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
     */
    public function mergeThread($thread_from, $message_id)
    {
        $sql = 'SELECT message_thread, parents FROM ' . $this->_threads_table . ' WHERE message_id = ?';
        $destination = $this->_db->getRow($sql, null, array($message_id));
        if ($destination instanceof PEAR_Error) {
            return $destination;
        }

        /* Merge to the top level */
        if ($destination['message_thread'] == 0) {
            $destination['message_thread'] = $message_id;
        }

        $sql = 'SELECT message_thread, parents, message_id FROM ' . $this->_threads_table . ' WHERE message_id = ? OR message_thread = ?';
        $children = $this->_db->getAll($sql, null, array($thread_from, $thread_from));
        if ($children instanceof PEAR_Error) {
            return $children;
        }

        if (!empty($children)) {
            foreach ($children as $i => $message) {
                $children[$i]['message_thread'] = $destination['message_thread'];
                $children[$i]['parents'] = $destination['parents'] . $message['parents'];
                if (empty($children[$i]['parents'])) {
                    $children[$i]['parents'] = ':' . $message_id;
                }
            }

            $statement = $this->_write_db->prepare('UPDATE ' . $this->_threads_table . ' SET message_thread = ?, parents = ? WHERE message_id = ?');
            if ($statement instanceof PEAR_Error) {
                return $statement;
            }

            $result = $this->_write_db->executeMultiple($statement, $children);
            if ($result instanceof PEAR_Error) {
                return $result;
            }
        }

        $count = $this->countThreads($destination['message_thread']);
        $sql = 'UPDATE ' . $this->_threads_table . ' SET message_seq = ' . $count
                . ' WHERE message_id = ' . (int)$destination['message_thread'];
        $result = $this->_write_db->query($sql);
        if ($result instanceof PEAR_Error) {
            return $result;
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
        $message = $this->_db->getRow($sql, null, array($message_id));
        if ($message instanceof PEAR_Error) {
            Horde::logMessage($message, 'ERR');
            return $message;
        }

        if (empty($message)) {
            return PEAR::raiseError(sprintf(_("Message ID \"%d\" not found"),
                                            $message_id));
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
     */
    public function replyMessage($message)
    {
        if (!is_array($message)) {
            $message = $this->getMessage($message);
            if ($message instanceof PEAR_Error) {
                return $message;
            }
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
        $message['body'] = "\n> " . Horde_String::wrap($message['body'], 60, "\n> ", 'UTF-8');

        return $message;
    }

    /**
     * Deletes a message and all replies.
     *
     * @param integer $message_id  The ID of the message to delete.
     *
     * @return mixed  Thread ID on success or PEAR_Error on failure.
     */
    public function deleteMessage($message_id)
    {
        /* Check delete permissions. */
        if (!$this->hasPermission(Horde_Perms::DELETE)) {
            return PEAR::raiseError(sprintf(_("You don't have permission to delete messages in forum %s."), $this->_forum_id));
        }

        $sql = 'SELECT message_thread FROM ' . $this->_threads_table . ' WHERE message_id = ?';
        $thread_id = $this->_db->getOne($sql, null, array($message_id));

        if ($thread_id instanceof PEAR_Error) {
            Horde::logMessage($thread_id, 'ERR');
            return $thread_id;
        }

        $sql = 'DELETE FROM ' . $this->_threads_table . ' WHERE message_id = ' . (int)$message_id;
        if ($thread_id == 0) {
            $sql .= ' OR message_thread = ' . (int)$message_id;
        }

        $result = $this->_write_db->query($sql);
        if ($result instanceof PEAR_Error) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }

        /* Update counts */
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
     */
    private function _lastInForum($forum_id, $message_id = 0, $message_author = '', $message_timestamp = 0)
    {
        // Get the last message in form or thread - when managing threads
        if ($message_id == 0) {
            $sql = 'SELECT message_id, message_author, message_timestamp FROM ' . $this->_threads_table
                . ' WHERE forum_id = ' . (int)$forum_id . ' ORDER BY message_id DESC';
            $this->_db->setLimit(1, 0);
            $last = $this->_db->getRow($sql);
            if (empty($last)) {
                array(0, '', 0);
            } else {
                extract($last);
            }
        }

        $sql = 'UPDATE ' . $this->_forums_table
            . ' SET last_message_id = ?, last_message_author = ?, last_message_timestamp = ? WHERE forum_id = ?';

        $statement = $this->_write_db->prepare($sql);
        if ($statement instanceof PEAR_Error) {
            return $statement;
        }

        $statement->execute(array($message_id, $message_author, $message_timestamp, $forum_id));

        $this->_cache->expire('agora_forum_' . $forum_id, $GLOBALS['conf']['cache']['default_lifetime']);
    }

    /**
     * Update lastMessage in Thread
     *
     * @param integer $thread_id         Thread to update
     * @param integer $message_id        Last message id
     * @param string  $message_author    Last message author
     * @param integer $message_timestamp Last message timestamp
     */
    private function _lastInThread($thread_id, $message_id = 0, $message_author = '', $message_timestamp = 0)
    {
        // Get the last message in form or thread - when managing threads
        if ($message_id == 0) {
            $sql = 'SELECT message_id, message_author, message_timestamp FROM ' . $this->_threads_table
                . ' WHERE message_thread = ' . (int)$thread_id . ' ORDER BY message_id DESC';
            $this->_db->setLimit(1, 0);
            $last = $this->_db->getRow($sql);
            if (empty($last)) {
                $last = array(0, '', 0);
            } else {
                extract($last);
            }
        }

        $sql = 'UPDATE ' . $this->_threads_table
            . ' SET last_message_id = ?, last_message_author = ?, message_modifystamp = ? WHERE message_id = ?';

        $statement = $this->_write_db->prepare($sql);
        if ($statement instanceof PEAR_Error) {
            return $statement;
        }

        $statement->execute(array($message_id, $message_author, $message_timestamp, $thread_id));
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

        return $this->_write_db->query($sql);
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
        Horde::logMessage('Query by Agora_Messages::_sequence(): ' . $sql, 'DEBUG');
        return $this->_write_db->query($sql);
    }

    /**
     * Deletes an entire message thread.
     *
     * @param integer $thread_id  The ID of the thread to delete. If not
     *                            specified will delete all the threads for the
     *                            current forum.
     */
    public function deleteThread($thread_id = 0)
    {
        /* Check delete permissions. */
        if (!$this->hasPermission(Horde_Perms::DELETE)) {
            return PEAR::raiseError(sprintf(_("You don't have permission to delete messages in forum %s."), $this->_forum_id));
        }

        if ($thread_id > 0) {
            $sql = 'DELETE FROM ' . $this->_threads_table . ' WHERE message_thread = ' . (int)$thread_id;
            $result = $this->_write_db->query($sql);
            if ($result instanceof PEAR_Error) {
                Horde::logMessage($result, 'ERR');
                return $result;
            }

            $sql = 'SELECT COUNT(*) FROM ' . $this->_threads_table . ' WHERE forum_id = ' . (int)$this->_forum_id;
            $messages = $this->_db->getOne($sql);

            $this->_forumSequence($this->_forum_id, 'thread', '-');
            $this->_forumSequence($this->_forum_id, 'message', $messages);

            /* Update cache */
            $this->_updateCacheState($thread_id);

        } else {
            $sql = 'DELETE FROM ' . $this->_threads_table . ' WHERE forum_id = ' . (int)$this->_forum_id;
            $result = $this->_write_db->query($sql);
            if ($result instanceof PEAR_Error) {
                Horde::logMessage($result, 'ERR');
                return $result;
            }

            $this->_forumSequence($this->_forum_id, 'thread', 0);
            $this->_forumSequence($this->_forum_id, 'message', 0);
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
        foreach ($messages as $id => &$message) {

            /* Add attachment link */
            if ($message['attachments']) {
                $message['message_attachment'] = $this->getAttachmentLink($id);
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
            if ($thread_root == 0 && $this->isNew($id, $last_timestamp)) {
                $message['new'] = $new_img;
            }

            /* Mark moderators */
            if (isset($this->_forum['moderators']) && array_key_exists($message['message_author'], $moderators)) {
                $message['message_author_moderator'] = 1;
            }

            /* Link to view the message. */
            $url = Agora::setAgoraId($message['forum_id'], $id, $view_url, $this->_scope);
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
                    $url = Agora::setAgoraId($message['forum_id'], $id, $view_url, $this->_scope);
                }
                $url = Horde_Util::addParameter($url, 'reply_focus', 1) . '#messageform';
                $message['reply'] = Horde::link($url, _("Reply to message"), '', '', '', _("Reply to message")) . _("Reply") . '</a>';
            }

            /* Link to edit the message. */
            if ($thread_root > 0 && isset($this->_forum['moderators'])) {
                $url = Agora::setAgoraId($message['forum_id'], $id, $abuse_url);
                $message['actions'][] = Horde::link($url, _("Report as abuse")) . _("Report as abuse") . '</a>';
            }

            if ($is_moderator) {
                /* Link to edit the message. */
                $url = Agora::setAgoraId($message['forum_id'], $id, $edit_url, $this->_scope);
                $message['actions'][] = Horde::link($url, _("Edit"), '', '', '', _("Edit message")) . _("Edit") . '</a>';

                /* Link to delete the message. */
                $url = Agora::setAgoraId($message['forum_id'], $id, $del_url, $this->_scope);
                $message['actions'][] = Horde::link($url, _("Delete"), '', '', '', _("Delete message")) . _("Delete") . '</a>';

                /* Link to lock/unlock the message. */
                $url = Agora::setAgoraId($this->_forum_id, $id, Horde::url('messages/lock.php'), $this->_scope);
                $label = ($message['locked']) ? _("Unlock") : _("Lock");
                $message['actions'][] = Horde::link($url, $label, '', '', '', $label) . $label . '</a>';

                /* Link to move thread to another forum. */
                if ($this->_scope == 'agora') {
                    if ($message['message_thread'] == $id) {
                        $url = Agora::setAgoraId($this->_forum_id, $id, Horde::url('messages/move.php'), $this->_scope);
                        $message['actions'][] = Horde::link($url, _("Move"), '', '', '', _("Move")) . _("Move") . '</a>';

                        /* Link to merge a message thred with anoter thread. */
                        $url = Agora::setAgoraId($this->_forum_id, $id, Horde::url('messages/merge.php'), $this->_scope);
                        $message['actions'][] = Horde::link($url, _("Merge"), '', '', '', _("Merge")) . _("Merge") . '</a>';
                    } elseif ($message['message_thread'] != 0) {

                        /* Link to split thread to two threads, from this message after. */
                        $url = Agora::setAgoraId($this->_forum_id, $id, Horde::url('messages/split.php'), $this->_scope);
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
        foreach ($messages as $id => &$message) {
            $message['message_id'] = $id;
            $message['message_author'] = htmlspecialchars($message['message_author']);
            $message['message_subject'] = htmlspecialchars($this->convertFromDriver($message['message_subject']), ENT_COMPAT, 'UTF-8');
            $message['message_date'] = $this->dateFormat($message['message_timestamp']);
            if ($format) {
                $message['body'] = $this->formatBody($this->convertFromDriver($message['body']));
            }

            // If we are on the top, thread id is message itself
            if ($message['message_thread'] == 0) {
                $message['message_thread'] = $id;
            }

            /* Get last message */
            if ($thread_root == 0 && $message['last_message_id'] > 0) {
                $message['last_message_date'] = $this->dateFormat($message['last_message_timestamp']);
            }

            /* Set up indenting for threads. */
            if ($sort_by == 'message_thread') {
                $indent = explode(':', $message['parents']);
                $message['indent'] = count($indent) - 1;
                $last = array_pop($indent);
                if (!isset($messages[$last])) {
                    $message['indent'] = 1;
                    $last = null;
                }
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

            // check bad words replacement
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

        return $GLOBALS['injector']->getInstance('Horde_Text_Filter')->filter($body, $filters, $filters_params);
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
     */
    public function getModerateList($sort_by, $sort_dir)
    {
        $sql = 'SELECT forum_id, forum_name FROM ' . $this->_forums_table . ' WHERE forum_moderated = ?';
        $params = array(1);

        /* Check permissions */
        if ($GLOBALS['registry']->isAdmin(array('permission' => 'agora:admin')) ||
            ($GLOBALS['injector']->getInstance('Horde_Perms')->exists('agora:forums:' . $this->_scope) &&
             $GLOBALS['injector']->getInstance('Horde_Perms')->hasPermission('agora:forums:' . $this->_scope, $GLOBALS['registry']->getAuth(), Horde_Perms::DELETE))) {
                $sql .= ' AND scope = ? ';
                $params[] = $this->_scope;
        } else {
            // Get only author forums
            $sql .= ' AND scope = ? AND author = ?';
            $params[] = $this->_scope;
            $params[] = $GLOBALS['registry']->getAuth();
        }

        /* Get moderate forums and their names */
        $forums_list = $this->_db->getAssoc($sql, null, $params, null, MDB2_FETCHMODE_ASSOC, false);
        if ($forums_list instanceof PEAR_Error || empty($forums_list)) {
            return $forums_list;
        }

        /* Get message waiting for approval */
        $sql = 'SELECT message_id, forum_id, message_subject, message_author, '
            . 'body, message_timestamp, attachments FROM ' . $this->_threads_table . ' WHERE forum_id IN ('
            . implode(',', array_keys($forums_list)) . ')'
            . ' AND approved = ? ORDER BY ' . $sort_by . ' '
            . ($sort_dir ? 'DESC' : 'ASC');

        $messages = $this->_db->getAssoc($sql, null, array(0));
        if ($messages instanceof PEAR_Error) {
            return $messages;
        }

        /* Loop through the messages and set up the array. */
        $approve_url = Horde_Util::addParameter(Horde::url('moderate.php'), 'approve', true);
        $del_url  = Horde::url('messages/delete.php');
        foreach ($messages as $id => &$message) {
            $message['forum_name'] = $this->convertFromDriver($forums_list[$message['forum_id']]);
            $message['message_id'] = $id;
            $message['message_author'] = htmlspecialchars($message['message_author']);
            $message['message_subject'] = htmlspecialchars($this->convertFromDriver($message['message_subject']), ENT_COMPAT, 'UTF-8');
            $message['message_body'] = $GLOBALS['injector']->getInstance('Horde_Text_Filter')filter($this->convertFromDriver($message['body']), 'highlightquotes');
            if ($message['attachments']) {
                $message['message_attachment'] = $this->getAttachmentLink($id);
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

        // Filter users moderators
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
            $forum_perm = $perms->newPermission($perm_name);
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

        $statement = $this->_write_db->prepare($sql);
        if ($statement instanceof PEAR_Error) {
            return $statement;
        }

        $result = $statement->execute(array($forum_id, $moderator));
        $statement->free();
        if ($result instanceof PEAR_Error) {
            return $result;
        }

        /* Update permissions*/
        $perm_name = 'agora:forums:' . $this->_scope . ':' . $forum_id;
        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');
        if (!$perms->exists($perm_name)) {
            $forum_perm = $perms->newPermission($perm_name);
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
     * @return mixed  Returns true if successful or otherwise a PEAR_Error.
     */
    public function moderate($action, $ids)
    {
        switch ($action) {
        case 'approve':

            // Get message thread to expire cache
            $sql = 'SELECT message_thread FROM ' . $this->_threads_table
                    . ' WHERE message_id IN (' . implode(',', $ids) . ')';
            $threads = $this->_db->getCol($sql);
            $this->_updateCacheState($threads);

            $sql = 'UPDATE ' . $this->_threads_table . ' SET approved = 1'
                 . ' WHERE message_id IN (' . implode(',', $ids) . ')';
            $this->_write_db->query($sql);

            // Save original forum_id for later resetting
            $orig_forum_id = $this->_forum_id;
            foreach ($ids as $message_id) {
                // Update cached message and thread counts
                $message = $this->getMessage($message_id);
                $this->_forum_id = $message['forum_id'];

                // Update cached last poster
                $this->_lastInForum($this->_forum_id);
                $this->_forumSequence($this->_forum_id, 'message', '+');
                if (!empty($message['parents'])) {
                    $this->_sequence($message['message_thread'], '+');
                    $this->_lastInThread($message['message_thread'], $message_id, $message['message_author'], $_SERVER['REQUEST_TIME']);
                } else {
                    $this->_forumSequence($this->_forum_id, 'thread', '+');
                }

                // Send the new post to the distribution address
                Agora::distribute($message_id);
            }

            // Restore original forum_id
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
            $params = array($thread_root);
            return $this->_db->getOne($sql, 'integer', array($thread_root));
        } else {
            return $this->_db->getOne($sql . ' AND forum_id = ?', 'integer', array(0, $this->_forum_id));
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
        return $this->_db->getOne($sql, null, array($this->_forum_id));
    }

    /**
     * Returns a table showing the specified message list.
     *
     * @param array $threads         A hash with the thread messages as
     *                               returned by {@link
     *                               Agora_Messages::getThreads}.
     * @param array $col_headers     A hash with the column headers.
     * @param boolean $bodies        Display the message bodies?
     * @param string $template_file  Template to use.
     *
     * @return string  The rendered message table.
     */
    public function getThreadsUI($threads, $col_headers, $bodies = false,
                                 $template_file = false)
    {
        if (!count($threads)) {
            return '';
        }

        /* Render threaded lists with Horde_Tree. */
        $current = key($threads);
        if (!$template_file && isset($threads[$current]['indent'])) {
            $tree = $GLOBALS['injector']->getInstance('Horde_Tree')->getTree('threads', 'Html', array(
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

                $tree->addNode(
                    $thread['message_id'],
                    $thread['parent'],
                    $text,
                    $thread['indent'],
                    true,
                    array(
                        'class' => 'linedRow',
                    ),
                    array(
                        $thread['message_author'],
                        $thread['message_date']
                    )
                );
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
            $template_file = 'messages/threads.html.php';
        }

        return $view->render($template_file);
    }

    /**
     */
    public function getThreadRoot($message_id)
    {
        $sql = 'SELECT message_thread FROM ' . $this->_threads_table . ' WHERE message_id = ?';
        $thread_id = $this->_db->getOne($sql, null, array($message_id));
        return $thread_id ? $thread_id : $message_id;
    }

    /**
     */
    public function setThreadLock($message_id, $lock)
    {
        $sql = 'UPDATE ' . $this->_threads_table . ' SET locked = ' . (int)$lock
                . ' WHERE message_id = ' . (int)$message_id . ' OR message_thread = ' . (int)$message_id;
        return $this->_write_db->query($sql);
    }

    /**
     * @return boolean
     */
    public function isThreadLocked($message_id)
    {
        $sql = 'SELECT message_thread FROM ' . $this->_threads_table . ' WHERE message_id = ?';
        $thread = $this->_db->getOne($sql, null, array($message_id));

        return $this->_db->getOne('SELECT locked FROM ' . $this->_threads_table . ' WHERE message_id = ?',
                                  null, array($thread));
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

        require_once AGORA_BASE . '/lib/Forms/Message.php';
        $form = new MessageForm($vars, $title);
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
        $sql = 'UPDATE ' . $this->_threads_table . ' SET view_count = view_count + 1 WHERE message_id = ' . (int)$thread_id;
        $result = $this->_write_db->query($sql);
        if ($result instanceof PEAR_Error) {
            return $result;
        }

        return true;
    }

    /**
     * Constructs message attachments link.
     */
    public function getAttachmentLink($message_id)
    {
        if (!$this->allowAttachments()) {
            return '';
        }

        $sql = 'SELECT file_id, file_name, file_size, file_type FROM agora_files WHERE message_id = ?';
        $files = $this->_db->getAssoc($sql, null, array($message_id));
        if ($files instanceof PEAR_Error || empty($files)) {
            Horde::logMessage($files, 'ERR');
            return $files;
        }

        /* Constuct the link with a tooltip for further info on the download. */
        $html = '<br />';
        $view_url = Horde::url('view.php');
        foreach ($files as $file_id => $file) {
            $mime_icon = $GLOBALS['injector']->getInstance('Horde_Mime_Viewer')->getIcon($file['file_type']);
            $title = _("download") . ': ' . $file['file_name'];
            $tooltip = $title . "\n" . sprintf(_("size: %s"), $this->formatSize($file['file_size'])) . "\n" . sprintf(_("type: %s"), $file['file_type']);
            $url = Horde_Util::addParameter($view_url, array('forum_id' => $this->_forum_id,
                                                       'message_id' => $message_id,
                                                       'file_id' => $file_id,
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
        $forum = $this->_db->getRow($sql, null, array($forum_id));
        if ($forum instanceof PEAR_Error) {
            return $forum;
        } elseif (empty($forum)) {
            return PEAR::raiseError(sprintf(_("Forum %s does not exist."), $forum_id));
        }

        $forum['forum_name'] = $this->convertFromDriver($forum['forum_name']);
        $forum['forum_description'] = $this->convertFromDriver($forum['forum_description']);
        $forum['forum_distribution_address'] = $this->convertFromDriver($forum['forum_distribution_address']);

        /* Get moderators */
        $sql = 'SELECT horde_uid FROM agora_moderators WHERE forum_id = ?';
        $moderators = $this->_db->getCol($sql, null, array($forum_id));
        if ($moderators instanceof PEAR_Error) {
            return $moderators;
        } elseif (!empty($moderators)) {
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
        return $this->_db->getOne($sql, null, array(1, $this->_scope));
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

        $moderate = array();
        $user = $GLOBALS['registry']->getAuth();
        $edit_url =  Horde::url('messages/edit.php');
        $editforum_url =  Horde::url('editforum.php');
        $delete_url = Horde::url('deleteforum.php');

        foreach ($forums as $forum_id => &$forum) {
            if (!$this->hasPermission(Horde_Perms::SHOW, $forum_id, $forum['scope'])) {
                unset($forums[$forum_id]);
                continue;
            }

            $forum['indentn'] =  0;
            $forum['indent'] = '';
            if (!$this->hasPermission(Horde_Perms::READ, $forum_id, $forum['scope'])) {
                continue;
            }

            $forum['url'] = Agora::setAgoraId($forum_id, null, Horde::url('threads.php'), $forum['scope'], true);
            $forum['message_count'] = number_format($forum['message_count']);
            $forum['thread_count'] = number_format($forum['thread_count']);

            if ($forum['last_message_id']) {
                $forum['last_message_date'] = $this->dateFormat($forum['last_message_timestamp']);
                $forum['last_message_url'] = Agora::setAgoraId($forum_id, $forum['last_message_id'], Horde::url('messages/index.php'), $forum['scope'], true);
            }

            $forum['actions'] = array();

            /* Post message button. */

            if ($this->hasPermission(Horde_Perms::EDIT, $forum_id, $forum['scope'])) {
                /* New Post forum button. */
                $url = Agora::setAgoraId($forum_id, null, $edit_url, $forum['scope'], true);
                $forum['actions'][] = Horde::link($url, _("Post message")) . _("New Post") . '</a>';

                if ($GLOBALS['registry']->isAdmin(array('permission' => 'agora:admin'))) {
                    /* Edit forum button. */
                    $url = Agora::setAgoraId($forum_id, null, $editforum_url, $forum['scope'], true);
                    $forum['actions'][] = Horde::link($url, _("Edit forum")) . _("Edit") . '</a>';
                }
            }

            if ($GLOBALS['registry']->isAdmin(array('permission' => 'agora:admin'))) {
                /* Delete forum button. */
                $url = Agora::setAgoraId($forum_id, null, $delete_url, $forum['scope'], true);
                $forum['actions'][] = Horde::link($url, _("Delete forum")) . _("Delete") . '</a>';
            }

            /* User is a moderator */
            if (isset($forum['moderators']) && in_array($user, $forum['moderators'])) {
                $moderate[] = $forum_id;
            }
        }

        /* If needed, display moderate link */
        if (!empty($moderate)) {
            $sql = 'SELECT forum_id, COUNT(forum_id) FROM ' . $this->_threads_table
                 . ' WHERE forum_id IN (' . implode(',', $moderate) . ') AND approved = ?'
                 . ' GROUP BY forum_id';
            $unapproved = $this->_db->getAssoc($sql, null, array(0), null, MDB2_FETCHMODE_ASSOC, false);
            if ($unapproved instanceof PEAR_Error) {
                return $unapproved;
            }

            $url = Horde::link(Horde::url('moderate.php', true), _("Moderate")) . _("Moderate") . '</a>';
            foreach ($unapproved as $forum_id => $count) {
                $forum['actions'][] = $url . ' (' . $count . ')' ;
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
     * @param integer $forums      Frorms to format
     * @param boolean $formatted   Whether to return the list formatted or raw.
     *
     * @return mixed  An array of forums or PEAR_Error on failure.
     */
    protected function _formatForums($forums, $formatted = true)
    {
        foreach (array_keys($forums) as $forum_id) {
            $forums[$forum_id]['forum_name'] = $this->convertFromDriver($forums[$forum_id]['forum_name']);
            if ($formatted) {
                $forums[$forum_id]['forum_description'] = $this->convertFromDriver($forums[$forum_id]['forum_description']);
            }
        }

        if ($formatted) {
            /* Get moderators */
            $sql = 'SELECT forum_id, horde_uid'
                . ' FROM agora_moderators WHERE forum_id IN (' . implode(',', array_keys($forums)) . ')';
            $moderators = $this->_db->getAssoc($sql, null, null, null, MDB2_FETCHMODE_ASSOC, false, true);
            if ($moderators instanceof PEAR_Error) {
                return $moderators;
            }

            foreach ($forums as $forum_id => $forum) {
                if (isset($moderators[$forum_id])) {
                    $forums[$forum_id]['moderators'] = $moderators[$forum_id];
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
     */
    public function newForum($forum_name, $owner)
    {
        if (empty($forum_name)) {
            return PEAR::raiseError(_("Cannot create a forum with an empty name."));
        }

        $forum_id = $this->_write_db->nextId('agora_forums');
        $sql = 'INSERT INTO ' . $this->_forums_table . ' (forum_id, scope, forum_name, active, author) VALUES (?, ?, ?, ?, ?)';
        $statement = $this->_write_db->prepare($sql);
        if ($statement instanceof PEAR_Error) {
            return $statement;
        }

        $result = $statement->execute(array($forum_id, $this->_scope, $this->convertToDriver($forum_name), 1, $owner));
        $statement->free();

        if ($result instanceof PEAR_Error) {
            return $result;
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
     * @return mixed  The forum ID on success or PEAR_Error on failure.
     */
    public function saveForum($info)
    {
        if (empty($info['forum_id'])) {
            if (empty($info['author'])) {
                $info['author'] = $GLOBALS['registry']->getAuth();
            }
            $info['forum_id'] = $this->newForum($info['forum_name'], $info['author']);
            if ($info['forum_id'] instanceof PEAR_Error) {
                return $info['forum_id'];
            }
        }

        $sql = 'UPDATE ' . $this->_forums_table . ' SET forum_name = ?, forum_parent_id = ?, '
             . 'forum_description = ?, forum_moderated = ?, '
             . 'forum_attachments = ?, forum_distribution_address = ? '
             . 'WHERE forum_id = ?';

        $params = array($this->convertToDriver($info['forum_name']),
                        (int)$info['forum_parent_id'],
                        $this->convertToDriver($info['forum_description']),
                        (int)$info['forum_moderated'],
                        isset($info['forum_attachments']) ? (int)$info['forum_attachments'] : abs($GLOBALS['conf']['forums']['enable_attachments']),
                        isset($info['forum_distribution_address']) ? $info['forum_distribution_address'] : '',
                        $info['forum_id']);

        Horde::logMessage('SQL Query by Agora_Message::saveForum(): ' . $sql, 'DEBUG');
        $statement = $this->_write_db->prepare($sql);
        if ($statement instanceof PEAR_Error) {
            return $statement;
        }

        $result = $statement->execute($params);
        $statement->free();

        if ($result instanceof PEAR_Error) {
            return $result;
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
     * @return mixed  True on success or PEAR_Error on failure.
     */
    public function deleteForum($forum_id)
    {
        $result = $this->deleteThread();
        if ($result instanceof PEAR_Error) {
            return $result;
        }

        /* Delete the forum itself. */
        $result = $this->_write_db->query('DELETE FROM ' . $this->_forums_table . ' WHERE forum_id = ' . (int)$forum_id);
        if ($result instanceof PEAR_Error) {
            return $result;
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
            $forums = $this->_db->getAssoc($sql, null, array(1, $this->_scope));
        } else {
            $sql .= ' forum_id IN (' . implode(',', $filter['forums']) . ')';
            $forums = $this->_db->getAssoc($sql);
        }
        if ($forums instanceof PEAR_Error) {
            return $forums;
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
            $sql .= ' AND message_author = ' . $this->_db->quote(Horde_String::lower($filter['author'], 'UTF-8'));
        }

        /* Sort by result column. */
        $sql .= ' ORDER BY ' . $sort_by . ' ' . ($sort_dir ? 'DESC' : 'ASC');

        /* Slice directly in DB. */
        if ($count) {
            $total = $this->_db->getOne('SELECT COUNT(*) '  . $sql);
            $this->_db->setLimit($count, $from);
        }

        $sql = 'SELECT message_id, forum_id, message_subject, message_author, message_timestamp '  . $sql;
        $messages = $this->_db->query($sql);
        if ($messages instanceof PEAR_Error) {
            return $messages;
        }
        if (empty($messages)) {
            return array('results' => array(), 'total' => 0);
        }

        $results = array();
        $msg_url = Horde::url('messages/index.php');
        $forum_url = Horde::url('threads.php');
        while ($message = $messages->fetchRow()) {
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
        return Horde_String::convertCharset($value, $this->_params['charset']);
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
        return Horde_String::convertCharset($value, 'UTF-8', $this->_params['charset']);
    }

    /**
     * Attempts to open a persistent connection to the SQL server.
     *
     * @return boolean  True on success.
     * @throws Horde_Exception
     */
    private function _connect()
    {
        $this->_params = Horde::getDriverConfig('storage', 'sql');
        Horde::assertDriverConfig($this->_params, 'storage',
                                  array('phptype', 'charset'));

        $charset = $this->_params['charset'];
        unset($this->_params['charset']);

        $this->_write_db = MDB2::factory($this->_params);
        if ($this->_write_db instanceof PEAR_Error) {
            throw new Horde_Exception($this->_write_db);
        }

        if (!empty($params['splitread'])) {
            $params = array_merge($this->_params, $this->_params['read']);
            $this->_db = MDB2::factory($this->_params);
            if ($this->_db instanceof PEAR_Error) {
                throw new Horde_Exception($this->_db);
            }
        } else {
            /* Default to the same DB handle for the writer too. */
            $this->_db =& $this->_write_db;
        }

        $this->_db->loadModule('Extended');
        if ($this->_db instanceof PEAR_Error) {
            throw new Horde_Exception($this->_db);
        }

        $this->_db->setFetchMode(MDB2_FETCHMODE_ASSOC);
        $this->_write_db->setOption('seqcol_name', 'id');
        $this->_db->setOption('portability', MDB2_PORTABILITY_ALL ^ MDB2_PORTABILITY_EMPTY_TO_NULL);
        $this->_params['charset'] = $charset;

        return true;
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
