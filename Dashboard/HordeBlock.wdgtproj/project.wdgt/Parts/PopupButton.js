/*
Copyright © 2006-2007 Apple Inc.  All Rights Reserved.

IMPORTANT:  This Apple software ("Apple Software") is supplied to you in consideration of your agreement to the following terms. Your use, installation and/or redistribution of this Apple Software constitutes acceptance of these terms. If you do not agree with these terms, please do not use, install, or redistribute this Apple Software.

Provided you comply with all of the following terms, Apple grants you a personal, non-exclusive license, under Apple’s copyrights in the Apple Software, to use, reproduce, and redistribute the Apple Software for the sole purpose of creating Dashboard widgets for Mac OS X. If you redistribute the Apple Software, you must retain this entire notice in all such redistributions.

You may not use the name, trademarks, service marks or logos of Apple to endorse or promote products that include the Apple Software without the prior written permission of Apple. Except as expressly stated in this notice, no other rights or licenses, express or implied, are granted by Apple herein, including but not limited to any patent rights that may be infringed by your products that incorporate the Apple Software or by other works in which the Apple Software may be incorporated.

The Apple Software is provided on an "AS IS" basis.  APPLE MAKES NO WARRANTIES, EXPRESS OR IMPLIED, INCLUDING WITHOUT LIMITATION THE IMPLIED WARRANTIES OF NON-INFRINGEMENT, MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE, REGARDING THE APPPLE SOFTWARE OR ITS USE AND OPERATION ALONE OR IN COMBINATION WITH YOUR PRODUCTS.

IN NO EVENT SHALL APPLE BE LIABLE FOR ANY SPECIAL, INDIRECT, INCIDENTAL OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) ARISING IN ANY WAY OUT OF THE USE, REPRODUCTION, AND/OR DISTRIBUTION OF THE APPLE SOFTWARE, HOWEVER CAUSED AND WHETHER UNDER THEORY OF CONTRACT, TORT (INCLUDING NEGLIGENCE), STRICT LIABILITY OR OTHERWISE, EVEN IF APPLE HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

function CreatePopupButton(popupID, spec)
{
	var popupElement = document.getElementById(popupID);
	if (!popupElement.loaded) {
		popupElement.loaded = true;
		popupElement.object = new PopupButton(popupElement, popupID, spec);
		return popupElement.object;
	}
}


function PopupButton(popupElement, popupID, spec)
{
	var imagePrefix = "Images/" + popupID + "_";
	var leftImageWidth = spec.leftImageWidth || 0;
	var rightImageWidth = spec.rightImageWidth || 0;
	var width = getElementWidth(popupElement) || 20;
	var height = getElementHeight(popupElement) || 20;
	var _self = this;

	// setup the button
	this.button = new AppleButton(popupElement, '', height, imagePrefix + "left.png", imagePrefix + "left_clicked.png", leftImageWidth, imagePrefix + "middle.png", imagePrefix + "middle_clicked.png", imagePrefix + "right.png", imagePrefix + "right_clicked.png", rightImageWidth, null);
	this.button._container.childNodes.item(2).style.width = rightImageWidth + "px";
	this.button.textElement.style.width = (width - (leftImageWidth + rightImageWidth)) + "px";
	this.button.textElement.style.textIndent = Math.max(10-leftImageWidth, 0) + "px";
	var eventsDiv = document.createElement("div");
	eventsDiv.setAttribute("style", "position: absolute; left: 0; top: 0; width: 100%; height: 100%");
	popupElement.appendChild(eventsDiv);
	var clickHandler = function(event) {
		_self.select.dispatchEvent(event);
		event.stopPropagation();
		event.preventDefault();
	}
	eventsDiv.addEventListener("mousedown", clickHandler, true);


	// setup the select
	this.select = document.createElement("select");
	var onchange = spec.onchange || null;
	try { onchange = eval(onchange); } catch (e) { onchange = null; }
	this.onchange = onchange;
	this._setOptionsStr(spec.options);
	this.select.setAttribute("style", "position: absolute; left: 0; top: 0; width: 100%; height: 100%; opacity: 0;");
	popupElement.appendChild(this.select);
	this.select.style.top = Math.max((height - getElementHeight(this.select)) / 2, 0) + "px";

	// onchange event handler
	this.select.onchange = function (event) {
		var selectedOption = this.options[this.selectedIndex];
		if (selectedOption) {
			_self.button.textElement.innerText = selectedOption.text;
			// if it is a real event, forward it to the custom handler
			if (_self.onchange && event) {
				_self.onchange(event);
			}
		}
	};

	this.setEnabled(!spec.disabled);
}

PopupButton.prototype.getValue = function()
{
	return this.select.value;
}

PopupButton.prototype.getSelectedIndex = function()
{
	return this.select.selectedIndex;
}

PopupButton.prototype.setSelectedIndex = function(index)
{
	this.select.selectedIndex = index;
	this.select.onchange(null);
}

PopupButton.prototype.setEnabled = function(enabled)
{
	this.button.setEnabled(enabled);
	this.select.disabled=!enabled;
}

PopupButton.prototype.setOptions = function(options, shouldLocalize)
{
	if (!options || !(options instanceof Array)) options = [];
	var text = '';

	this.select.options.length = 0;
	for (var i = 0; i < options.length; i++) {
		var defaultSelected = false;
		var optionLabel = '';
		var optionValue = null;
		if ((options[i]) instanceof Array) {
			if (options[i].length > 0) {
				optionLabel = options[i][0];
				if (options[i].length > 1) {
					optionValue = options[i][1];
					if (options[i].length > 2 && options[i][2]) {
						defaultSelected = true;
					}
				}
			}
		}
		else {
			optionLabel = options[i];
		}
		
		if (shouldLocalize) {
			optionLabel = getLocalizedString(optionLabel);
		}
		if (i==0 || defaultSelected) {
			text = optionLabel;
		}
		if (!optionValue || optionValue.length == 0) {
			optionValue = optionLabel;
		}
        
		this.select.options[this.select.length] = new Option(optionLabel, optionValue, defaultSelected);
	}
	this.button.textElement.innerText = text;
}

PopupButton.prototype._setOptionsStr = function(optionsStr)
{
	var options = null;
	if (typeof optionsStr == "string") {
		options = eval(optionsStr);
	}
	this.setOptions(options, true);
}
