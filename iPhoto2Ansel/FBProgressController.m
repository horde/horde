/*
 * Facebook Exporter for iPhoto Software License
 * Copyright 2007, Facebook, Inc.
 * All rights reserved.
 * Permission is hereby granted, free of charge, to any person or organization 
 * obtaining a copy of the software and accompanying documentation covered by 
 * this license (which, together with any graphical images included with such 
 * software, are collectively referred to below as the “Software”) to (a) use, 
 * reproduce, display, distribute, execute, and transmit the Software, (b) 
 * prepare derivative works of the Software (excluding any graphical images 
 * included with the Software, which may not be modified or altered), and (c) 
 * permit third-parties to whom the Software is furnished to do so, all 
 * subject to the following:
 *
 * - Redistributions of source code must retain the above copyright notice, 
 *   this list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright notice, 
 *   this list of conditions and the following disclaimer in the documentation 
 *   and/or other materials provided with the distribution.
 * - Neither the name of Facebook, Inc. nor the names of its contributors may 
 *   be used to endorse or promote products derived from this software without 
 *   specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" 
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE 
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE 
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE 
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR 
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF 
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS 
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN 
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) 
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
 * POSSIBILITY OF SUCH DAMAGE.
 *
 */

//
//  FBProgressController.m
//  FacebookExport
//
//  Created by Josh Wiseman on 1/28/07.
//

#import "FBProgressController.h"

#import "AnselExportController.h"

NSString* const CompleteStatus = @"Export completed!";

@implementation FBProgressController

- (id)initWithFBExport:(AnselExportController *)fbe {
    if ((self = [super init])) {
        fbExport = fbe;
        [NSBundle loadNibNamed: @"ProgressSheet" owner: self];
    }
    return self;
}

- (void)dealloc {
    [super dealloc];
}

- (void)awakeFromNib {
    [progressIndicator setUsesThreadedAnimation: YES];
}

- (IBAction)cancel:(id)sender {
    [self setStatus: @"Cancelling"];
    [progressIndicator setIndeterminate: YES];
    [progressIndicator startAnimation: nil];
    [fbExport cancelExport];
}

- (void)startProgress {
    [progressIndicator setDoubleValue: 0.0];
    [progressIndicator setIndeterminate: YES];
    [progressIndicator startAnimation: nil];
    [self setStatus: @"Started export"];
    
    [NSApp beginSheet: progressPanel modalForWindow: [fbExport window] modalDelegate: nil didEndSelector: nil contextInfo: nil];
}

- (void)stopProgress {
    [progressIndicator stopAnimation: nil];
    [NSApp endSheet: progressPanel];
    [progressPanel orderOut: nil];
}

- (void)setStatus:(NSString *)status {
    [statusField setStringValue: status];
    [statusField display];
}

- (void)setPercent:(NSNumber *)percent {
    if (percent == nil)
        [progressIndicator setIndeterminate: YES];
    else {
        if ([progressIndicator isIndeterminate])
            [progressIndicator setIndeterminate: NO];
        [progressIndicator setDoubleValue: [percent doubleValue]];
    }
    [progressIndicator display];
}

@end
