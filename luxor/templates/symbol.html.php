<div id="headerbar">
<h1 class="header">
 <?= $this->escape($this->title) ?>
</h1>
</div>

<div id="symbol-declarations">
<h2><?= _("Declarations") ?></h2>
<? foreach ($this->declarations as $declaration): ?>
 <dl class="box">
  <dt><?= $this->escape($declaration['title']) ?></dt>
  <? foreach ($declaration['files'] as $file): ?>
   <dd><?= $file ?></dd>
  <? endforeach ?>
 </dl>
<? endforeach ?>
</div>

<div id="symbol-references">
<h2><?= _("Referenced in") ?></h2>
<? foreach ($this->references as $reference): ?>
 <dl class="box">
  <dt><?= $reference['file'] ?></dt>
   <? foreach ($reference['lines'] as $line): ?>
   <dd><?= $line ?></dd>
   <? endforeach ?>
 </dl>
<? endforeach ?>
</div>
