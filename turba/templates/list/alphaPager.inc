<?php if (count($this) > $prefs->getValue('perpage')): ?>
<div class="turbaPager">
<?php
$currentPage = Horde_String::upper($page);
if ($currentPage == '*') {
    echo '<strong>(' . _("All") . ')</strong>&nbsp;';
} else {
    echo $viewurl->copy()->add('page', '*')->link() . _("All") . '</a>&nbsp;';
}
for ($i = 65; $i < 91; $i++) {
    $a = chr($i);
    if ($currentPage == $a) {
        echo '<strong>(' . $a . ')</strong>&nbsp;';
    } else {
        echo $viewurl->copy()->add('page', $a)->link() . $a . '</a>&nbsp;';
    }
}
?>
</div>
<?php endif; ?>
