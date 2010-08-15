<?php
/**
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @package Whups
 */
class Horde_Form_Renderer_Comment extends Horde_Form_Renderer {

    var $queue = null;
    var $type = null;
    var $state = null;
    var $priority = null;
    var $due = null;

    function begin($title)
    {
        $this->_sectionHeader($title);
        echo '<div id="comments">';
    }

    function render($transaction, &$vars)
    {
        global $prefs, $conf, $registry;
        static $canUpdate, $comment_count = 0;

        if (!isset($canUpdate)) {
            $canUpdate = $GLOBALS['registry']->getAuth() &&
                Whups::hasPermission($vars->get('queue'), 'queue', 'update');
        }

        $comment = '';
        $private = false;
        $changes = array();

        $changelist = $vars->get('changes');
        if (!$changelist) {
            return '';
        }

        /* Format each change in this history entry, including comments,
         * etc. */
        foreach ($changelist as $change) {
            switch ($change['type']) {
            case 'summary':
                $changes[] = sprintf(_("Summary &rArr; %s"),
                                     htmlspecialchars($change['value']));
                break;

            case 'attachment':
                $ticket = $vars->get('ticket_id');
                if ($file = Whups::getAttachments($ticket, $change['value'])) {
                    $changes[] = sprintf(_("New Attachment: %s"),
                                         Whups::attachmentUrl($ticket, $file,
                                                              $vars->get('queue')));
                } else {
                    $changes[] = sprintf(_("New Attachment: %s"),
                                         htmlspecialchars($change['value']));
                }
                break;

            case 'delete-attachment':
                $changes[] = sprintf(_("Deleted Attachment: %s"),
                                     htmlspecialchars($change['value']));
                break;

            case 'assign':
                $changes[] = sprintf(_("Assigned to %s"),
                                     Whups::formatUser($change['value'], false,
                                                       true, true));
                break;

            case 'unassign':
                $changes[] = sprintf(_("Taken from %s"),
                                     Whups::formatUser($change['value'], false,
                                                       true, true));
                break;

            case 'comment':
                $comment = $change['comment'];
                $private = !empty($change['private']);
                if ($comment) {
                    $reply =
                        Horde::link(
                            Horde::applicationUrl(
                                Horde_Util::addParameter(
                                    $canUpdate ? 'ticket/update.php' : 'ticket/comment.php',
                                    array('id' => $vars->get('ticket_id'),
                                          'transaction' => $transaction))))
                        . _("Reply to this comment") . '</a>';
                }
                break;

            case 'queue':
                $changes[] = sprintf(_("Queue &rArr; %s"),
                                     htmlspecialchars($change['label']));
                break;

            case 'version':
                $changes[] = sprintf(_("Version &rArr; %s"),
                                     htmlspecialchars($change['label']));
                break;

            case 'type':
                $changes[] = sprintf(_("Type &rArr; %s"),
                                     htmlspecialchars($change['label']));
                break;

            case 'state':
                $changes[] = sprintf(_("State &rArr; %s"),
                                     htmlspecialchars($change['label']));
                break;

            case 'priority':
                $changes[] = sprintf(_("Priority &rArr; %s"),
                                     htmlspecialchars($change['label']));
                break;

            case 'attribute':
                $changes[] = sprintf(_("%s &rArr; %s"),
                                     htmlspecialchars($change['label']),
                                     htmlspecialchars($change['value']));
                break;

            case 'due':
                if ($change['label']) {
                    $changes[] = sprintf(
                        _("Due &rArr; %s"),
                        strftime($prefs->getValue('date_format'),
                                 $change['label']));
                }
                break;
            }
        }

        if ($comment) {
            $flowed = new Horde_Text_Flowed($comment);
            $flowed->setDelSp(true);
            $comment = $flowed->toFlowed(false);
            $comment = $GLOBALS['injector']->getInstance('Horde_Text_Filter')->filter(
                $comment, array('text2html', 'simplemarkup', 'highlightquotes'),
                array(array('parselevel' => Horde_Text_Filter_Text2html::MICRO),
                      array(), array()));
            if ($prefs->getValue('autolink_tickets') &&
                $conf['prefs']['autolink_terms']) {
                $term_regex = '/(' . $conf['prefs']['autolink_terms']
                    . ')\s*#?(\d+)/i';
                $comment = preg_replace_callback($term_regex,
                                                 array(&$this, '_autolink'),
                                                 $comment);
            }

            $comment_count++;
            if ($private) {
                $comment_label = Horde::img('locked.png')
                    . sprintf(_("Comment #%d (Private)"), $comment_count);
            } else {
                $comment_label = sprintf(_("Comment #%d"), $comment_count);
            }
            array_unshift($changes, '<a href="#c' . $comment_count . '" id="c'
                                    . $comment_count . '">'
                                    . $comment_label
                                    . '</a>');
        }

        if (count($changes)) {
            // Admins can delete entries.
            $delete_link = '';
            if (Whups::hasPermission($vars->get('queue'), 'queue', Horde_Perms::DELETE)) {
                $delete_link = Horde::applicationUrl('ticket/delete_history.php')
                    ->add(array('transaction' => $transaction,
                                'id' => $vars->get('ticket_id'),
                                'url' => Whups::urlFor('ticket', $vars->get('ticket_id'), true)))
                    ->link(array('title' => _("Delete entry"), 'onclick' => 'return window.confirm(\'' . addslashes(_("Permanently delete entry?")) . '\');'))
                    . Horde::img('delete.png', _("Delete entry"))
                    . '</a>';
            }

            Horde::startBuffer();
            $class = $private ? 'pc' : 'c';
?>
<div id="t<?php echo (int)$transaction ?>">
<table cellspacing="0" width="100%">
 <tr>
  <td width="20%" class="<?php echo $class ?>_l nowrap" valign="top"><?php echo strftime($prefs->getValue('date_format'), $vars->get('timestamp')) ?></td>
  <td width="20%" class="<?php echo $class ?>_m" valign="top"><?php echo $vars->get('user_id') ? Whups::formatUser($vars->get('user_id'), false, true, true) : '&nbsp;' ?></td>
  <td width="30%" class="<?php echo $class ?>_m" valign="top"><?php echo implode('<br />', $changes) ?></td>
  <td width="30%" class="<?php echo $class ?>_r rightAlign" valign="top"><?php if ($comment && !$private) echo $reply . ' '; echo $delete_link; ?></td>
 </tr>
<?php if ($comment): ?>
 <tr><td colspan="4" class="<?php echo $class ?>_b">
  <div class="comment-body">
   <?php echo $comment ?>
  </div>
 </td></tr>
<?php else: ?>
 <tr><td colspan="4" class="c_b">&nbsp;</td></tr>
<?php endif; ?>
</table>
</div>
<?php
            $html = Horde::endBuffer();
            return $html;
        }

        return '';
    }

    function _autolink($matches)
    {
        $url = Whups::urlFor('ticket', $matches[2]);
        $link = '<strong>' . Horde::link($url, 'View ' . $matches[0])
            . $matches[0] . '</a></strong>';
        $state = $GLOBALS['whups_driver']->getTicketState($matches[2]);
        if (!is_a($state, 'PEAR_Error') &&
            $state['state_category'] == 'resolved') {
            $link = '<del>' . $link . '</del>';
        }
        return $link;
    }

    function end()
    {
        echo '</div>';
    }

}
