/* http://www.quirksmode.org/dom/maxlength.html */

/*
<textarea id="text" name="text" maxlength="1500"></textarea>
*/


function setMaxLength()
{
	var x = document.getElementsByTagName('textarea');
	var counter = document.createElement('div');
	counter.className = 'counter';
	for (var i=0;i<x.length;i++)
	{
		if (x[i].getAttribute('maxlength'))
		{
			var counterClone = counter.cloneNode(true);
			counterClone.relatedElement = x[i];
			counterClone.innerHTML = '<span>0</span>/'+x[i].getAttribute('maxlength');
			x[i].parentNode.insertBefore(counterClone,x[i].nextSibling);
			x[i].relatedElement = counterClone.getElementsByTagName('span')[0];

			x[i].onkeyup = x[i].onchange = checkMaxLength;
			x[i].onkeyup();
		}
	}
}

function checkMaxLength()
{
	var maxLength = this.getAttribute('maxlength');
	var currentLength = this.value.length;
	if (currentLength > maxLength)
		this.relatedElement.className = 'toomuch';
	else
		this.relatedElement.className = '';
	this.relatedElement.firstChild.nodeValue = currentLength;
	// not innerHTML
}
