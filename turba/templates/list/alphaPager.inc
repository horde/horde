<?php if (count($this) > $prefs->getValue('perpage')): ?>
<div class="turbaPager">
<?php
$show = Horde_Util::getFormData('show', 'all');
$currentPage = Horde_String::upper($page);
if ($currentPage == '*') {
    echo '<strong>(' . _("All") . ')</strong>&nbsp;';
} else {
    echo Horde::url($viewurl)->add(array('page' => '*', 'show' => $show))->link() . _("All") . '</a>&nbsp;';
}
for ($i = 65; $i < 91; $i++) {
    $a = chr($i);
    if ($currentPage == $a) {
        echo '<strong>(' . $a . ')</strong>&nbsp;';
    } else {
        echo Horde::url($viewurl)->add(array('page' => $a, 'show' => $show))->link() . $a . '</a>&nbsp;';
    }
}
?>
</div>
<?php endif; ?>
