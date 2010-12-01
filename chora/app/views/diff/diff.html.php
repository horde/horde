<div class="diff"<?php if ($id) echo ' id="' . $id . '"' ?>>
<div class="diff-header"><h4><a href="<?php echo Chora::url('co', $file->queryModulePath(), array('onb' => $r1)) ?>"><?php echo $file->queryModulePath() ?></a></h4></div>
<?php foreach ($diff as $section): ?>
<div class="diff-container diff-section">
 <div class="diff-left"><h3><?php printf(_("Line %s"), $section['oldline']) ?></h3></div>
 <div class="diff-right"><h3><?php printf(_("Line %s"), $section['newline']) ?></h3></div>
</div>
<?php
foreach ($section['contents'] as $change) {
    if ($this->hasContext() && $change['type'] != 'empty') {
        echo $this->diffContext();
    }

    $method = 'diff' . ucfirst($change['type']);
    echo $this->$method($change);
}

if ($this->hasContext()) {
    echo $this->diffContext();
}

endforeach;
?>
</div>
