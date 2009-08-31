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
// XMLRPCRequest.m
// 
// Created by Eric Czarny on Wednesday, January 14, 2004.
// Copyright 2008 Divisible by Zero.
// 

#import "XMLRPCRequest.h"
#import "XMLRPCEncoder.h"

@implementation XMLRPCRequest

- (id)initWithHost: (NSURL *)host {
	if (self = [super init]) {
		if (host != nil) {
			mutableRequest = [[NSMutableURLRequest alloc] initWithURL: host];
		} else {
			mutableRequest = [[NSMutableURLRequest alloc] init];
		}
		
		requestXMLEncoder = [[XMLRPCEncoder alloc] init];
	}
	
	return self;
}

#pragma mark -

- (void)setHost: (NSURL *)host {
	[mutableRequest setURL: host];
}

- (NSURL *)host {
	return [mutableRequest URL];
}

#pragma mark -

- (void)setUserAgent: (NSString *)userAgent {
	if ([self userAgent] == nil) {
		[mutableRequest addValue: userAgent forHTTPHeaderField: @"User-Agent"];
	} else {
		[mutableRequest setValue: userAgent forHTTPHeaderField: @"User-Agent"];
	}
}

- (NSString *)userAgent {
	return [mutableRequest valueForHTTPHeaderField: @"User-Agent"];
}

#pragma mark -

- (void)setMethod: (NSString *)method {
	[requestXMLEncoder setMethod: method withParameters: nil];
}

- (void)setMethod: (NSString *)method withParameter: (id)parameter {
	[requestXMLEncoder setMethod: method withParameters: [NSArray arrayWithObject: parameter]];
}

- (void)setMethod: (NSString *)method withParameters: (NSArray *)parameters {
	[requestXMLEncoder setMethod: method withParameters: parameters];
}

#pragma mark -

- (NSString *)method {
	return [requestXMLEncoder method];
}

- (NSArray *)parameters {
	return [requestXMLEncoder parameters];
}

#pragma mark -

- (NSString *)requestSourceXML {
	return [requestXMLEncoder encoderSourceXML];
}

#pragma mark -

- (NSURLRequest *)request {
	NSData *request = [[requestXMLEncoder encode] dataUsingEncoding: NSUTF8StringEncoding];
	NSNumber *contentLength = [NSNumber numberWithInt: [request length]];
	
	if (request == nil) {
		return nil;
	}
	
	[mutableRequest setHTTPMethod: @"POST"];
	
	if ([mutableRequest valueForHTTPHeaderField: @"Content-Length"] == nil) {
		[mutableRequest addValue: @"text/xml" forHTTPHeaderField: @"Content-Type"];
	} else {
		[mutableRequest setValue: @"text/xml" forHTTPHeaderField: @"Content-Type"];
	}
	
	if ([mutableRequest valueForHTTPHeaderField: @"Content-Length"] == nil) {
		[mutableRequest addValue: [contentLength stringValue] forHTTPHeaderField: @"Content-Length"];
	} else {
		[mutableRequest setValue: [contentLength stringValue] forHTTPHeaderField: @"Content-Length"];
	}
	
	[mutableRequest setHTTPBody: request];
	
	return (NSURLRequest *)mutableRequest;
}

#pragma mark -

- (void)dealloc {
	[mutableRequest release];
	[requestXMLEncoder release];
	
	[super dealloc];
}

@end
