<?php
echo $tabs->render();

if (isset($form)) {
    $form->renderActive(null, null, null, 'post');
}

echo '<h1 class="header">' . sprintf(_("%s: faces %s"), $title, number_format($count)) . '</h1>';
if (empty($results)) {
    echo _("No faces found");
} else {
    echo $pager->render();
    echo '<div style="float:left;width:100%;">';
    foreach ($results as $face_id => $face) {
        include ANSEL_TEMPLATES . '/tile/face.inc';
    }
    echo '</div>';
    echo $pager->render();
}
