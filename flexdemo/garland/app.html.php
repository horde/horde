<?php
foreach ($this->app as $tpl) {
    echo $this->render($tpl . '.html.php');
}
