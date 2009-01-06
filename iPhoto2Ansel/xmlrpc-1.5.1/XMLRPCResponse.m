// 
// Copyright 2008 Eric Czarny <eczarny@gmail.com>
// 
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of  this  software  and  associated documentation files (the "Software"), to
// deal  in  the Software without restriction, including without limitation the
// rights  to  use,  copy,  modify,  merge,  publish,  distribute,  sublicense,
// and/or sell copies  of  the  Software,  and  to  permit  persons to whom the
// Software is furnished to do so, subject to the following conditions:
// 
// The  above  copyright notice and this permission notice shall be included in
// all copies or substantial portions of the Software.
// 
// THE  SOFTWARE  IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED,  INCLUDING  BUT  NOT  LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS  FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS  OR  COPYRIGHT  HOLDERS  BE  LIABLE  FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY,  WHETHER  IN  AN  ACTION  OF CONTRACT, TORT OR OTHERWISE, ARISING
// FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
// IN THE SOFTWARE.
// 

// 
// Cocoa XML-RPC Framework
// XMLRPCResponse.m
// 
// Created by Eric Czarny on Wednesday, January 14, 2004.
// Copyright 2008 Divisible by Zero.
// 

#import "XMLRPCResponse.h"
#import "XMLRPCDecoder.h"

@implementation XMLRPCResponse

- (id)initWithData: (NSData *)data
{
	if (data == nil) {
		return nil;
	}

	if (self = [super init]) {
		XMLRPCDecoder *responseXMLDecoder =[[XMLRPCDecoder alloc] initWithData: data];
		
		if (responseXMLDecoder == nil) {
			return nil;
		}
	
		responseXMLData = [[NSData alloc] initWithData: data];
		responseSourceXML = [[NSString alloc] initWithData: data encoding: NSUTF8StringEncoding];
		responseObject = [[responseXMLDecoder decode] retain];
		
		isFault = [responseXMLDecoder isFault];
		
		[responseXMLDecoder release];
	}
	
	return self;
}

#pragma mark -

- (BOOL)isFault {
	return isFault;
}

- (NSNumber *)faultCode {
	if (isFault) {
		return [responseObject objectForKey: @"faultCode"];
	}
	
	return nil;
}

- (NSString *)faultString {
	if (isFault) {
		return [responseObject objectForKey: @"faultString"];
	}
	
	return nil;
}

#pragma mark -

- (id)responseObject {
	return responseObject;
}

#pragma mark -

- (NSString *)responseSourceXML {
	return responseSourceXML;
}

#pragma mark -

- (void)dealloc {
	[responseXMLData release];
	[responseSourceXML release];
	[responseObject release];
	
	[super dealloc];
}

@end
