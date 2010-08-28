<?php
/**
 * The Turba_Object:: class provides a base implementation for Turba
 * objects - people, groups, restaurants, etc.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jon Parise <jon@csh.rit.edu>
 * @package Turba
 */
class Turba_Object {

    /**
     * Underlying driver.
     *
     * @var Turba_Driver
     */
    var $driver;

    /**
     * Hash of attributes for this contact.
     *
     * @var array
     */
    var $attributes;

    /**
     * Reference to this object's VFS instance.
     *
     * @var VFS
     */
    var $_vfs;

    /**
     * Keeps the normalized values of sort columns.
     *
     * @var array
     */
    var $sortValue = array();

    /**
     * Constructs a new Turba_Object object.
     *
     * @param Turba_Driver $driver  The source that this object came from.
     * @param array $attributes     Hash of attributes for this object.
     */
    function Turba_Object(&$driver, $attributes = array())
    {
        $this->driver = &$driver;
        $this->attributes = $attributes;
        $this->attributes['__type'] = 'Object';
    }

    /**
     * Returns a key-value hash containing all properties of this object.
     *
     * @return array  All properties of this object.
     */
    function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Returns the name of the address book that this object is from.
     */
    function getSource()
    {
        return $this->driver->getName();
    }

    /**
     * Get a fully qualified key for this contact.
     *
     * @param string $delimiter Delimiter for the parts of the key, defaults to ':'.
     *
     * @return string Fully qualified contact id.
     */
    function getGuid($delimiter = ':')
    {
        return 'turba' . $delimiter . $this->getSource() . $delimiter . $this->getValue('__uid');
    }

    /**
     * Returns the value of the specified attribute.
     *
     * @param string $attribute  The attribute to retrieve.
     *
     * @return mixed  The value of $attribute, an array (for photo type)
     *                or the empty string.
     */
    public function getValue($attribute)
    {
        if (isset($this->attributes[$attribute])) {
            try {
                return Horde::callHook('decode_attribute', array($attribute, $this->attributes[$attribute]), 'turba');
            } catch (Horde_Exception_HookNotSet $e) {
            } catch (Turba_Exception $e) {}
        }

        if (isset($this->driver->map[$attribute]) &&
            is_array($this->driver->map[$attribute])) {
            $args = array();
            foreach ($this->driver->map[$attribute]['fields'] as $field) {
                $args[] = $this->getValue($field);
            }
            return trim(vsprintf($this->driver->map[$attribute]['format'], $args), " \t\n\r\0\x0B,");
        } elseif (!isset($this->attributes[$attribute])) {
            return null;
        } elseif (isset($GLOBALS['attributes'][$attribute]) &&
                  ($GLOBALS['attributes'][$attribute]['type'] == 'image')) {
            return empty($this->attributes[$attribute])
                ? null
                : array(
                      'load' => array(
                          'data' => $this->attributes[$attribute],
                          'file' => basename(tempnam(Horde::getTempDir(), 'horde_form_'))
                      )
                  );
        }

        return $this->attributes[$attribute];
    }

    /**
     * Sets the value of the specified attribute.
     *
     * @param string $attribute  The attribute to set.
     * @param string $value      The value of $attribute.
     */
    function setValue($attribute, $value)
    {
        try {
            $value = Horde::callHook('encode_attribute', array($attribute, $value, isset($this->attributes[$attribute]) ? $this->attributes[$attribute] : null, $this), 'turba');
        } catch (Horde_Exception_HookNotSet $e) {
        } catch (Turba_Exception $e) {}

        if (isset($this->driver->map[$attribute]) &&
            is_array($this->driver->map[$attribute]) &&
            !isset($this->driver->map[$attribute]['attribute'])) {
            return false;
        }

        $this->attributes[$attribute] = $value;
        return true;
    }

    /**
     * Determines whether or not the object has a value for the specified
     * attribute.
     *
     * @param string $attribute  The attribute to check.
     *
     * @return boolean  Whether or not there is a value for $attribute.
     */
    function hasValue($attribute)
    {
        if (isset($this->driver->map[$attribute]) &&
            is_array($this->driver->map[$attribute])) {
            foreach ($this->driver->map[$attribute]['fields'] as $field) {
                if ($this->hasValue($field)) {
                    return true;
                }
            }
            return false;
        } else {
            return !is_null($this->getValue($attribute));
        }
    }

    /**
     * Returns the timestamp of the last modification, whether this was the
     * creation or editing of the object and stores it as the attribute
     * __modified. The value is cached for the lifetime of the object.
     *
     * @return integer  The timestamp of the last modification or zero.
     */
    function lastModification()
    {
        $time = $this->getValue('__modified');
        if (!is_null($time)) {
            return $time;
        }

        if (!$this->getValue('__uid')) {
            $this->setValue('__modified', 0);
            return 0;
        }

        $time = 0;
        try {
            $log = $GLOBALS['injector']
                ->getInstance('Horde_History')
                ->getHistory($this->getGuid());
            foreach ($log as $entry) {
                if ($entry['action'] == 'add' || $entry['action'] == 'modify') {

                    $time = max($time, $entry['ts']);
                }
            }
        } catch (Exception $e) {}
        $this->setValue('__modified', $time);

        return $time;
    }

    /**
     * Merges another contact into this one by filling empty fields of this
     * contact with values from the other.
     *
     * @param Turba_Object $contact  Another contact.
     */
    function merge(Turba_Object $contact)
    {
        foreach (array_keys($contact->attributes) as $attribute) {
            if (!$this->hasValue($attribute) && $contact->hasValue($attribute)) {
                $this->setValue($attribute, $contact->getValue($attribute));
            }
        }
    }

    /**
     * Returns history information about this contact.
     *
     * @return array  A hash with the optional entries 'created' and 'modified'
     *                and human readable history information as the values.
     */
    function getHistory()
    {
        if (!$this->getValue('__uid')) {
            return array();
        }

        $history = array();
        try {
            $log = $GLOBALS['injector']
                ->getInstance('Horde_History')
                ->getHistory($this->getGuid());
            foreach ($log as $entry) {
                if ($entry['action'] == 'add' || $entry['action'] == 'modify') {
                    if ($GLOBALS['registry']->getAuth() != $entry['who']) {
                        $by = sprintf(_("by %s"), Turba::getUserName($entry['who']));
                    } else {
                        $by = _("by me");
                    }
                    $history[$entry['action'] == 'add' ? 'created' : 'modified']
                        = strftime($GLOBALS['prefs']->getValue('date_format'), $entry['ts'])
                        . ' '
                        . date($GLOBALS['prefs']->getValue('twentyFour') ? 'G:i' : 'g:i a', $entry['ts'])
                        . ' '
                        . @htmlspecialchars($by, ENT_COMPAT, $GLOBALS['registry']->getCharset());
                }
            }
        } catch (Exception $e) {
            return array();
        }

        return $history;
    }

    /**
     * Returns true if this object is a group of multiple contacts.
     *
     * @return boolean  True if this object is a group of multiple contacts.
     */
    function isGroup()
    {
        return false;
    }

    /**
     * Returns true if this object is editable by the current user.
     *
     * @return boolean  Whether or not the current user can edit this object
     */
    function isEditable()
    {
        return $this->driver->hasPermission(Horde_Perms::EDIT);
    }

    /**
     * Returns whether or not the current user has the requested permission.
     *
     * @param integer $perm  The permission to check.
     *
     * @return boolean True if user has the permission.
     */
    function hasPermission($perm)
    {
        return $this->driver->hasPermission($perm);
    }

    /**
     * Contact url.
     */
    function url($view = null, $full = false)
    {
        $url = Horde::applicationUrl('contact.php', $full)->add(array(
            'source' => $this->driver->getName(),
            'key' => $this->getValue('__key')
        ));

        if (!is_null($view)) {
            $url->add('view', $view);
        }

        return $url;
    }

    /**
     * Saves a file into the VFS backend associated with this object.
     *
     * @param array $info  A hash with the file information as returned from a
     *                     Horde_Form_Type_file.
     * @throws Turba_Exception
     */
    function addFile($info)
    {
        $this->_vfsInit();

        $dir = TURBA_VFS_PATH . '/' . $this->getValue('__uid');
        $file = $info['name'];
        while ($this->_vfs->exists($dir, $file)) {
            if (preg_match('/(.*)\[(\d+)\](\.[^.]*)?$/', $file, $match)) {
                $file = $match[1] . '[' . ++$match[2] . ']' . $match[3];
            } else {
                $dot = strrpos($file, '.');
                if ($dot === false) {
                    $file .= '[1]';
                } else {
                    $file = substr($file, 0, $dot) . '[1]' . substr($file, $dot);
                }
            }
        }

        try {
            $this->_vfs->write($dir, $file, $info['tmp_name'], true);
        } catch (VFS_Exception $e) {
            throw new Turba_Exception($e);
        }
    }

    /**
     * Deletes a file from the VFS backend associated with this object.
     *
     * @param string $file  The file name.
     * @throws Turba_Exception
     */
    function deleteFile($file)
    {
        $this->_vfsInit();
        try {
            $this->_vfs->deleteFile(TURBA_VFS_PATH . '/' . $this->getValue('__uid'), $file);
        } catch (VFS_Exception $e) {
            throw new Turba_Exception($e);
        }
    }

    /**
     * Deletes all files from the VFS backend associated with this object.
     *
     * @throws Turba_Exception
     */
    function deleteFiles()
    {
        $this->_vfsInit();
        if ($this->_vfs->exists(TURBA_VFS_PATH, $this->getValue('__uid'))) {
            try {
                $this->_vfs->deleteFolder(TURBA_VFS_PATH, $this->getValue('__uid'), true);
            } catch (VFS_Exception $e) {
                throw new Turba_Exception($e);
            }
        }
    }

    /**
     * Returns all files from the VFS backend associated with this object.
     *
     * @return array  A list of hashes with file informations.
     */
    function listFiles()
    {
        try {
            $this->_vfsInit();
            if ($this->_vfs->exists(TURBA_VFS_PATH, $this->getValue('__uid'))) {
                return $this->_vfs->listFolder(TURBA_VFS_PATH . '/' . $this->getValue('__uid'));
            }
        } catch (Turba_Exception $e) {}

        return array();
    }

    /**
     * Returns a link to display and download a file from the VFS backend
     * associated with this object.
     *
     * @param string $file  The file name.
     *
     * @return string  The HTML code of the generated link.
     */
    function vfsDisplayUrl($file)
    {
        global $registry, $mime_drivers_map, $mime_drivers;

        $mime_part = new Horde_Mime_Part();
        $mime_part->setType(Horde_Mime_Magic::extToMime($file['type']));
        $viewer = $GLOBALS['injector']->getInstance('Horde_Mime_Viewer')->getViewer($mime_part);

        // We can always download files.
        $url_params = array('actionID' => 'download_file',
                            'file' => $file['name'],
                            'type' => $file['type'],
                            'source' => $this->driver->getName(),
                            'key' => $this->getValue('__key'));
        $dl = Horde::link(Horde::downloadUrl($file['name'], $url_params), $file['name']) . Horde::img('download.png', _("Download")) . '</a>';

        // Let's see if we can view this one, too.
        if ($viewer && !($viewer instanceof Horde_Mime_Viewer_Default)) {
            $url = Horde::applicationUrl('view.php')->add('actionID', 'view_file');
            $link = Horde::link($url, $file['name'], null, '_blank') . $file['name'] . '</a>';
        } else {
            $link = $file['name'];
        }

        return $link . ' ' . $dl;
    }

    /**
     * Returns a link to display, download, and delete a file from the VFS
     * backend associated with this object.
     *
     * @param string $file  The file name.
     *
     * @return string  The HTML code of the generated link.
     */
    function vfsEditUrl($file)
    {
        $delform = '<form action="' .
            Horde::applicationUrl('deletefile.php') .
            '" style="display:inline" method="post">' .
            Horde_Util::formInput() .
            '<input type="hidden" name="file" value="' . htmlspecialchars($file['name']) . '" />' .
            '<input type="hidden" name="source" value="' . htmlspecialchars($this->driver->getName()) . '" />' .
            '<input type="hidden" name="key" value="' . htmlspecialchars($this->getValue('__key')) . '" />' .
            '<input type="image" class="img" src="' . Horde_Themes::img('delete.png') . '" />' .
            '</form>';

        return $this->vfsDisplayUrl($file) . ' ' . $delform;
    }

    /**
     * Saves the current state of the object to the storage backend.
     *
     * @throws Turba_Exception
     */
    public function store()
    {
        return $this->setValue('__key', $this->driver->save($this));
    }

    /**
     * Loads the VFS configuration and initializes the VFS backend.
     *
     * @throws Turba_Exception
     */
    function _vfsInit()
    {
        if (!isset($this->_vfs)) {
            try {
                $this->_vfs = $GLOBALS['injector']->getInstance('Horde_Vfs')->getVfs('documents');
            } catch (Horde_Exception $e) {
                throw new Turba_Exception($e);
            }
        }
    }

}
