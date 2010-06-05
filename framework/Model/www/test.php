<?php
/**
 * Incubator Horde_Form rewrite example page.
 *
 * The initial Horde_Form xhtml rewrite was supported by Google SoC
 * 2005.
 */

$horde_base = '/path/to/horde';

require_once $horde_base . '/lib/Application.php';
Horde_Registry::appInit('horde', array('authentication' => 'none'));

$vars = Horde_Variables::getDefaultVariables();

$vars->set('example_bar', 'text with a beginning and an end');
$form = new Horde_Form($vars, 'Horde_Form Test');

$choices = array('big' => 'BIG',
                 'small' => 'small',
                 'other' => 'Other');
$form->add('condchoices', 'Enum', _("Select something"), '', true, false, array($choices, true));

$o = $form->add('other_text', 'String', _("If other, please describe"), '', false);
$params = array('target' => 'condchoices',
                'enabled' => true,
                'values' => array('other'));
$o->setAction(new Horde_Form_Action_ConditionalEnable($params));

$form->add('color', 'Color', _("Color"), null, false);

$vars->set('form', 'add');
$enum = array('' => _("Select:"),
              1 => _("Yes"),
              0 => _("No"));
$form->add('opciones', 'Enum', _("Simple description"), '', true, false, array($enum));
$form->add('bool', 'Boolean', _("Boolean"));
$form->add('number', 'Int', _("Integer"));
$form->add('mybday', 'date', _("A Date"), '', false);
$form->addHidden('form', 'String', true);
$unamevar = $form->add('user_name', 'String', _("Username"));
$form->add('password', 'password', _("Password"));
$form->addHidden('example_hidden', 'int', false);
$form->add('some_text', 'String', _("Insert some text"), _("Insert some text in this box"), false);
$choices = array('big' => 'BIG',
                 'small' => 'small',
                 'mixed' => 'mIxED');
$form->add('choices', 'enum', _("Select something2"), 'Use the selection box to make your choice', true, false, array($choices, true));
$form->add('email_address', 'email', _("Email"));
$form->add('email_address2', 'emailconfirm', _("Email2"));
$form->add('a_creditcard', 'creditcard', _("Credit Card"));
$form->add('a_password', 'password', _("Password"));
$form->add('a_password2', 'passwordconfirm', _("Password with confirmation"), _("type the password twice to confirm"));
$form->add('a_octal', 'Octal', _("Octal"), false);
$form->add('a_radiogroup', 'set', _("Radio Group"), '', true, false, array($choices));

$t = $form->add('example_bar', 'String', _("Bar field"), _("You have to fill in some long text here"), true, false, array(4, 40));
$t->setAction(new Horde_Form_Action_setcursorpos(array(4)));

$form->add('a_checkboxgroup', 'set', _("Checkbox Group"), '', false, false, array($choices));
//$form->add('a_obrowser', 'obrowser', _("obrowser"));

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<title>Incubator Horde_Form Test</title>
<link rel="stylesheet" type="text/css" href="themes/form.css" />
<script type="text/javascript" src="<?=$registry->get('jsuri', 'horde')?>/form_helpers.js"></script>
</head>
<body>
<?php
if ($form->validate()) {
    $form->getInfo($info);
    echo 'You have submitted:<br /><pre>';
    var_dump($info);
    echo '</pre>';
}

/* Render the form. */
$renderer = new Horde_Form_Renderer_Xhtml;
$renderer->setButtons(_("Add user"), _("Reset"));
$renderer->renderActive($form, 'test.php', 'post');

?>
</body>
</html>
