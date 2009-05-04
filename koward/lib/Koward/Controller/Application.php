<?php

class Koward_Controller_Application extends Horde_Controller_Base
{
    protected $welcome;

    protected function _initializeApplication()
    {
        global $registry;

        if (is_a(($pushed = $registry->pushApp('koward',
                                               empty($this->auth_handler)
                                               || $this->auth_handler != $this->params[':action'])), 'PEAR_Error')) {
            if ($pushed->getCode() == 'permission_denied') {
                header('Location: ' . $this->urlFor(array('controller' => 'index', 'action' => 'login')));
                exit;
            }
        }

        $this->koward = Koward::singleton();

        if ($this->koward->objects instanceOf PEAR_Error) {
            return;
        }

        if (!empty($this->koward->objects)) {
            $this->types = array_keys($this->koward->objects);
        } else  {
            throw new Koward_Exception('No object types have been configured!');
        }

        $this->checkAccess();

        $this->menu = $this->getMenu();

        $this->theme = isset($this->koward->conf['koward']['theme']) ? $this->koward->conf['koward']['theme'] : 'koward';

        $this->welcome = isset($this->koward->conf['koward']['greeting']) ? $this->koward->conf['koward']['greeting'] : _("Welcome.");

    }

    /**
     * Builds Koward's list of menu items.
     */
    public function getMenu()
    {
        global $registry;

        require_once 'Horde/Menu.php';
        $menu = new Menu();

        if ($this->koward->hasAccess('object/listall')) {
            $menu->add($this->urlFor(array('controller' => 'object', 'action' => 'listall')),
                       _("_Objects"), 'user.png', $registry->getImageDir('horde'));
        }

        if ($this->koward->hasAccess('object/add', Koward::PERM_EDIT)) {
            $menu->add($this->urlFor(array('controller' => 'object', 'action' => 'add')),
                       _("_Add"), 'plus.png', $registry->getImageDir('horde'));
        }

        if ($this->koward->hasAccess('object/search')) {
            $menu->add($this->urlFor(array('controller' => 'object', 'action' => 'search')),
                       _("_Search"), 'search.png', $registry->getImageDir('horde'));
        }

        if (!empty($this->koward->conf['koward']['menu']['queries'])) {
            $menu->add(Horde::applicationUrl('Queries'), _("_Queries"), 'query.png', $registry->getImageDir('koward'));
        }
        if (!empty($this->koward->conf['koward']['menu']['test'])) {
            $menu->add($this->urlFor(array('controller' => 'check', 'action' => 'show')),
                   _("_Test"), 'problem.png', $registry->getImageDir('horde'));
        }
        if (Auth::getAuth()) {
            $menu->add($this->urlFor(array('controller' => 'index', 'action' => 'logout')),
                       _("_Logout"), 'logout.png', $registry->getImageDir('horde'));
        }
        return $menu;
    }

    public function getPermissionId()
    {
        return $this->params['controller'] . '/' . $this->params['action'];
    }

    public function checkAccess($id = null, $permission = Koward::PERM_SHOW)
    {
        if ($id === null) {
            $id = $this->getPermissionId();
        }

        if (!$this->koward->hasAccess($id, $permission)) {
            $this->koward->notification->push(_("Access denied."), 'horde.error');
            if (Auth::getAuth()) {
                $url = $this->urlFor(array('controller' => 'index', 'action' => 'index'));
            } else {
                $url = $this->urlFor(array('controller' => 'index', 'action' => 'login'));
            }
            header('Location: ' . $url);
            exit;
        }
    }
}
