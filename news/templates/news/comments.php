<br />
<?php
// show comments only of allowd for api, for current news and if agora exists
if ($conf['comments']['allow'] != 'never' &&
    $row['comments']>-1 &&
    $registry->hasMethod('forums/doComments')) {

    $params = array('news', $id, 'commentCallback', true, null, null,
                    array('message_subject' => $row['title']), $conf['comments']['comment_template']);

    $comments = $registry->call('forums/doComments', $params);

    if (!empty($comments['threads'])) {
        echo $comments['threads'];
    }

    if (!empty($comments['comments'])) {
        echo $comments['comments'];
    }
}

