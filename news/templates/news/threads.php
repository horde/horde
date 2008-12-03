<br />
<?php
// show threads
if ($row['threads']) {

    echo '<strong>' . sprintf(_("Threads in %s"), $registry->get('name', 'agora')) . ':</strong>';
    $agore_link = $registry->get('webroot', 'agora');
    echo '<ul>';
    foreach ($row['threads'] as $thread_id => $thread) {
        echo '<li>- <a href="' . $agore_link . '/messages/index.php?scope=agora&agora=' . $thread['forum_id'] . '.' . $thread_id . '">'
                . $thread['message_subject'] . '</a> (' . $thread['message_seq'] . ')' . '</li>';
    }
    echo '</ul>';
}
