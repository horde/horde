/**
 * TURAnselGalleryPanelController.m
 *
 * Controller for handling the form that creates new remote Ansel galleries.
 *
 * Copyright 2009-2014 Horde LLC (http://www.horde.org/)
 *
 * @license http://www.horde.org/licenses/bsd
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 */
#import "TURAnselGalleryPanelController.h"

@implementation TURAnselGalleryPanelController

#pragma mark -
#pragma mark init/dealloc
-(id)initWithController: (TURAnsel *)theController
{
    [super init];
    _anselController = [theController retain];
    [NSBundle loadNibNamed: @"AnselGalleryPanel"
                     owner: self];

    return self;
}
- (id)initWithController: (TURAnsel *)theController
         withGalleryName: (NSString *)galleryName
{

    [super init];
    _anselController = [theController retain];
    [NSBundle loadNibNamed: @"AnselGalleryPanel"
                     owner: self];

    [galleryNameTextField setStringValue: galleryName];

    return self;
}
- (void)dealloc
{
    [_anselController release];
    [_controllerWindow release];
    [super dealloc];
}

#pragma mark -
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
        [alert beginSheetModalForWindow: _controllerWindow
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

    NSDictionary *results = [[_anselController createNewGallery: params] retain];

    [NSApp endSheet: newGallerySheet];
    [newGallerySheet orderOut: nil];

    if ([_anselController state] != TURAnselStateError) {
        NSAlert *alert = [[NSAlert alloc] init];
        [alert setMessageText: @"Gallery successfully created."];
        [alert beginSheetModalForWindow: _controllerWindow
                          modalDelegate: nil
                         didEndSelector: nil
                            contextInfo: nil];
        [alert release];
        if ([_delegate respondsToSelector:@selector(TURAnselGalleryPanelDidAddGallery)]) {
            [_delegate TURAnselGalleryPanelDidAddGallery];
        }
    }

    [results release];
}


- (void)setDelegate: (id)theDelegate
{
    _delegate = theDelegate; // weak
}


- (void)showSheetForWindow: (NSWindow *)theWindow
{
    [_controllerWindow release];
    _controllerWindow = [theWindow retain];
    [NSApp beginSheet: newGallerySheet
       modalForWindow: theWindow
        modalDelegate: nil
       didEndSelector: nil
          contextInfo: nil];
}

@end
