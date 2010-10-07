<div class="file-view">
<h3 class="file-view-header"><?php echo $this->h($this->title) ?></h3>
<div class="file-view-contents">
<?php
if (strpos($this->mimeType, 'text/plain') !== false) {
    $data = $this->pretty->render('inline');
    $data = reset($data);
    echo '<div class="fixed">' . $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($data['data'], 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO)) . '</div>';
} elseif (strpos($this->mimeType, 'image/') !== false) {
    echo Horde::img(Horde_Util::addParameter(Horde::selfUrl(true), 'p', 1), '', '', '');
} elseif ($this->pretty->canRender('inline')) {
    $data = $this->pretty->render('inline');
    $data = reset($data);
    echo $data['data'];
} else {
    echo Horde::link(Horde_Util::addParameter(Horde::selfUrl(true), 'p', 1)) . Horde::img('download.png') . ' ' . sprintf(_("Download revision %s"), $r) . '</a>';
}
?>
</div>
</div>
