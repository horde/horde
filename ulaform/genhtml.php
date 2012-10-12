<?php
/**
 * The Ulaform script to generate the HTML to display a form in an external
 * HTML page.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Marko Djukic <marko@oblo.com>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('ulaform', array('admin' => true));

/* Get some variables. */
$changed_type = false;
$html = '';
$vars = Horde_Variables::getDefaultVariables();
$formname = $vars->get('formname');
$form_id = $vars->get('form_id');
$type = $vars->get('type');
$old_type = $vars->get('old_type');

/* Check if type has been changed. */
if ($type != $old_type && $formname) {
    $changed_type = true;
    $notification->push(_("Changed embed type."), 'horde.message');
}

/* Fetch the form details and set a nice title. */
$form_details = $injector->getInstance('Ulaform_Factory_Driver')->create()->getForm($form_id);
$title = sprintf(_("HTML Generation for \"%s\""), $form_details['form_name']);

$form = new Horde_Form($vars, $title);
$form->useToken(false);

/* Set up the form. */
$form->setButtons(_("Generate HTML"));
$form->addHidden('', 'form_id', 'int', false);
$form->addHidden('', 'old_type', 'text', false);

$embed_types = array('php_pear' => _("PHP using PEAR classes"), 'iframe' => _("iframe"));
$v = &$form->addVariable(_("Select how you wish to embed the form"), 'type', 'enum', true, false, null, array($embed_types, true));
$v->setAction(Horde_Form_Action::factory('submit'));

switch ($type) {
case 'php_pear':
    break;

case 'iframe':
    $form->addVariable(_("Name"), 'params[name]', 'text', true, false);
    $form->addVariable(_("Height"), 'params[height]', 'int', false, false);
    $form->addVariable(_("Width"), 'params[width]', 'int', false, false);
    break;
}

/* Set up the gateway choice fields. */
$vars->set('old_type', $type);

/* Check if submitted and validate. */
if ($formname && !$changed_type) {
    $form->validate($vars);

    if ($form->isValid()) {
        $form->getInfo($vars, $info);
        switch ($type) {
        case 'php_pear':
            $html = array(
                '&lt;?php',
                '$options[\'method\'] = \'post\';',
                '$data = $_POST;',
                '$data[\'form_params\'][\'url\'] = $_SERVER[\'SCRIPT_URI\'];',
                '$data[\'form_params\'][\'embed\'] = \'php\';',
                '$data[\'form_id\'] =  ' . $info['form_id'] . ';',
                'require_once \'HTTP/Request.php\';',
                '$http = new HTTP_Request(\'' . Horde::url('display.php', true, -1) . '\', $options);',
                '$http->addRawPostData($data);',
                '$http->sendRequest();',
                'if ($http->getResponseCode() != 200) echo \'Form not available.\';',
                'else echo $http->getResponseBody();',
                '?>',
            );
            break;

        case 'iframe':
            $html = array(
                        sprintf('&lt;iframe src="%s" name="%s" %s%s hspace="2" vspace="2" scrolling="auto" marginwidth="5" marginheight="5" frameborder="0">&lt;/iframe>',
                                Horde::url('display.php', true, -1)->add('form_id', $info['form_id']),
                                $info['params']['name'],
                                ($info['params']['width'] ? 'width="' . $info['params']['width'] . '" ' : ''),
                                ($info['params']['height'] ? 'height="' . $info['params']['height'] . '" ' : '')));
            break;
        }
    }
}

/* Render the form. */
$view = new Horde_View(array('templatePath' => ULAFORM_TEMPLATES));
Horde::startBuffer();
$form->renderActive(new Horde_Form_Renderer(), $vars, Horde::url('genhtml.php'), 'post');
$view->inputform = Horde::endBuffer();
$view->html = $html;

$page_output->header(array(
    'title' => $title
));
$notification->notify(array('listeners' => 'status'));
echo $view->render('genhtml');
$page_output->footer();
