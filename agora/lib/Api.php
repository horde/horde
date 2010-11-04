<?php
/**
 * Agora external API interface.
 *
 * This file defines Agora's external API interface. Other
 * applications can interact with Agora through this API.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Marko Djukic <marko@oblo.com>
 * @author  Duck <duck@obala.net>
 * @package Agora
 */

class Agora_Api extends Horde_Registry_Api
{
    /**
     * Get back a list of available forums.
     *
     * @param integer $forum_id  Supplying this parameter will return a list of
     *                           child forums of the requested forum id.
     * @param string $scope      If set, limit the forums to requested application.
     *
     * @return array  The list of available forums.
     */
    public function listForums($forum_id = 0, $scope = null)
    {
        $forums = &Agora_Messages::singleton($scope);
        return $forums->getForums($forum_id, true, 'forum_name', 0, isset($scope));
    }

    /**
     * Retrieve the name of a forum
     *
     * @param string $scope      Scope which form belongs to
     * @param integer $forum_id  The forum id to fetch the name for.
     *
     * @return mixed The forum name | Pear_Error
     */
    public function getForumName($scope, $forum_id)
    {
        $forums = &Agora_Messages::singleton($scope);
        $forum = $forums->getForum($forum_id);
        if ($forum instanceof PEAR_Error) {
            return $forum;
        }

        return $forum['forum_name'];
    }

    /**
     * Create or modify an agora forum. This is used for apps to create
     * forums for their own use. They will not show up in the regular
     * agora forum view since they will be using a datatree group
     * 'agora.forums.<sope>'.
     *
     * @param string $scope   The Horde application that is saving this forum.
     * @param string $parent  The parent forum.
     * @param array  $info    The forum information to save
     */
    public function saveForum($scope, $parent, $info)
    {
        $forums = &Agora_Messages::singleton($scope);
        $forum_info = $this->prepareFormData($scope, $parent, $info);
        if ($forum_info instanceof PEAR_Error) {
            return $forum_info;
        }

        return $forums->saveForum($forum_info);
    }

    /**
     * Allow other applications to delete forums. Used when an object that
     * has been commented on has been deleted.
     *
     * @param string $scope       The Horde application that the forum belongs to.
     * @param string $forum_name  The unique forum name to delete.
     *
     * @return boolean True on success.
     */
    public function deleteForum($scope, $forum_name)
    {
        $forums = &Agora_Messages::singleton($scope);
        $id = $forums->getForumId($forum_name);
        if ($id instanceof PEAR_Error) {
            Horde::logMessage($id, 'ERR');
            return false;
        }

        $forums = &Agora_Messages::singleton($scope, $id);
        $result = $forums->deleteForum($id);
        if ($result instanceof PEAR_Error) {
            Horde::logMessage($result, 'ERR');
            return false;
        }
        return true;
    }

    /**
     * Returns all messages of a forum, in a threaded order.
     *
     * @param string  $forum_name  The unique name for the forum.
     * @param boolean $bodies      Whether to include message bodies in the view.
     * @param string  $sort_by     Return messages sorted by this property.
     * @param integer $sort_dir    The direction by which to sort:
     *                                0 - ascending
     *                                1 - descending
     * @param string  $scope       The application that the specified forum belongs
     *                             to.
     * @param string  $base_url    An alternate link where edit/delete/reply links
     *                             point to.
     * @param string  $from        The thread to begin listing at.
     * @param string  $count       The number of threads to return.
     *
     * @return array  All messages of the specified forum.
     */
    public function getThreads($forum_name, $sort_by = 'message_timestamp', $sort_dir = 0, $bodies = false,
                            $scope = 'agora', $base_url = null, $from = 0, $count = 0)
    {
        $forums = &Agora_Messages::singleton($scope);
        if (empty($forum_name)) {
            return $forums->getThreads(0, false, $sort_by, $sort_dir, true, '', $base_url, $from, $count);
        } elseif (($forum_id = $forums->getForumId($forum_name)) instanceof PEAR_Error) {
            return $forum_id;
        } elseif (empty($forum_id)) {
            return array();
        }

        $messages = &Agora_Messages::singleton($scope, $forum_id);
        if ($messages instanceof PEAR_Error) {
            return $messages;
        }

        return $messages->getThreads(0, true, $sort_by, $sort_dir, true, '', $base_url, $from, $count);
    }

    /**
     * Returns all messages for the forums requested, in a threaded order.
     *
     * @param array   $forum_names  An array of unique forum names.
     * @param boolean $bodies       Whether to include message bodies in the view.
     * @param string  $sort_by      Return messages sorted by this property.
     * @param integer $sort_dir     The direction by which to sort:
     *                                0 - ascending
     *                                1 - descending
     * @param string  $scope        The application that the specified forum belongs
     *                              to.
     * @param string  $base_url     An alternate link where edit/delete/reply links
     *                              point to.
     * @param string  $from         The thread to begin listing at.
     * @param string  $count        The number of threads to return.
     *
     * @return array  An array of message arrays of the specified forum.
     */
    public function getThreadsBatch($forum_names, $sort_by = 'message_timestamp', $sort_dir = 0, $bodies = false,
                            $scope = 'agora', $base_url = null, $from = 0, $count = 0)
    {
        $results = array();
        $forums = &Agora_Messages::singleton($scope);
        $results = array();
        foreach ($forum_names as $forum) {
            $forum_id = $forums->getForumId($forum);
            if ($forum_id instanceof PEAR_Error || empty($forum_id)) {
                $results[$forum] = array();
            } else {
                $messages = &Agora_Messages::singleton($scope, $forum_id);
                if ($messages instanceof PEAR_Error) {
                    return $messages;
                }
                $results[$forum] = $messages->getThreads(0, true, $sort_by, $sort_dir, true, '', $base_url, $from, $count);
            }
        }
        return $results;
    }

    /**
     * Returns all messages of a forum, in a threaded order.
     *
     * @param string  $forum_owner   Forum owner
     * @param boolean $bodies      Whether to include message bodies in the view.
     * @param string  $sort_by     Return messages sorted by this property.
     * @param integer $sort_dir    The direction by which to sort:
     *                                0 - ascending
     *                                1 - descending
     * @param string  $scope       The application that the specified forum belongs
     *                             to.
     * @param string  $from        The thread to begin listing at.
     * @param string  $count       The number of threads to return.
     *
     * @return array  All messages of the specified forum.
     */
    public function getThreadsByForumOwner($owner, $sort_by = 'message_timestamp', $sort_dir = 0, $bodies = false,
                            $scope = 'agora', $from = 0, $count = 0)
    {
        $forums = &Agora_Messages::singleton($scope);

        return $forums->getThreadsByForumOwner($owner, 0, true, $sort_by, $sort_dir, true, $from, $count);
    }

    /**
     * Returns the number of messages in a forum.
     *
     * @param string  $forum_name  The unique name for the forum.
     * @param string  $scope       The application that the specified forum
     *                             belongs to.
     * @param int     $thread_id   The thread to count, if not supplied it
     *                             will count all messages
     *
     * @return int  The number of messages in the specified forum.
     */
    public function numMessages($forum_name, $scope = 'agora', $thread_id = null)
    {
        $forums = &Agora_Messages::singleton($scope);

        if (($forum_id = $forums->getForumId($forum_name)) instanceof PEAR_Error) {
            return $forum_id;
        } elseif (empty($forum_id)) {
            return 0;
        }

        $messages = Agora_Messages::singleton($scope, $forum_id);
        if (is_a($messages, 'PEAR_Error')) {
            return $messages;
        }
        return ($thread_id === null) ? $messages->_forum['message_count'] : $messages->countThreads($thread_id);
    }

    /**
     * Returns the number of messages for the requested forums.
     * All requested forums must belong to the same scope.
     *
     * @param array   $forum_name  An array of unique forum names.
     * @param string  $scope       The application that the specified forum
     *                             belongs to.
     * @param int     $thread_id   The thread to count, if not supplied it
     *                             will count all messages
     *
     * @return mixed  An array containing the message counts with the forum name as
     *                the key | PEAR_Error
     */
    public function numMessagesBatch($forum_name, $scope = 'agora', $thread_id = null)
    {
        $forums = &Agora_Messages::singleton($scope);
        if ($forums instanceof PEAR_Error) {
            return $forums;
        }

        $results = array();
        foreach ($forum_name as $forum) {
            if (($forum_id = $forums->getForumId($forum)) instanceof PEAR_Error) {
                // In case of error, just return zero but log the error - so
                // the calling app always gets an array with all the image ids.
                Horde::logMessage($forum_id, 'ERR');
                $results[$forum] = 0;
            } elseif (empty($forum_id)) {
                $results[$forum] = 0;
            } else {
                $messages = Agora_Messages::singleton($scope, $forum_id);
                if ($messages instanceof PEAR_Error) {
                    return $messages;
                }
                $results[$forum] = ($thread_id === null) ? $messages->_forum['message_count'] : $messages->countThreads($thread_id);
            }
        }
        return $results;
    }

    /**
     * Returns all threads of a forum in a threaded view.
     *
     * @param string  $forum_name     The unique name for the forum.
     * @param boolean $bodies         Whether to include message bodies in the view.
     * @param string  $scope          The application that the specified forum belongs to.
     * @param string  $base_url       An alternate link where edit/delete/reply links
     *                                point to.
     * @param string  $template_file  Template file to use.
     *
     * @return string  The HTML code of the thread view.
     */
    public function renderThreads($forum_name, $bodies = false, $scope = 'agora', $base_url = null, $template_file = false)
    {
        /* An agora parameter may already be present. If so it would
         * interfere; remove it. */
        if ($base_url) {
            $base_url = Horde_Util::removeParameter($base_url, array('agora', 'message_parent_id', 'delete'));
        }

        $threads = $this->getThreads($forum_name, 'message_thread', 0, $bodies, $scope, $base_url);
        if (!count($threads)) {
            return '';
        }

        $col_headers = array(
            'message_thread' => _("Subject"),
            'message_thread_class_plain' => 'msgThreadPlain',
            'message_author' => _("Posted by"),
            'message_author_class_plain' => 'msgAuthorPlain',
            'message_timestamp' => _("Date"),
            'message_timestamp_class_plain' => 'msgTimestampPlain'
        );

        $forums = &Agora_Messages::singleton($scope);
        $forum_id = $forums->getForumId($forum_name);

        $messages = &Agora_Messages::singleton($scope, $forum_id);
        if ($messages instanceof PEAR_Error) {
            return $messages;
        }
        return '<h1 class="header">' . _("Comments") . '</h1>' .
            $messages->getThreadsUI($threads, $col_headers, $bodies, $template_file);
    }


    /**
     * Allows other Horde apps to add/edit messages.
     *
     * The forum name is constructed by just the $forum_name variable
     * under the data root 'agora.forums.<app>'. It is up to the apps
     * themselves to make sure that the forum name is unique.
     *
     * If the forum does not exist, it will be automatically created by
     * Agora.
     *
     * @access private
     *
     * @param string $scope       The application which is posting this message.
     * @param string $forum_name  The unique name for the forum.
     * @param string $callback    A callback method of the specified application
     *                            that gets called to make sure that posting to
     *                            this forum is allowed.
     * @param array $params       Any parameters for the forum message posting.
     * <pre>
     * message_id        - An existing message to edit
     * message_parent_id - The ID of the parent message
     * message_body      - Message body
     * </pre>
     *
     * @return mixed  Returns message id if the message was posted
     *                or PEAR_Error object on error
     */
    public function addMessage($scope, $forum_name, $callback, $params = array())
    {
        global $registry;

        /* Check if adding messages is allowed. */
        $check = $registry->callByPackage($scope, $callback, array($forum_name));
        if ($check instanceof PEAR_Error || !$check) {
            return '';
        }

        /* Check if the forum exists and fetch the ID, or create a new one. */
        $forums = &Agora_Messages::singleton($scope);
        if (($params['forum_id'] = $forums->getForumId($forum_name)) instanceof PEAR_Error) {
            return $params['forum_id'];
        } elseif (empty($params['forum_id'])) {
            $forum_info = $this->prepareFormData($scope, false, array('forum_name' => $forum_name), $callback);
            if ($forum_info instanceof PEAR_Error) {
                return $forum_info;
            }
            $params['forum_id'] = $forums->saveForum($forum_info);
            if ($params['forum_id'] instanceof PEAR_Error) {
                return $params['forum_id'];
            }
        }

        /* Set up the messages control object. */
        $messages = &Agora_Messages::singleton($scope, $params['forum_id']);
        if ($messages instanceof PEAR_Error) {
            return $messages;
        }

        return $messages->saveMessage($params);
    }

    /**
     * Allows other Horde apps to post messages.
     *
     * The forum name is constructed by just the $forum_name variable under the
     * data root 'agora.forums.<app>'. It is up to the apps themselves to make
     * sure that the forum name is unique.
     *
     * If the forum does not exist, it will be automatically created by Agora.
     *
     * @access private
     *
     * @param string $scope       The application which is posting this message.
     * @param string $forum_name  The unique name for the forum.
     * @param string $callback    A callback method of the specified application
     *                            that gets called to make sure that posting to
     *                            this forum is allowed.
     * @param array $params       Any parameters for the forum message posting.
     * <pre>
     * message_id        - An existing message to edit
     * message_parent_id - The ID of the parent message
     * title             - Posting title
     * </pre>
     * @param string $url         If specified, the form gets submitted to this URL
     *                            instead of the current page.
     * @param array $variables    A hash with all variables of a submitted form
     *                            generated by this method.
     *
     * @return mixed  Returns either the rendered Horde_Form for posting a message
     *                or PEAR_Error object on error, or true in case of a
     *                successful post.
     */
    public function postMessage($scope, $forum_name, $callback, $params = array(),
                                $url = null, $variables = null)
    {
        global $registry;

        /* Check if posting messages is allowed. */
        $check = $registry->callByPackage($scope, $callback, array($forum_name));
        if ($check instanceof PEAR_Error || !$check) {
            return '';
        }

        /* Create a separate notification queue. */
        $queue = Horde_Notification::singleton('agoraPostMessage');
        $queue->attach('status');

        /* Set up the forums object. */
        $forums = &Agora_Messages::singleton($scope);

        /* Set up form variables. */
        $vars = Horde_Variables::getDefaultVariables();
        if (is_array($variables)) {
            foreach ($variables as $varname => $value) {
                $vars->add($varname, $value);
            }
        }
        $formname = $vars->get('formname');

        /* Check if the forum exists and fetch the ID. */
        $params['forum_id'] = $forums->getForumId($forum_name);
        if ($params['forum_id'] === null) {
            $vars->set('new_forum', $forum_name);
        } else {
            $vars->set('forum_id', $params['forum_id']);
        }

        /* Set up the messages control object. */
        $messages = &Agora_Messages::singleton($scope, $params['forum_id']);
        if ($messages instanceof PEAR_Error) {
            $queue->push(_("Could not post the message: ") . $messages->getMessage(), 'horde.error');

            Horde::startBuffer();
            $queue->notify(array('listeners' => 'status'));
            return Horde::endBuffer();
        }

        /* Check post permissions. */
        if (!$messages->hasPermission(Horde_Perms::EDIT)) {
            Horde::permissionDeniedError('agora', null);
            return PEAR::raiseError(sprintf(_("You don't have permission to post messages in forum %s."), $params['forum_id']));
        }

        if (isset($params['message_id'])) {
            $message = $messages->getMessage($params['message_id']);
            if (!$formname) {
                $vars = new Horde_Variables($message);
                $vars->set('message_subject', $message['message_subject']);
                $vars->set('message_body', $message['body']);
            }
            $editing = true;
        } else {
            $editing = false;
            $params['message_id'] = null;
        }

        /* Set a default title if one not specified. */
        if (!isset($params['title'])) {
            $params['title'] = ($editing) ? _("Edit Message") : _("Post a New Message");
        }

        /* Get the form object. */
        $form = $messages->getForm($vars, $params['title'], $editing, is_null($params['forum_id']));

        /* Validate the form. */
        if ($form->validate($vars)) {
            $form->getInfo($vars, $info);

            if (isset($info['new_forum'])) {
                $forum_info = $this->prepareFormData($scope, false, array('forum_name' => $info['new_forum']), $callback);
                if ($forum_info instanceof PEAR_Error) {
                    return $forum_info;
                }
                $info['forum_id'] = $m_params['forum_id'] = $forums->saveForum($forum_info);
                $result = &Agora_Messages::singleton($scope, $info['forum_id']);
                if ($result instanceof PEAR_Error) {
                    return $result;
                }
            }

            /* Try and store this message and get back a new message_id */
            $message_id = $messages->saveMessage($info);
            if ($message_id instanceof PEAR_Error) {
                $queue->push(_("Could not post the message: ") . $message_id->getMessage(), 'horde.error');
            } else {
                $queue->push(_("Message posted."), 'horde.success');
                $count = $messages->countMessages();
                $registry->callByPackage($scope, $callback, array($forum_name, 'messages', $count));

                Horde::startBuffer();
                $queue->notify(array('listeners' => 'status'));
                return Horde::endBuffer();
            }
        }

        /* Replying to a previous post? */
        if (isset($params['message_parent_id']) && !$form->isSubmitted()) {
            $message = $messages->replyMessage($params['message_parent_id']);
            if (!($message instanceof PEAR_Error)) {
                $vars->set('message_parent_id', $params['message_parent_id']);
                $vars->set('message_subject', $message['message_subject']);
                $vars->set('message_body', $message['body']);
            } else {
                /* Bad parent message id, offer to do a regular post. */
                $vars->set('message_parent_id', '');
            }
        }

        if (!$url) {
            $url = Horde::selfUrl(true, false, true);
        }

        Horde::startBuffer();
        $form->renderActive(null, $vars, $url, 'post', null, false);
        return Horde::endBuffer();
    }

    /**
     * Allows other Horde apps to remove messages.
     *
     * The forum name is constructed by just the $forum_name variable
     * under the data root 'agora.forums.<app>'. It is up to the apps
     * themselves to make sure that the forum name is unique.
     *
     * @access private
     *
     * @param string $scope       The application which is posting this message.
     * @param string $forum_name  The unique name for the forum.
     * @param string $callback    A callback method of the specified application
     *                            that gets called to make sure that posting to
     *                            this forum is allowed.
     * @param array $params       Any parameters for the forum message posting.
     * <pre>
     * message_id        - An existing message to delete
     * </pre>
     * @param array $variables    A hash with all variables of a submitted form
     *                            generated by this method.
     *
     * @return mixed  Returns either the rendered Horde_Form for posting a message
     *                or PEAR_Error object on error, or true in case of a
     *                successful post.
     */
    public function removeMessage($scope, $forum_name, $callback, $params = array(),
                                $variables = null)
    {
        global $registry;

        /* Check if posting messages is allowed. */
        $check = $registry->callByPackage($scope, $callback, array($forum_name));
        if ($check instanceof PEAR_Error || !$check) {
            return '';
        }

        /* Create a separate notification queue. */
        $queue = Horde_Notification::singleton('agoraRemoveMessage');
        $queue->attach('status');

        /* Set up the forums object. */
        $forums = &Agora_Messages::singleton($scope);
        $params['forum_id'] = $forums->getForumId($forum_name);
        if (empty($params['forum_id'])) {
            return PEAR::raiseError(sprintf(_("Forum %s does not exist."), $forum_name));
        }

        /* Set up the messages control object. */
        $messages = &Agora_Messages::singleton($scope, $params['forum_id']);
        if ($messages instanceof PEAR_Error) {
            PEAR::raiseError(sprintf(_("Could not delete the message. %s"), $messages->getMessage()));
        }

        /* Check delete permissions. */
        if (!$messages->hasPermission(Horde_Perms::DELETE)) {
            return PEAR::raiseError(sprintf(_("You don't have permission to delete messages in forum %s."), $params['forum_id']));
        }

        /* Get the message to be deleted. */
        $message = $messages->getMessage($params['message_id']);
        if ($message instanceof PEAR_Error) {
            return PEAR::raiseError(sprintf(_("Could not delete the message. %s"), $message->getMessage()));
        }

        /* Set up the form. */
        $vars = new Horde_Variables($variables);
        $form = new Horde_Form($vars, sprintf(_("Delete \"%s\" and all replies?"), $message['message_subject']), 'delete_agora_message');
        $form->setButtons(array(_("Delete"), _("Cancel")));
        $form->addHidden('', 'forum_id', 'int', true);
        $form->addHidden('', 'message_id', 'int', true);

        if ($form->validate()) {
            if ($vars->get('submitbutton') == _("Delete")) {
                $result = $messages->deleteMessage($params['message_id']);
                if ($result instanceof PEAR_Error) {
                    $queue->push(sprintf(_("Could not delete the message. %s"), $result->getMessage()), 'horde.error');
                } else {
                    $queue->push(_("Message deleted."), 'horde.success');
                    $count = $messages->countMessages();
                    $registry->callByPackage($scope, $callback, array($forum_name, 'messages', $count));
                }
            } else {
                $queue->push(_("Message not deleted."), 'horde.message');
            }

            Horde::startBuffer();
            $queue->notify(array('listeners' => 'status'));
            return Horde::endBuffer();
        }

        Horde::startBuffer();
        $form->renderActive(null, null, null, 'post', null, false);
        return Horde::endBuffer();
    }

    /**
     * Allows other Horde apps to post messages.
     *
     * In most apps we use the same code to make comments possible. This function
     * does most of that. Allow comments to be added to any app. The app itself
     * should check if the agora api is present, determine a key and call this
     * function before app::menu is called (before any output has started. At the
     * end of its output it can print the array returned to show the comments.
     *
     * @access private
     *
     * @param string $scope          The application which is posting this message.
     * @param string $key            Unique key from the object (picture etc we're
     *                               viewing. It will be used as the forum name.
     * @param string $callback       A callback method of the specified application
     *                               that gets called to make sure that posting to
     *                               this forum is allowed.
     * @param boolean $body          Show the comment bodies in the thread view or
     *                               not.
     * @param string $base_url       Base URL the edit/delete/reply links should
     *                               point to.
     * @param string $url            If specified, the form gets submitted to this
     *                               URL instead of the current page.
     * @param array $variables       A hash with all variables of a submitted form
     *                               generated by this method.
     * @param string $template_file  Template file to use.
     *
     * @return mixed array  Returns either the rendered Horde_Form for comments
     *                      and threads for posting/viewing a message or PEAR
     *                      objects on error.
     */
    public function doComments($scope, $key, $callback, $bodies = true,
                            $base_url = null, $url = null, $variables = null,
                            $template_file = false)
    {
        if (is_null($base_url)) {
            $base_url = Horde::selfUrl(true);
        }

        list($forum_id, $message_id) = Agora::getAgoraId();

        $params = array();
        if ($message_id) {
            $params['message_id'] = $message_id;
        }

        if ($parent = Horde_Util::getFormData('message_parent_id')) {
            $params['message_parent_id'] = $parent;
        }

        // See if we're editing.
        if (isset($params['message_id'])) {
            $params['title'] = _("Edit a comment");
        } else {
            $params['title'] = _("Add a comment");
            $params['message_id'] = null;
        }

        if (Horde_Util::getFormData('delete') === null) {
            $comments = $this->postMessage($scope, $key, $callback, $params, $url, $variables);
        } else {
            $comments = $this->removeMessage($scope, $key, $callback, $params, $url, $variables);
        }

        if ($comments instanceof PEAR_Error) {
            return $comments;
        }

        include AGORA_BASE . '/lib/Comments.php';
        $threads = Agora_ViewComments::render($key, $scope, $base_url, $template_file);

        if ($threads instanceof PEAR_Error) {
            $threads = $threads->getMessage();
        }
        if ($comments instanceof PEAR_Error) {
            $comments = $comments->getMessage();
        }

        return array('threads' => $threads, 'comments' => $comments);
    }

    /**
     * Fill up a form data array.
     *
     * @param string $scope     The Horde application that is saving this forum.
     * @param string $parent    The parent forum.
     * @param array  $info      The forum information to consisting of:
     *                              forum_parent_id
     *                              forum_name
     *                              forum_description
     *                              forum_moderated
     *                              forum_attachments
     * @param string $callback  A callback method of the specified application
     *                          that gets called to make sure that posting to
     *                          this forum is allowed.
     */
    public function prepareFormData($scope, $parent = false, $info = array(), $callback = null)
    {
        $forums = &Agora_Messages::singleton($scope);

        if ($parent) {
            $parent_id = $forums->getForumId($parent);
            $parent_form = $forums->getForum($parent_id);
            $info['forum_parent_id'] = $parent_id;
            if (!isset($info['forum_moderated'])) {
                $info['forum_moderated'] = $parent_form->isModerated();
            }
            if (!isset($info['forum_attachments'])) {
                $info['forum_attachments'] = $parent_form->forum->data['forum_attachments'];
            }
        } elseif (isset($info['forum_name'])) {
            $forum_id = $forums->getForumId($info['forum_name']);
            if (!empty($forum_id)) {
                $forum = $forums->getForum($forum_id);
                $info = array_merge($forum, $info);
            }
        }

        if (!isset($info['forum_parent_id'])) {
            $info['forum_parent_id'] = 0;
        }

        if (!isset($info['forum_attachments'])) {
            $info['forum_attachments'] = ($GLOBALS['conf']['forums']['enable_attachments'] == '-1') ? false : true;
        }

        if (!isset($info['forum_moderated'])) {
            $info['forum_moderated'] = false;
        }

        if (!isset($info['forum_description'])) {
            $info['forum_description'] = '';
        }

        if (!isset($info['author'])) {
            $info['author'] = '';
        }

        if ($callback) {
            /* Get the data owner */
            if (empty($info['author'])) {
                $info['author'] = $GLOBALS['registry']->callByPackage($scope, $callback, array($info['forum_name'], 'owner'));
                if ($info['author'] instanceof PEAR_Error) {
                    return $info['author'];
                }
            }

            /* Get description */
            if (empty($info['forum_description'])) {
                $info['forum_description'] = $GLOBALS['registry']->callByPackage($scope, $callback, array($info['forum_name']));
                if ($info['forum_description'] instanceof PEAR_Error) {
                    return $info['forum_description'];
                }
            }
        }

        return $info;
    }

    /**
     * Prepare the moderate form
     *
     * @param string $scope     The Horde application that is saving this forum.
     */
    public function moderateForm($scope)
    {
        global $notification, $prefs, $registry;

        $api_call = true;

        return require AGORA_BASE . '/moderate.php';
    }
}
