<?php
$view = $injector->createInstance('Horde_View');
echo $view->renderPartial('app/views/logMessage', array('object' => $log));
?>

<div class="checkout"><?php echo $rendered ?></div>
