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
// XMLRPCConnection.m
// 
// Created by Eric Czarny on Thursday, January 15, 2004.
// Copyright 2008 Divisible by Zero.
// 

#import "XMLRPCConnection.h"
#import "XMLRPCRequest.h"
#import "XMLRPCResponse.h"

NSString *XMLRPCSentRequestNotification = @"XML-RPC Sent Request";
NSString *XMLRPCRequestFailedNotification = @"XML-RPC Failed Receiving Response";
NSString *XMLRPCReceivedAuthenticationChallengeNotification = @"XML-RPC Received Authentication Challenge";
NSString *XMLRPCCancelledAuthenticationChallengeNotification = @"XML-RPC Cancelled Authentication Challenge";
NSString *XMLRPCReceivedResponseNotification = @"XML-RPC Successfully Received Response";

@interface XMLRPCConnection (XMLRPCConnectionPrivate)

- (void)connection: (NSURLConnection *)connection didReceiveResponse:(NSURLResponse *)response;

- (void)connection: (NSURLConnection *)connection didReceiveData: (NSData *)data;

- (void)connection: (NSURLConnection *)connection didFailWithError: (NSError *)error;

- (void)connection: (NSURLConnection *)connection didReceiveAuthenticationChallenge: (NSURLAuthenticationChallenge *)challenge;

- (void)connection: (NSURLConnection *)connection didCancelAuthenticationChallenge: (NSURLAuthenticationChallenge *)challenge;

- (void)connectionDidFinishLoading: (NSURLConnection *)connection;

@end

#pragma mark -

@implementation XMLRPCConnection

- (id)initWithXMLRPCRequest: (XMLRPCRequest *)request delegate: (id)delegate {
	if (self = [super init]) {
		incomingXMLData = [[NSMutableData alloc] init];
		currentConnection = [[NSURLConnection alloc] initWithRequest: [request request] delegate: self];
		
		applicationDelegate = delegate;
		
		if (currentConnection != nil) {
			currentXMLRPCMethod = [[NSString alloc] initWithString: [request method]];
			
			[request release];
			
			[[NSNotificationCenter defaultCenter] postNotificationName: XMLRPCSentRequestNotification object: nil];
		} else {
			if ([applicationDelegate respondsToSelector: @selector(connection:didFailWithError:forMethod:)]) {
				[applicationDelegate connection: self didFailWithError: nil forMethod: [request method]];
			}
			
			[request release];
						
			return nil;
		}
	}
	
	return self;
}

#pragma mark -

+ (XMLRPCResponse *)sendSynchronousXMLRPCRequest: (XMLRPCRequest *)request {
	NSData *data = [[[NSURLConnection sendSynchronousRequest: [request request] 
		returningResponse: nil error: nil] retain] autorelease];
	
	[request release];
	
	if (data != nil) {
		return [[[XMLRPCResponse alloc] initWithData: data] autorelease];
	}
	
	return nil;
}

#pragma mark -

- (void)cancel {
	[currentConnection cancel];
	
	[currentConnection release];
}

#pragma mark -

- (void)dealloc {
	[currentXMLRPCMethod release];
	[incomingXMLData release];
	NSLog(@"XMLRPCConnection being released");
	[super dealloc];
}

@end

#pragma mark -

@implementation XMLRPCConnection (XMLRPCConnectionPrivate)

- (void)connection: (NSURLConnection *)connection didReceiveResponse: (NSURLResponse *)response {
	if([response respondsToSelector: @selector(statusCode)]) {
		NSInteger statusCode = [(NSHTTPURLResponse *)response statusCode];
		
		if(statusCode >= 400) {
			[connection cancel];
			
			if ([applicationDelegate respondsToSelector: @selector(connection:didFailWithError:forMethod:)]) {
				NSError *error = [NSError errorWithDomain: NSCocoaErrorDomain code: statusCode userInfo: nil];
				
				[applicationDelegate connection: self didFailWithError: error forMethod: currentXMLRPCMethod];
			}
			
			[connection release];
		}
	}
	
	[incomingXMLData setLength: 0];
}

- (void)connection: (NSURLConnection *)connection didReceiveData: (NSData *)data {
	[incomingXMLData appendData: data];
}

- (void)connection: (NSURLConnection *)connection didFailWithError: (NSError *)error {
	if ([applicationDelegate respondsToSelector: @selector(connection:didFailWithError:forMethod:)]) {
		[applicationDelegate connection: self didFailWithError: error forMethod: currentXMLRPCMethod];
	}
	
	[[NSNotificationCenter defaultCenter] postNotificationName: XMLRPCRequestFailedNotification object: nil];
	
	[connection release];
}

- (void)connection: (NSURLConnection *)connection didReceiveAuthenticationChallenge: (NSURLAuthenticationChallenge *)challenge {
	if ([applicationDelegate respondsToSelector: @selector(connection:didReceiveAuthenticationChallenge:forMethod:)]) {
		[applicationDelegate connection: self didReceiveAuthenticationChallenge: challenge forMethod: currentXMLRPCMethod];
	}
	
	[[NSNotificationCenter defaultCenter] postNotificationName: XMLRPCReceivedAuthenticationChallengeNotification object: nil];
}

- (void)connection: (NSURLConnection *)connection didCancelAuthenticationChallenge: (NSURLAuthenticationChallenge *)challenge {
	if ([applicationDelegate respondsToSelector: @selector(connection:didCancelAuthenticationChallenge:forMethod:)]) {
		[applicationDelegate connection: self didCancelAuthenticationChallenge: challenge forMethod: currentXMLRPCMethod];
	}
	
	[[NSNotificationCenter defaultCenter] postNotificationName: XMLRPCCancelledAuthenticationChallengeNotification object: nil];
}

- (void)connectionDidFinishLoading: (NSURLConnection *)connection {
	XMLRPCResponse *response = [[XMLRPCResponse alloc] initWithData: incomingXMLData];
	
	if ([applicationDelegate respondsToSelector: @selector(connection:didReceiveResponse:forMethod:)]) {
		[applicationDelegate connection: self didReceiveResponse: response forMethod: currentXMLRPCMethod];
	}
	
	[[NSNotificationCenter defaultCenter] postNotificationName: XMLRPCReceivedResponseNotification object: nil];
	
	[connection release];
}

@end
