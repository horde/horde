//
//  TURAnselGalleryPanelController.m
//  iPhoto2Ansel
//
//  Created by Michael Rubinsky on 12/7/08.
//  Copyright 2008 __MyCompanyName__. All rights reserved.
//

#import "TURAnselGalleryPanelController.h"

@implementation TURAnselGalleryPanelController

#pragma mark IBActions
- (IBAction)cancelNewGallery: (id)sender
{
    [NSApp endSheet: newGallerySheet];
    [newGallerySheet orderOut: nil];   
}

- (IBAction)doNewGallery: (id)sender
{
    // Get Gallery Properties from the panel.
    NSString *galleryName = [galleryNameTextField stringValue];
    NSString *gallerySlug = [gallerySlugTextField stringValue];
    NSString *galleryDescription = [galleryDescTextField stringValue];
    
    if (!galleryName) {
        
        [NSApp endSheet: newGallerySheet];
        [newGallerySheet orderOut: nil];
        
        NSAlert *alert = [[NSAlert alloc] init];
        [alert setMessageText:@"Gallery names cannot be empty"];
        [alert setAlertStyle: NSCriticalAlertStyle];
        [alert beginSheetModalForWindow: controllerWindow
                          modalDelegate: nil 
                         didEndSelector: nil
                            contextInfo: nil];
        [alert release];
        return;
    }
    NSDictionary *params = [NSDictionary dictionaryWithObjectsAndKeys:
                            galleryName, @"name",
                            gallerySlug, @"slug",
                            galleryDescription, @"desc", nil];
    
    NSDictionary *results = [[anselController createNewGallery: params] retain];
    
    [NSApp endSheet: newGallerySheet];
    [newGallerySheet orderOut: nil];
    
    if ([anselController state] != TURAnselStateError) {
        NSAlert *alert = [[NSAlert alloc] init];
        [alert setMessageText: @"Gallery successfully created."];
        [alert beginSheetModalForWindow: controllerWindow
                          modalDelegate: nil
                         didEndSelector: nil
                            contextInfo: nil];
        [alert release];
        if ([delegate respondsToSelector:@selector(TURAnselGalleryPanelDidAddGallery)]) {
            [delegate TURAnselGalleryPanelDidAddGallery];
        }
    }
    
    [results release];
}

-(id)initWithController: (TURAnsel *)theController
{
    [super init];
    anselController = [theController retain];
    [NSBundle loadNibNamed: @"AnselGalleryPanel"
                     owner: self];
    
    return self;
}

- (id)initWithController: (TURAnsel *)theController
       withGalleryName: (NSString *)galleryName 
{
    
    [super init];
    anselController = [theController retain];
    [NSBundle loadNibNamed: @"AnselGalleryPanel"
                     owner: self];
    
    [galleryNameTextField setStringValue: galleryName];
    
    return self;  
    
    
}

- (void)setDelegate: (id)theDelegate
{
    delegate = theDelegate; // weak
}


- (void)showSheetForWindow: (NSWindow *)theWindow
{
    [controllerWindow release];
    controllerWindow = [theWindow retain];
    [NSApp beginSheet: newGallerySheet
       modalForWindow: theWindow
        modalDelegate: nil
       didEndSelector: nil
          contextInfo: nil];
}

- (void)dealloc 
{
    [anselController release];
    [controllerWindow release];
    [super dealloc];
}

@end
