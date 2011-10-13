/**
 * This file is distributed as a component of Sandals.wdgt
 * Sandals is a part of the Hermes application of the Horde Project
 * Horde LLC (http://www.horde.org/)
 *
 *    Copyright 2000, 2001, 2002  Virtual Cowboys info@virtualcowboys.nl
 *		
 *		Author: Ruben Daniels <ruben@virtualcowboys.nl>
 *		Version: 0.91
 *		Date: 29-08-2001
 *		Site: www.vcdn.org/Public/XMLRPC/
 *
 *    This program is free software; you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation; either version 2 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program; if not, write to the Free Software
 *    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @author Ruben Daniels <ruben@virtualcowboys.nl>
 */


Object.prototype.toXMLRPC = function(){
	var wo = this.valueOf();
	
	if(wo.toXMLRPC == this.toXMLRPC){
		retstr = "<struct>";
		
		for(prop in this){
			if(typeof wo[prop] != "function"){
				retstr += "<member><name>" + prop + "</name><value>" + XMLRPC.getXML(wo[prop]) + "</value></member>";
			}
		}
		retstr += "</struct>";
		
		return retstr;
	}
	else{
		return wo.toXMLRPC();
	}
}

String.prototype.toXMLRPC = function(){
	//<![CDATA[***your text here***]]>
	return "<string><![CDATA[" + this.replace(/\]\]/g, "] ]") + "]]></string>";//.replace(/</g, "&lt;").replace(/&/g, "&amp;")
}

Number.prototype.toXMLRPC = function(){
	if(this == parseInt(this)){
		return "<int>" + this + "</int>";
	}
	else if(this == parseFloat(this)){
		return "<double>" + this + "</double>";
	}
	else{
		return false.toXMLRPC();
	}
}

Boolean.prototype.toXMLRPC = function(){
	if(this) return "<boolean>1</boolean>";
	else return "<boolean>0</boolean>";
}

Date.prototype.toXMLRPC = function(){
	//Could build in possibilities to express dates 
	//in weeks or other iso8601 possibillities
	//hmmmm ????
	//19980717T14:08:55
	return "<dateTime.iso8601>" + doYear(this.getUTCYear()) + doZero(this.getMonth()) + doZero(this.getUTCDate()) + "T" + doZero(this.getHours()) + ":" + doZero(this.getMinutes()) + ":" + doZero(this.getSeconds()) + "</dateTime.iso8601>";
	
	function doZero(nr) {
		nr = String("0" + nr);
		return nr.substr(nr.length-2, 2);
	}
	
	function doYear(year) {
		if(year > 9999 || year < 0) 
			XMLRPC.handleError(new Error("Unsupported year: " + year));
			
		year = String("0000" + year)
		return year.substr(year.length-4, 4);
	}
}

Array.prototype.toXMLRPC = function(){
	var retstr = "<array><data>";
	for(var i=0;i<this.length;i++){
		retstr += "<value>" + XMLRPC.getXML(this[i]) + "</value>";
	}
	return retstr + "</data></array>";
}

function VirtualService(servername, oRPC){
	this.version = '0.91';
	this.URL = servername;
	this.multicall = false;
	this.autoroute = true;
	this.onerror = null;
	
	this.rpc = oRPC;
	this.receive = {};
	
	this.purge = function(receive){
		return this.rpc.purge(this, receive);
	}
	
	this.revert = function(){
		this.rpc.revert(this);
	}
	
	this.add = function(name, alias, receive){
		this.rpc.validateMethodName();if(this.rpc.stop){this.rpc.stop = false;return false}
		if(receive) this.receive[name] = receive;
		this[(alias || name)] = new Function('var args = new Array(), i;for(i=0;i<arguments.length;i++){args.push(arguments[i]);};return this.call("' + name + '", args);');
		return true;
	}
	
	//internal function for sending data
	this.call = function(name, args){
		var info = this.rpc.send(this.URL, name, args, this.receive[name], this.multicall, this.autoroute);
		
		if(info){
			if(!this.multicall) this.autoroute = info[0];
			return info[1];
		}
		else{
			if(this.onerror) this.onerror(XMLRPC.lastError);
			return false;
		}
	}
}


XMLRPC = {
	routeServer : "http://www.vcdn.org/cgi-bin/rpcproxy.cgi",
	autoroute : true,
	multicall : false,

	services : {},
	stack : {},
	queue : new Array(),
	timers : new Array(),
	timeout : 30000,
	
	ontimeout : null,
	
	getService : function(serviceName){
		//serviceNames cannot contain / or .
		if(/[\/\.]/.test(serviceName)){
			return new VirtualService(serviceName, this);
		}
		else if(this.services[serviceName]){
			return this.services[serviceName];
		}
		else{
			try{
				var ct = eval(serviceName);
				this.services[serviceName] = new ct(this);
			}
			catch(e){
				return false;
			}
		}
	},
	
	purge : function(modConst, receive){
		if(this.stack[modConst.URL].length){
			var info = this.send(modConst.URL, "system.multicall", [this.stack[modConst.URL]], receive, false, modConst.autoroute);
			modConst.autoroute = info[0];
			this.revert(modConst);
			
			if(info){
				modConst.autoroute = info[0];
				return info[1];
			}
			else{
				if(modConst.onerror) modConst.onerror(this.lastError);
				return false;
			}
		}
	},
	
	revert : function(modConst){
		this.stack[modConst.URL] = new Array();
	},
	
	call : function(){
		//[optional info || receive, servername,] functionname, args......
		var args = new Array(), i, a = arguments;
		var servername, methodname, receive, service, info, autoroute, multicall;
		
		if(typeof a[0] == "object"){
			receive = a[0][0];
			servername = a[0][1].URL;
			methodname = a[1];
			multicall = (a[0][1].supportsMulticall && a[0][1].multicall);
			autoroute = a[0][1].autoroute;
			service = a[0][1];
		}
		else if(typeof a[0] == "function"){
			i = 3;
			receive = a[0];
			servername = a[1];
			methodname = a[2];
		}
		else{
			i = 2;
			servername = a[0];
			methodname = a[1];
		}
			
		for(i=i;i<a.length;i++){
			args.push(a[i]);
		}
		
		info = this.send(servername, methodname, args, receive, multicall, autoroute);
		if(info){
			(service || this).autoroute = info[0];
			return info[1];
		}
		else{
			if(service && service.onerror) service.onerror(this.lastError);
			return false;
		}
		
	},
	
	/***
	* Perform typematching on 'vDunno' and return a boolean value corresponding
	* to the result of the evaluation-match of the mask-value stated in the 2nd argument.
	* The 2nd argument is optional (none will be treated as a 0-mask) or a sum of
	* several masks as follows:
	* type/s    ->  mask/s
	* --------------------
	* undefined ->  0/1 [default]
	* number    ->  2
	* boolean   ->  4
	* string    ->  8
	* function  -> 16
	* object    -> 32
	* --------------------
	* Examples:
	* Want [String] only: (eqv. (typeof(vDunno) == 'string') )
	*  Soya.Common.typematch(unknown, 8)
	* Anything else than 'undefined' acceptable:
	*  Soya.Common.typematch(unknown)
	* Want [Number], [Boolean] or [Function]:
	*  Soya.Common.typematch(unknown, 2 + 4 + 16)
	* Want [Number] only:
	*  Soya.Common.typematch(unknown, 2)
	**/
	typematch : function (vDunno, nCase){
		var nMask;
		switch(typeof(vDunno)){
			case 'number'  : nMask = 2;  break;
			case 'boolean' : nMask = 4;  break;
			case 'string'  : nMask = 8;  break;
			case 'function': nMask = 16; break;
			case 'object'  : nMask = 32; break;
			default	     : nMask = 1;  break;
		}
		return Boolean(nMask & (nCase || 62));
	},
	
	getNode : function(data, tree){
		var nc = 0;//nodeCount
		//node = 1
		if(data != null){
			for(i=0;i<data.childNodes.length;i++){
				if(data.childNodes[i].nodeType == 1){
					if(nc == tree[0]){
						data = data.childNodes[i];
						if(tree.length > 1){
							tree.shift();
							data = this.getNode(data, tree);
						}
						return data;
					}
					nc++
				}
			}
		}
		
		return false;
	},
	
	toObject : function(data){
		var ret, i;
		switch(data.tagName){
			case "string":
				return (data.firstChild) ? new String(data.firstChild.nodeValue) : "";
				break;
			case "int":
			case "i4":
			case "double":
				return (data.firstChild) ? new Number(data.firstChild.nodeValue) : 0;
				break;
			case "dateTime.iso8601":
				/*
				Have to read the spec to be able to completely 
				parse all the possibilities in iso8601
				07-17-1998 14:08:55
				19980717T14:08:55
				*/
				
				var sn = (isIE) ? "-" : "/";
				
				if(/^(\d{4})(\d{2})(\d{2})T(\d{2}):(\d{2}):(\d{2})/.test(data.firstChild.nodeValue)){;//data.text)){
	      		return new Date(RegExp.$2 + sn + RegExp.$3 + sn + 
	      							RegExp.$1 + " " + RegExp.$4 + ":" + 
	      							RegExp.$5 + ":" + RegExp.$6);
	      	}
	    		else{
	    			return new Date();
	    		}

				break;
			case "array":
				data = this.getNode(data, [0]);
				
				if(data && data.tagName == "data"){
					ret = new Array();
					
					var i = 0;
					while(child = this.getNode(data, [i++])){
      				ret.push(this.toObject(child));
					}
					
					return ret;
				}
				else{
					this.handleError(new Error("Malformed XMLRPC Message1"));
					return false;
				}
				break;
			case "struct":
				ret = {};
					
				var i = 0;
				while(child = this.getNode(data, [i++])){
					if(child.tagName == "member"){
						ret[this.getNode(child, [0]).firstChild.nodeValue] = this.toObject(this.getNode(child, [1]));
					}
					else{
						this.handleError(new Error("Malformed XMLRPC Message2"));
						return false;
					}
				}
				
				return ret;
				break;
			case "boolean":
				return Boolean(isNaN(parseInt(data.firstChild.nodeValue)) ? (data.firstChild.nodeValue == "true") : parseInt(data.firstChild.nodeValue))

				break;
			case "base64":
				return this.decodeBase64(data.firstChild.nodeValue);
				break;
			case "value":
				child = this.getNode(data, [0]);
				return (!child) ? ((data.firstChild) ? new String(data.firstChild.nodeValue) : "") : this.toObject(child);

				break;
			default:
				this.handleError(new Error("Malformed XMLRPC Message: " + data.tagName));
				return false;
				break;
		}
	},
	
	/*** Decode Base64 ******
	* Original Idea & Code by thomas@saltstorm.net
	* from Soya.Encode.Base64 [http://soya.saltstorm.net]
	**/
	decodeBase64 : function(sEncoded){
		// Input must be dividable with 4.
		if(!sEncoded || (sEncoded.length % 4) > 0)
		  return sEncoded;
	
		/* Use NN's built-in base64 decoder if available.
		   This procedure is horribly slow running under NN4,
		   so the NN built-in equivalent comes in very handy. :) */
	
		else if(typeof(atob) != 'undefined')
		  return atob(sEncoded);
	
	  	var nBits, i, sDecoded = '';
	  	var base64 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';
		sEncoded = sEncoded.replace(/\W|=/g, '');
	
		for(i=0; i < sEncoded.length; i += 4){
			nBits =
				(base64.indexOf(sEncoded.charAt(i))   & 0xff) << 18 |
				(base64.indexOf(sEncoded.charAt(i+1)) & 0xff) << 12 |
				(base64.indexOf(sEncoded.charAt(i+2)) & 0xff) <<  6 |
				base64.indexOf(sEncoded.charAt(i+3)) & 0xff;
			sDecoded += String.fromCharCode(
				(nBits & 0xff0000) >> 16, (nBits & 0xff00) >> 8, nBits & 0xff);
		}
	
		// not sure if the following statement behaves as supposed under
		// all circumstances, but tests up til now says it does.
	
		return sDecoded.substring(0, sDecoded.length -
		 ((sEncoded.charCodeAt(i - 2) == 61) ? 2 :
		  (sEncoded.charCodeAt(i - 1) == 61 ? 1 : 0)));
	},
	
	getObject : function(type, message){
		if(type == "HTTP"){
			if(isIE)
				obj = new ActiveXObject("microsoft.XMLHTTP"); 
			else if(isNS)
				obj = new XMLHttpRequest();
		}
		else if(type == "XMLDOM"){
			if(isIE){
				obj = new ActiveXObject("microsoft.XMLDOM"); 
				obj.loadXML(message)
			}else if(isNS){
				obj = new DOMParser();
				obj = obj.parseFromString(message, "text/xml");
			}
			
		}
		else{
			this.handleError(new Error("Unknown Object"));
		}

		return obj;
	},
	
	validateMethodName : function(name){
		/*do Checking:
		
		The string may only contain identifier characters, 
		upper and lower-case A-Z, the numeric characters, 0-9, 
		underscore, dot, colon and slash. 
		
		*/
		if(/^[A-Za-z0-9\._\/:]+$/.test(name))
			return true
		else
			this.handleError(new Error("Incorrect method name"));
	},
	
	getXML : function(obj){
		if(typeof obj == "function"){
			this.handleError(new Error("Cannot Parse functions"));
		}else if(obj == null || obj == undefined || (typeof obj == "number" && !isFinite(obj)))
			return false.toXMLRPC();
		else
			return obj.toXMLRPC();
	},
	
	handleError : function(e){
		if(!this.onerror || !this.onerror(e)){
			//alert("An error has occured: " + e.message);
			throw e;
		}
		this.stop = true;
		this.lastError = e;
	},
	
	cancel : function(id){
		//You can only cancel a request when it was executed async (I think)
		if(!this.queue[id]) return false;
		
		this.queue[id][0].abort();
		return true;
	},
	
	send : function(serverAddress, functionName, args, receive, multicall, autoroute){
		var id, http;
		//default is sync
		this.validateMethodName();
		if(this.stop){this.stop = false; return false;}
		
		//setting up multicall
		multicall = (multicall != null) ? multicall : this.multicall;
		
		if(multicall){
			if(!this.stack[serverAddress]) this.stack[serverAddress] = new Array();
			this.stack[serverAddress].push({methodName : functionName, params : args});
			return true;
		}
		
		//creating http object
		var http = this.getObject("HTTP");
		
		//setting some things for async/sync transfers
		if(!receive || isNS){;
			async = false;
		}
		else{
			async = true;
			/* The timer functionality is implemented instead of
				the onreadystatechange event because somehow
				the calling of this event crashed IE5.x
			*/
			id = this.queue.push([http, receive, null, new Date()])-1;
			
			this.queue[id][2] = new Function("var id='" + id + "';var dt = new Date(new Date().getTime() - XMLRPC.queue[id][3].getTime());diff = parseInt(dt.getSeconds()*1000 + dt.getMilliseconds());if(diff > XMLRPC.timeout){if(XMLRPC.ontimeout) XMLRPC.ontimeout(); clearInterval(XMLRPC.timers[id]);XMLRPC.cancel(id);return};if(XMLRPC.queue[id][0].readyState == 4){XMLRPC.queue[id][0].onreadystatechange = function(){};XMLRPC.receive(id);clearInterval(XMLRPC.timers[id])}");
			this.timers[id] = setInterval("XMLRPC.queue[" + id + "][2]()", 20);
		}
		
		//setting up the routing
		autoroute = (autoroute || this.autoroute);
		
		//'active' is only set when direct sending the message has failed
		var srv = (autoroute == "active") ? this.routeServer : serverAddress;
		
		try{
			http.open('POST', srv, async);
			http.setRequestHeader("User-Agent", "vcXMLRPC v0.91 (" + navigator.userAgent + ")");
			http.setRequestHeader("Host", srv.replace(/^https?:\/{2}([:\[\]\-\w\.]+)\/?.*/, '$1'));
			http.setRequestHeader("Content-type", "text/xml");
			if(autoroute == "active"){
				http.setRequestHeader("X-Proxy-Request", serverAddress);
				http.setRequestHeader("X-Compress-Response", "gzip");
			}
		}
		catch(e){
			if(autoroute == true){
				//Access has been denied, Routing call.
				autoroute = "active";
				if(id){
					delete this.queue[id];
					clearInterval(this.timers[id]);
				}
				return this.send(serverAddress, functionName, args, receive, multicall, autoroute);
			}
			
			//Routing didn't work either..Throwing error
			this.handleError(new Error("Could not sent XMLRPC Message (Reason: Access Denied on client)"));
			if(this.stop){this.stop = false;return false}
		}
		
		//Construct the message
		var message = '<?xml version="1.0"?><methodCall><methodName>' + functionName + '</methodName><params>';
   	for(i=0;i<args.length;i++){
   		message += '<param><value>' + this.getXML(args[i]) + '</value></param>';
		}
		message += '</params></methodCall>';
		
		var xmldom = this.getObject('XMLDOM', message);
		if(self.DEBUG) alert(message);
		
		try{
			//send message
			http.send(xmldom);
		}
		catch(e){
			//Most likely the message timed out(what happend to your internet connection?)
			this.handleError(new Error("XMLRPC Message not Sent(Reason: " + e.message + ")"));
			if(this.stop){this.stop = false;return false}
		}
		
		if(!async && receive)
			return [autoroute, receive(this.processResult(http))];
		else if(receive)
			return [autoroute, id];
		else
			return [autoroute, this.processResult(http)];
	},
	
	receive : function(id){
		//Function for handling async transfers..
		if(this.queue[id]){
			var data = this.processResult(this.queue[id][0]);
			this.queue[id][1](data);
			delete this.queue[id];
		}
		else{
			this.handleError(new Error("Error while processing queue"));
		}
	},
	
	processResult : function(http){
		if(self.DEBUG) alert(http.responseText);
		if(http.status == 200){
			//getIncoming message
		   dom = http.responseXML;

		   if(dom){
		   	var rpcErr, main;

		   	//Check for XMLRPC Errors
		   	rpcErr = dom.getElementsByTagName("fault");
		   	if(rpcErr.length > 0){
		   		rpcErr = this.toObject(rpcErr[0].firstChild);
		   		this.handleError(new Error(rpcErr.faultCode, rpcErr.faultString));
		   		return false
		   	}

		   	//handle method result
		   	main = dom.getElementsByTagName("param");
		      if(main.length == 0) this.handleError(new Error("Malformed XMLRPC Message"));
				data = this.toObject(this.getNode(main[0], [0]));

				//handle receiving
				if(this.onreceive) this.onreceive(data);
				return data;
		   }
		   else{
		  		this.handleError(new Error("Malformed XMLRPC Message"));
			}
		}
		else{
		    e = new Error("HTTP Exception: (" + http.status + ") " + http.statusText + "\n\n" + http.responseText);
		    e.http_status = http.status;
		    e.http_statusText = http.statusText;
		    this.handleError(e);
			//this.handleError(new Error("HTTP Exception: (" + http.status + ") " + http.statusText + "\n\n" + http.responseText));
		}
	}
}

//Smell something
ver = navigator.appVersion;
app = navigator.appName;
isNS = Boolean(navigator.productSub)
//moz_can_do_http = (parseInt(navigator.productSub) >= 20010308)

isIE = (ver.indexOf("MSIE 5") != -1 || ver.indexOf("MSIE 6") != -1) ? 1 : 0;
isIE55 = (ver.indexOf("MSIE 5.5") != -1) ? 1 : 0;

isOTHER = (!isNS && !isIE) ? 1 : 0;

if(isOTHER) alert("Sorry your browser doesn't support the features of vcXMLRPC");
