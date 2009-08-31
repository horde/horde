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
// XMLRPCEncoder.m
// 
// Created by Eric Czarny on Wednesday, January 14, 2004.
// Copyright 2008 Divisible by Zero.
// 

#import "XMLRPCEncoder.h"
#import "NSStringAdditions.h"

@interface XMLRPCEncoder (XMLRPCEncoderPrivate)

- (NSString *)valueTag: (NSString *)tag value: (NSString *)value;

#pragma mark -

- (NSString *)replaceTarget: (NSString *)target withValue: (NSString *)value inString: (NSString *)string;

- (NSString *)escapeValue: (NSString *)value;

#pragma mark -

- (NSString *)encodeObject: (id)object;

#pragma mark -

- (NSString *)encodeArray: (NSArray *)array;

- (NSString *)encodeDictionary: (NSDictionary *)dictionary;

#pragma mark -

- (NSString *)encodeBoolean: (CFBooleanRef)boolean;

- (NSString *)encodeNumber: (NSNumber *)number;

- (NSString *)encodeString: (NSString *)string;

- (NSString *)encodeDate: (NSDate *)date;

- (NSString *)encodeData: (NSData *)data;

@end

#pragma mark -

@implementation XMLRPCEncoder

- (id)init {
	if (self = [super init]) {
		currentXMLRPCMethod = [[NSString alloc] init];
		encoderSourceXML = [[NSString alloc] init];
		currentXMLRPCParameters = [[NSArray alloc] init];
	}
	
	return self;
}

#pragma mark -

- (NSString *)encode {
	NSMutableString *buffer = [NSMutableString stringWithString: @"<?xml version=\"1.0\"?><methodCall>"];
	
	[buffer appendFormat: @"<methodName>%@</methodName>", currentXMLRPCMethod];
	
	[buffer appendString: @"<params>"];
	
	if (currentXMLRPCParameters != nil) {
		NSEnumerator *enumerator = [currentXMLRPCParameters objectEnumerator];
		id parameter = nil;
		
		while (parameter = [enumerator nextObject]) {
			[buffer appendString: @"<param>"];
			[buffer appendString: [self encodeObject: parameter]];
			[buffer appendString: @"</param>"];
		}
	}
	
	[buffer appendString: @"</params>"];
	
	[buffer appendString: @"</methodCall>"];
	
	return buffer;
}

#pragma mark -

- (void)setMethod: (NSString *)method withParameters: (NSArray *)parameters {
	if (currentXMLRPCMethod != nil)	{
		[currentXMLRPCMethod release];
	}
	
	if (method == nil) {
		currentXMLRPCMethod = nil;
	} else {
		currentXMLRPCMethod = [method retain];
	}
	
	if (currentXMLRPCParameters != nil) {
		[currentXMLRPCParameters release];
	}
	
	if (parameters == nil) {
		currentXMLRPCParameters = nil;
	} else {
		currentXMLRPCParameters = [parameters retain];
	}
}

#pragma mark -

- (NSString *)method {
	return currentXMLRPCMethod;
}

- (NSArray *)parameters {
	return currentXMLRPCParameters;
}

#pragma mark -

- (NSString *)encoderSourceXML {
	if (encoderSourceXML != nil) {
		[encoderSourceXML release];
	}
	
	encoderSourceXML = [[self encode] retain];
	
	return encoderSourceXML;
}

#pragma mark -

- (void)dealloc {
	[currentXMLRPCMethod release];
	[encoderSourceXML release];
	[currentXMLRPCParameters release];
	
	[super dealloc];
}

@end

#pragma mark -

@implementation XMLRPCEncoder (XMLRPCEncoderPrivate)

- (NSString *)valueTag: (NSString *)tag value: (NSString *)value {
	return [NSString stringWithFormat: @"<value><%@>%@</%@></value>", tag, [self escapeValue: value], tag];
}

#pragma mark -

- (NSString *)replaceTarget: (NSString *)target withValue: (NSString *)value inString: (NSString *)string {
	return [[string componentsSeparatedByString: target] componentsJoinedByString: value];	
}

- (NSString *)escapeValue: (NSString *)value {
	value = [self replaceTarget: @"&" withValue: @"&amp;" inString: value];
	value = [self replaceTarget: @"<" withValue: @"&lt;" inString: value];
	
	return value;
}

#pragma mark -

- (NSString *)encodeObject: (id)object {
	if (object == nil) {
		return nil;
	}
	
	if ([object isKindOfClass: [NSArray class]]) {
		return [self encodeArray: object];
	} else if ([object isKindOfClass: [NSDictionary class]]) {
		return [self encodeDictionary: object];
	} else if (((CFBooleanRef)object == kCFBooleanTrue) || ((CFBooleanRef)object == kCFBooleanFalse)) {
		return [self encodeBoolean: (CFBooleanRef)object];
	} else if ([object isKindOfClass: [NSNumber class]]) {
		return [self encodeNumber: object];
	} else if ([object isKindOfClass: [NSString class]]) {
		return [self encodeString: object];
	} else if ([object isKindOfClass: [NSDate class]]) {
		return [self encodeDate: object];
	} else if ([object isKindOfClass: [NSData class]]) {
		return [self encodeData: object];
	} else {
		return [self encodeString: object];
	}
}

#pragma mark -

- (NSString *)encodeArray: (NSArray *)array {
	NSMutableString *buffer = [NSMutableString string];
	NSEnumerator *enumerator = [array objectEnumerator];
	
	[buffer appendString: @"<value><array><data>"];
	
	id object = nil;
	
	while (object = [enumerator nextObject]) {
		[buffer appendString: [self encodeObject: object]];
	}
	
	[buffer appendString: @"</data></array></value>"];
	
	return (NSString *)buffer;
}

- (NSString *)encodeDictionary: (NSDictionary *)dictionary {
	NSMutableString * buffer = [NSMutableString string];
	NSEnumerator *enumerator = [dictionary keyEnumerator];
	
	[buffer appendString: @"<value><struct>"];
	
	NSString *key = nil;
	
	while (key = [enumerator nextObject]) {
		[buffer appendString: @"<member>"];
		[buffer appendFormat: @"<name>%@</name>", key];
		[buffer appendString: [self encodeObject: [dictionary objectForKey: key]]];
		[buffer appendString: @"</member>"];
	}
	
	[buffer appendString: @"</struct></value>"];
	
	return (NSString *)buffer;
}

#pragma mark -

- (NSString *)encodeBoolean: (CFBooleanRef)boolean {
	if (boolean == kCFBooleanTrue) {
		return [self valueTag: @"boolean" value: @"1"];
	} else {
		return [self valueTag: @"boolean" value: @"0"];
	}
}

- (NSString *)encodeNumber: (NSNumber *)number {
	return [self valueTag: @"i4" value: [number stringValue]];
}

- (NSString *)encodeString: (NSString *)string {
	return [self valueTag: @"string" value: string];
}

- (NSString *)encodeDate: (NSDate *)date {
	NSString *buffer = [date descriptionWithCalendarFormat: @"%Y%m%dT%H:%M:%S"
		timeZone: nil locale: nil];

	return [self valueTag: @"dateTime.iso8601" value: buffer];
}

- (NSString *)encodeData: (NSData *)data {
	NSString *buffer = [NSString base64StringFromData: data
		length: [data length]];

	return [self valueTag: @"base64" value: buffer];
}

@end
