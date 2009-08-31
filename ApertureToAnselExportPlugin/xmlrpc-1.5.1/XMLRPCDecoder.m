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
// XMLRPCDecoder.m
// 
// Created by Eric Czarny on Wednesday, January 14, 2004.
// Copyright 2008 Divisible by Zero.
// 

#import "XMLRPCDecoder.h"
#import "NSDataAdditions.h"

@interface XMLRPCDecoder (XMLRPCDecoderPrivate)

- (NSXMLElement *)getChildFromElement: (NSXMLElement *)element withName: (NSString *)name;

#pragma mark -

- (id)decodeObject: (NSXMLElement *)element;

- (id)trueDecodeObject: (NSXMLElement *)element;

#pragma mark -

- (NSArray *)decodeArray: (NSXMLElement *)element;

#pragma mark -

- (NSDictionary *)decodeDictionary: (NSXMLElement *)element;

#pragma mark -

- (NSNumber *)decodeNumber: (NSXMLElement *)element isDouble: (BOOL)flag;

- (CFBooleanRef)decodeBool: (NSXMLElement *)element;

- (NSString *)decodeString: (NSXMLElement *)element;

- (NSDate *)decodeDate: (NSXMLElement *)element;

- (NSData *)decodeData: (NSXMLElement *)element;

@end

#pragma mark -

@implementation XMLRPCDecoder

- (id)initWithData: (NSData *)data {
	if (data == nil) {
		return nil;
	}
	
	if (self = [super init]) {
		NSError *error = nil;
		responseXMLDocument = [[NSXMLDocument alloc] initWithData: data options: NSXMLDocumentTidyXML error: &error];
		
		if (responseXMLDocument == nil) {
			if (error) {
				NSLog(@"Encountered an XML error: %@", error);
			}
			
			return nil;
		}
		
		if (error) {
			NSLog(@"Encountered an XML error: %@", error);
			
			return nil;
		}
	}
	
	return self;
}

#pragma mark -

- (id)decode {
	NSXMLElement *child, *root = [responseXMLDocument rootElement];
	
	if (root == nil) {
		return nil;
	}
	
	child = [self getChildFromElement: root withName: @"params"];
	
	if (child != nil) {
		child = [self getChildFromElement: child withName: @"param"];
		
		if (child == nil) {
			return nil;
		}
		
		child = [self getChildFromElement: child withName: @"value"];
		
		if (child == nil) {
			return nil;
		}
	} else {
		child = [self getChildFromElement: root withName: @"fault"];
		
		if (child == nil) {
			return nil;
		}
		
		child = [self getChildFromElement: child withName: @"value"];
		
		if (child == nil) {
			return nil;
		}
		
		isFault = YES;
	}
	
	return [self decodeObject: child];
}

#pragma mark -

- (BOOL)isFault {
	return isFault;
}

#pragma mark -

- (void)dealloc {
	[responseXMLDocument release];
	
	[super dealloc];
}

@end

#pragma mark -

@implementation XMLRPCDecoder (XMLRPCDecoderPrivate)

- (NSXMLElement *)getChildFromElement: (NSXMLElement *)element withName: (NSString *)name {
	NSArray *children = [element elementsForName: name];
	
	if ([children count] > 0) {
		return [children objectAtIndex: 0];
	}
	
	return nil;
}

#pragma mark -

- (id)decodeObject: (NSXMLElement *)element {
	NSXMLElement *child = (NSXMLElement *)[element childAtIndex: 0];
	
	if (child != nil) {
		return [self trueDecodeObject: child];
	}
	
	return nil;
}

- (id)trueDecodeObject: (NSXMLElement *)element {
	NSString *name = [element name];
	
	if ([name isEqualToString: @"array"]) {
		return [self decodeArray: element];
	} else if ([name isEqualToString: @"struct"]) {
		return [self decodeDictionary: element];
	} else if ([name isEqualToString: @"int"] || [name isEqualToString: @"i4"]) {
		return [self decodeNumber: element isDouble: NO];
	} else if ([name isEqualToString: @"double"]) {
		return [self decodeNumber: element isDouble: YES];
	} else if ([name isEqualToString: @"boolean"]) {
		return (id)[self decodeBool: element];
	} else if ([name isEqualToString: @"string"]) {
		return [self decodeString: element];
	} else if ([name isEqualToString: @"dateTime.iso8601"]) {
		return [self decodeDate: element];
	} else if ([name isEqualToString: @"base64"]) {
		return [self decodeData: element];
	} else {
		return [self decodeString: element];
	}
	
	return nil;
}

#pragma mark -

- (NSArray *)decodeArray: (NSXMLElement *)element {
	NSXMLElement *parent = [self getChildFromElement: element withName: @"data"];
	NSMutableArray *array = [NSMutableArray array];
	NSInteger index;
	
	if (parent == nil) {
		return nil;
	}
	
	for (index = 0; index < [parent childCount]; index++) {
		NSXMLElement *child = (NSXMLElement *)[parent childAtIndex: index];
		
		if (![[child name] isEqualToString: @"value"]) {
			continue;
		}
		
		id value = [self decodeObject: child];
		
		if (value != nil) {
			[array addObject: value];
		}
	}
	
	return (NSArray *)array;
}

#pragma mark -

- (NSDictionary *)decodeDictionary: (NSXMLElement *)element {
	NSMutableDictionary *dictionary = [NSMutableDictionary dictionary];
	NSInteger index;
	
	for (index = 0; index < [element childCount]; index++) {
		NSXMLElement *child, *parent = (NSXMLElement *)[element childAtIndex: index];
		
		if (![[parent name] isEqualToString: @"member"]) {
			continue;
		}
		
		child = [self getChildFromElement: parent withName: @"name"];
		
		if (child == nil) {
			continue;
		}
		
		NSString *key = [child stringValue];
		
		child = [self getChildFromElement: parent withName: @"value"];
		
		if (child == nil) {
			continue;
		}
		
		id object = [self decodeObject: child];
		
		if ((object != nil) && (key != nil) && ![key isEqualToString: @""]) {
			[dictionary setObject: object forKey: key];
		}
	}
	
	return (NSDictionary *)dictionary;
}

#pragma mark -

- (NSNumber *)decodeNumber: (NSXMLElement *)element isDouble: (BOOL)flag {
	if (flag) {
		return [NSNumber numberWithDouble: [[element stringValue] intValue]];
	}
	
	return [NSNumber numberWithInt: [[element stringValue] intValue]];
}

- (CFBooleanRef)decodeBool: (NSXMLElement *)element {
	if ([[element stringValue] isEqualToString: @"1"]) {
		return kCFBooleanTrue;
	}
	
	return kCFBooleanFalse;
}

- (NSString *)decodeString: (NSXMLElement *)element {
	return [element stringValue];
}

- (NSDate *)decodeDate: (NSXMLElement *)element {
	NSCalendarDate *date = [NSCalendarDate dateWithString: [element stringValue] 
		calendarFormat: @"%Y%m%dT%H:%M:%S" locale: nil];
	
	return date;
}

- (NSData *)decodeData: (NSXMLElement *)element {
	return [NSData base64DataFromString: [element stringValue]];
}

@end
