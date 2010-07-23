/**
 * inplaceeditor.js - A javascript library which implements ajax inplace editing
 * Requires prototype.js v1.6.0.2+ and scriptaculous v1.8.0+ (effects.js) if
 * using the default callback functions.
 *
 * Adapted from script.aculo.us controls.js v1.8.3
 * Copyright (c) 2005-2009 Thomas Fuchs (http://script.aculo.us, http://mir.aculo.us)
 *          (c) 2005-2009 Ivan Krstic (http://blogs.law.harvard.edu/ivan)
 *          (c) 2005-2009 Jon Tirsen (http://www.tirsen.com)
 *   Contributors:
 *   Richard Livsey
 *   Rahul Bhargava
 *   Rob Wills
 *
 * The original script was freely distributable under the terms of an
 * MIT-style license.
 *
 * Usage:
 * ------
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

InPlaceEditor = Class.create(
{
    /**
     * Constructor
     */
    initialize: function(element, url, options)
    {
        // Default Options/Callbacks
        var defaults =
        {
            ajaxOptions: { },
            autoRows: 3,                                // Use when multi-line w/ rows == 1
            cancelControl: 'link',                      // 'link'|'button'|false
            cancelText: 'cancel',
            cancelClassName: 'button',
            clickToEditText: 'Click to edit',
            emptyClassName: 'inplaceeditor-empty',
            externalControl: null,                      // id|elt
            externalControlOnly: false,
            fieldPostCreation: 'activate',              // 'activate'|'focus'|false
            formClassName: 'inplaceeditor-form',
            formId: null,                               // id|elt
            highlightColor: '#ffff99',
            highlightEndColor: '#ffffff',
            hoverClassName: '',
            htmlResponse: true,
            loadingClassName: 'inplaceeditor-loading',
            loadingText: 'Loading...',
            okControl: 'button',                        // 'link'|'button'|false
            okText: 'ok',
            okClassName: 'button',
            paramName: 'value',
            rows: 1,                                    // If 1 and multi-line, uses autoRows
            savingClassName: 'inplaceeditor-saving',
            savingText: 'Saving...',
            size: 0,
            stripLoadedTextTags: false,
            submitOnBlur: false,
            width: null,

            /** Default Callbacks **/
            callback: function(form)
            {
              return Form.serialize(form);
            },

            onComplete: function(ipe)
            {
                new Effect.Highlight(element, { startcolor: this.options.highlightColor, keepBackgroundImage: true });
            },

            onEnterEditMode: Prototype.emptyFunction,

            onEnterHover: function(ipe)
            {
                ipe.element.style.backgroundColor = ipe.options.highlightColor;
                if (ipe._effect) {
                    ipe._effect.cancel();
                }
            },

            onFailure: function(transport, ipe) {
                alert('Error communication with the server: ' + transport.responseText.stripTags());
            },

            /**
             * Takes the IPE and its generated form, after editor, before controls.
             */
            onFormCustomization: Prototype.emptyFunction,

            onLeaveEditMode: Prototype.emptyFunction,

            onLeaveHover: function(ipe)
            {
                ipe._effect = new Effect.Highlight(ipe.element, {
                    startcolor: ipe.options.highlightColor, endcolor: ipe.options.highlightEndColor,
                    restorecolor: ipe._originalBackground, keepBackgroundImage: true
                });
            }
        };

        this.url = url;
        this.element = element = $(element);
        this._controls = { };
        Object.extend(defaults, options || { });
        this.options = defaults;
        if (!this.options.formId && this.element.id) {
            this.options.formId = this.element.id + '-inplaceeditor';
            if ($(this.options.formId)) {
                this.options.formId = '';
            }
        }
        if (this.options.externalControl) {
            this.options.externalControl = $(this.options.externalControl);
        }
        if (!this.options.externalControl) {
            this.options.externalControlOnly = false;
        }
        this._originalBackground = this.element.getStyle('background-color') || 'transparent';
        this.element.title = this.options.clickToEditText;
        this._boundCancelHandler = this.handleFormCancellation.bind(this);
        this._boundComplete = (this.options.onComplete || Prototype.emptyFunction).bind(this);
        this._boundFailureHandler = this.handleAJAXFailure.bind(this);
        this._boundSubmitHandler = this.handleFormSubmission.bind(this);
        this._boundWrapperHandler = this.wrapUp.bind(this);
        this.registerListeners();
        this.checkEmpty();
    },

    checkEmpty: function() {
        if (this.element.innerHTML.length == 0) {
            emptyNode = new Element('span', {className: this.options.emptyClassName}).update(this.options.emptyText);
            this.element.appendChild(emptyNode);
        }
    },

    keyHandler: function(e)
    {
        if (!this._editing || e.ctrlKey || e.altKey || e.shiftKey) return;
        if (e.keyCode == Event.KEY_ESC) {
            this.handleFormCancellation(e);
        } else if (e.keyCode == Event.KEY_RETURN) {
            this.handleFormSubmission(e);
        }
    },

    createControl: function(mode, handler, extraClasses)
    {
        var control = this.options[mode + 'Control'];
        var text = this.options[mode + 'Text'];
        if (control == 'button') {
            var btn = new Element('input', { type: 'submit', value: text, className: this.options[mode + 'ClassName'] });
            if (mode == 'cancel') {
                btn.observe('click', this._boundCancelHandler);
            }
            this._form.appendChild(btn);
            this._controls[mode] = btn;
        } else if (control == 'link') {
            var link = new Element('a', { href: '#', className:  this.options[mode + 'ClassName'] });
            link.observe('click', 'cancel' == mode ? this._boundCancelHandler : this._boundSubmitHandler);
            link.appendChild(document.createTextNode(text));
            if (extraClasses) {
                link.addClassName(extraClasses);
            }
            this._form.appendChild(link);
            this._controls[mode] = link;
        }
    },

    createEditField: function()
    {
        var text = (this.options.loadTextURL ? this.options.loadingText : this.getText());
        var fld;
        if (this.options.rows <= 1 && !/\r|\n/.test(this.getText())) {
            fld = new Element('input', { type: 'text' });
            var size = this.options.size || this.options.cols || 0;
            if (size > 0) {
                fld.size = size;
            }
        } else {
            fld = new Element('textarea', { rows: (this.options.rows <= 1 ? this.options.autoRows : this.options.rows),
                                            cols: this.options.cols || 40 });
        }
        fld.name = this.options.paramName;
        fld.value = text; // No HTML breaks conversion anymore
        fld.className = 'editor_field';
        if (this.options.width) {
            fld.setStyle({ width: this.options.width + 'px' });
        }
        if (this.options.submitOnBlur) {
            fld.observe('blur', this._boundSubmitHandler);
        }
        this._controls.editor = fld;
        if (this.options.loadTextURL) {
            this.loadExternalText();
        }
        this._form.appendChild(this._controls.editor);
    },

    createForm: function()
    {
        var ipe = this;
        function addText(mode, condition)
        {
            var text = ipe.options['text' + mode + 'Controls'];
            if (!text || condition === false) return;
            ipe._form.appendChild(text);
        };

        this._form = new Element('form', { id: this.options.formId, className: this.options.formClassName });
        this._form.observe('submit', this._boundSubmitHandler);
        this.createEditField();
        if (this._controls.editor.tagName.toLowerCase() == 'textarea') {
            this._form.appendChild(new Element('br'));
        }
        if (this.options.onFormCustomization) {
            this.options.onFormCustomization(this, this._form);
        }
        addText('Before', this.options.okControl || this.options.cancelControl);
        this.createControl('ok', this._boundSubmitHandler);
        addText('Between', this.options.okControl && this.options.cancelControl);
        this.createControl('cancel', this._boundCancelHandler, 'editor_cancel');
        addText('After', this.options.okControl || this.options.cancelControl);
    },

    destroy: function()
    {
        if (this._oldInnerHTML) {
            this.element.innerHTML = this._oldInnerHTML;
        }
        this.leaveEditMode();
        this.unregisterListeners();
    },

    clickHandler: function(e)
    {
        if (this._saving || this._editing) {
            return;
        }
        this._editing = true;
        this.triggerCallback('onEnterEditMode');
        if (this.options.externalControl) {
            this.options.externalControl.hide();
        }
        this.element.hide();
        this.createForm();
        this.element.parentNode.insertBefore(this._form, this.element);
        if (!this.options.loadTextURL) {
            this.postProcessEditField();
        }
        if (e) {
            Event.stop(e);
        }
    },

    mouseoverHandler: function(e)
    {
        if (this.options.hoverClassName) {
            this.element.addClassName(this.options.hoverClassName);
        }
        if (this._saving) {
            return;
        }
        this.triggerCallback('onEnterHover');
    },

    getText: function()
    {   
        $(this.element).select('.' + this.options.emptyClassName).each(function(child) {
            this.element.removeChild(child);
        }.bind(this));

        return this.element.innerHTML.unescapeHTML();
    },

    handleAJAXFailure: function(transport)
    {
        this.triggerCallback('onFailure', transport);
        if (this._oldInnerHTML) {
            this.element.innerHTML = this._oldInnerHTML;
            this._oldInnerHTML = null;
        }
    },

    handleFormCancellation: function(e)
    {
        this.wrapUp();
        if (e) {
            Event.stop(e);
        }
    },

    handleFormSubmission: function(e)
    {
        var form = this._form;
        var value = $F(this._controls.editor);
        this.prepareSubmission();
        var params = this.options.callback(form, value) || '';
        if (Object.isString(params)) {
            params = params.toQueryParams();
        }
        params.editorId = this.element.id;
        if (this.options.htmlResponse) {
            var options = Object.extend({ evalScripts: true }, this.options.ajaxOptions);
            Object.extend(options, {
                parameters: params,
                onComplete: this._boundWrapperHandler,
                onFailure: this._boundFailureHandler
            });
            new Ajax.Updater({ success: this.element }, this.url, options);
        } else {
            var options = Object.extend({ method: 'get' }, this.options.ajaxOptions);
            Object.extend(options, {
                parameters: params,
                onComplete: this._boundWrapperHandler,
                onFailure: this._boundFailureHandler
            });
            new Ajax.Request(this.url, options);
        }
        if (e) {
            Event.stop(e);
        }
    },

    leaveEditMode: function()
    {
        this.element.removeClassName(this.options.savingClassName);
        this.removeForm();
        this.mouseoutHandler();
        this.element.style.backgroundColor = this._originalBackground;
        this.element.show();
        if (this.options.externalControl) {
            this.options.externalControl.show();
        }
        this._saving = false;
        this._editing = false;
        this._oldInnerHTML = null;
        this.triggerCallback('onLeaveEditMode');
    },

    mouseoutHandler: function(e)
    {
        if (this.options.hoverClassName) {
            this.element.removeClassName(this.options.hoverClassName);
        }
        if (this._saving) {
            return;
        }
        this.triggerCallback('onLeaveHover');
    },

    loadExternalText: function()
    {
        this._form.addClassName(this.options.loadingClassName);
        this._controls.editor.disabled = true;
        var options = Object.extend({ method: 'get' }, this.options.ajaxOptions);
        Object.extend(options, {
            parameters: 'editorId=' + encodeURIComponent(this.element.id),
            onComplete: Prototype.emptyFunction,
            onSuccess: function(transport) {
                this._form.removeClassName(this.options.loadingClassName);
                var text = transport.responseText;
                if (this.options.stripLoadedTextTags) {
                    text = text.stripTags();
                }
                this._controls.editor.value = text;
                this._controls.editor.disabled = false;
                this.postProcessEditField();
            }.bind(this),
            onFailure: this._boundFailureHandler
        });
        new Ajax.Request(this.options.loadTextURL, options);
    },

    postProcessEditField: function()
    {
        var fpc = this.options.fieldPostCreation;
        if (fpc) {
            $(this._controls.editor)['focus' == fpc ? 'focus' : 'activate']();
        }
    },

    prepareSubmission: function()
    {
        this._saving = true;
        this.removeForm();
        this.mouseoutHandler();
        this.showSaving();
    },

    registerListeners: function()
    {
        var listeners = {
            click: 'clickHandler',
            keydown: 'keyHandler',
            mouseover: 'mouseoverHandler',
            mouseout: 'mouseoutHandler'
        };

        this._listeners = { };
        var listener;
        $H(listeners).each(function(pair) {
            listener = this[pair.value].bind(this);
            this._listeners[pair.key] = listener;
            if (!this.options.externalControlOnly) {
                this.element.observe(pair.key, listener);
            }
            if (this.options.externalControl) {
                this.options.externalControl.observe(pair.key, listener);
            }
        }.bind(this));
    },

    removeForm: function()
    {
        if (!this._form) return;
        this._form.remove();
        this._form = null;
        this._controls = { };
    },

    showSaving: function()
    {
        this._oldInnerHTML = this.element.innerHTML;
        this.element.innerHTML = this.options.savingText;
        this.element.addClassName(this.options.savingClassName);
        this.element.style.backgroundColor = this._originalBackground;
        this.element.show();
    },

    triggerCallback: function(cbName, arg)
    {
        if ('function' == typeof this.options[cbName]) {
            this.options[cbName](this, arg);
        }
    },

    unregisterListeners: function()
    {
        $H(this._listeners).each(function(pair) {
            if (!this.options.externalControlOnly) {
                this.element.stopObserving(pair.key, pair.value);
            }
            if (this.options.externalControl) {
                this.options.externalControl.stopObserving(pair.key, pair.value);
            }
        }.bind(this));
    },

    wrapUp: function(transport) {
        this.leaveEditMode();
        this.triggerCallback('onComplete', null);
    }
});