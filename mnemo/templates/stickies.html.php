<style>
div.mnemo-stickies {
    font-family:arial,sans-serif;
    font-size:100%;
    color:#fff;
}
div.mnemo-stickies ul, li {
    list-style: none;
}
div.mnemo-stickies ul {
    overflow: hidden;
}
div.mnemo-stickies ul li a {
    text-decoration: none;
    display: block;
    height: 10em;
    width: 10em;
    padding: 1em;
    overflow: hidden;
    -moz-box-shadow:5px 5px 7px rgba(99,99,99,1);
    -webkit-box-shadow: 5px 5px 7px rgba(99,99,99,.7);
    box-shadow: 5px 5px 7px rgba(99,99,99,.7);
    -moz-transition:-moz-transform .15s linear;
    -o-transition:-o-transform .15s linear;
    -webkit-transition:-webkit-transform .15s linear;
}
div.mnemo-stickies ul li {
    margin: 1em;
    float: left;
}
div.mmemo-stickies ul li h2 {
    font-family: 'Nobile', arial, sans-serif;
    font-size: 140%;
    font-weight: bold;
    padding-bottom: 10px;
}
div.mnemo-stickies ul li p {
    font-family: 'Nobile', arial, sans-serif;
    font-size: 100%;
}
div.mnemo-stickies ul li:nth-child(even) a {
  -o-transform:rotate(2deg);
  -webkit-transform:rotate(2deg);
  -moz-transform:rotate(2deg);
  position:relative;
  top:5px;
}
div.mnemo-stickies ul li:nth-child(3n) a {
  -o-transform:rotate(-1deg);
  -webkit-transform:rotate(-1deg);
  -moz-transform:rotate(-1deg);
  position:relative;
  top:-5px;
}
div.mnemo-stickies ul li:nth-child(5n) a {
  -o-transform:rotate(3deg);
  -webkit-transform:rotate(3deg);
  -moz-transform:rotate(3deg);
  position:relative;
  top:-10px;
}
div.mnemo-stickies ul li a:hover, div.stickies ul li a:focus{
  -moz-box-shadow:10px 10px 7px rgba(66,66,66,.7);
  -webkit-box-shadow: 10px 10px 7px rgba(66,66,66,.7);
  box-shadow:10px 10px 7px rgba(66,66,66,.7);
  -webkit-transform: scale(1.25);
  -moz-transform: scale(1.25);
  -o-transform: scale(1.25);
  position:relative;
  z-index:5;
}
</style>

<link href="//fonts.googleapis.com/css?family=Nobile:regular,bold&subset=latin" rel="stylesheet" type="text/css">

<div class="mnemo-stickies">
<ul>
<?php
foreach ($memos as $memo_id => $memo) {
    $viewurl = Horde_Util::addParameter(
        'view.php',
        array('memo' => $memo['memo_id'],
              'memolist' => $memo['memolist_id']));

    $memourl = Horde_Util::addParameter(
        'memo.php', array('memo' => $memo['memo_id'],
                          'memolist' => $memo['memolist_id']));
    try {
        $share = $GLOBALS['mnemo_shares']->getShare($memo['memolist_id']);
        $notepad = $share->get('name');
    } catch (Horde_Share_Exception $e) {
        $notepad = $memo['memolist_id'];
    }

    if ($share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
    }

    $preview = ($memo['body'] != $memo['desc']) ? Mnemo::getNotePreview($memo) : '';
    $preview = trim(substr($preview, strlen($memo['desc'])));
?>
  <li>
    <a href="#" class="category<?php echo md5($memo['category']) ?>">
      <h2><?php echo htmlspecialchars($memo['desc']) ?></h2>
      <p><?php echo htmlspecialchars($preview) ?></p>
    </a>
  </li>
<?php
}
?>
</ul>
</div>
