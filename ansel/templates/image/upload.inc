<?php
$form->renderActive(null, null, Horde::url('img/upload.php'), 'post', 'multipart/form-data');
$lbcssurl = Horde::url($GLOBALS['registry']->get('themesuri', 'ansel') . '/lightbox.css', true);
