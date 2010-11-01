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
// NSDataAdditions.m
//
// Created by Eric Czarny on Wednesday, January 14, 2004.
// Copyright 2008 Divisible by Zero.
//

#import "NSDataAdditions.h"

@implementation NSData (NSDataAdditions)

+ (NSData *)base64DataFromString: (NSString *)string {
	unsigned long ixtext, lentext;
	unsigned char ch, input[4], output[3];
	short i, ixinput;
	Boolean flignore, flendtext = false;
	const char *temporary;
	NSMutableData *result;

	if (string == nil) {
		return [NSData data];
	}

	ixtext = 0;

	temporary = [string UTF8String];

	lentext = [string length];

	result = [NSMutableData dataWithCapacity: lentext];

	ixinput = 0;

	while (true) {
		if (ixtext >= lentext) {
			break;
		}

		ch = temporary[ixtext++];

		flignore = false;

		if ((ch >= 'A') && (ch <= 'Z')) {
			ch = ch - 'A';
		} else if ((ch >= 'a') && (ch <= 'z')) {
			ch = ch - 'a' + 26;
		} else if ((ch >= '0') && (ch <= '9')) {
			ch = ch - '0' + 52;
		} else if (ch == '+') {
			ch = 62;
		} else if (ch == '=') {
			flendtext = true;
		} else if (ch == '/') {
			ch = 63;
		} else {
			flignore = true;
		}

		if (!flignore) {
			short ctcharsinput = 3;
			Boolean flbreak = false;

			if (flendtext) {
				if (ixinput == 0) {
					break;
				}

				if ((ixinput == 1) || (ixinput == 2)) {
					ctcharsinput = 1;
				} else {
					ctcharsinput = 2;
				}

				ixinput = 3;

				flbreak = true;
			}

			input[ixinput++] = ch;

			if (ixinput == 4) {
				ixinput = 0;

				output[0] = (input[0] << 2) | ((input[1] & 0x30) >> 4);
				output[1] = ((input[1] & 0x0F) << 4) | ((input[2] & 0x3C) >> 2);
				output[2] = ((input[2] & 0x03) << 6) | (input[3] & 0x3F);

				for (i = 0; i < ctcharsinput; i++) {
					[result appendBytes: &output[i] length: 1];
				}
			}

			if (flbreak) {
				break;
			}
		}
	}

	return result;
}

@end
