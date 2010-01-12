/**
 * AnselGalleryViewItem.m
 *
 * Implements the IKImageBrowserItem protocol for displaying images from a
 * remote Ansel gallery in an IKImageBrowser.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org)
 * 
 * @implements IKImageBrowserItem
 * @license http://opensource.org/licenses/bsd-license.php
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 */
#import "AnselGalleryViewItem.h"

@implementation AnselGalleryViewItem
@synthesize image;
@synthesize imageID;

- (id)initWithURL: (NSURL *)theURL
        withTitle: (NSString *)theTitle
         withDate: (NSDate *)theDate
{
    [super init];
    image = [theURL retain];
    imageID = [[theURL absoluteString] retain];
    imageTitle = [theTitle retain];
    imageDate =  [theDate retain];
    return self; 
}
- (void)dealloc
{
    [image release];
    [imageID release];
    [imageTitle release];
    [imageDate release];
    [super dealloc];
}

#pragma mark
#pragma mark Required methods
- (NSString *)imageUID
{
    return imageID;
}

- (NSString *)imageRepresentationType
{
    return IKImageBrowserNSURLRepresentationType;
}

- (id)imageRepresentation
{
    return image;
}

#pragma mark
#pragma mark Optional methods.
- (NSString *)imageTitle
{
    NSLog(@"imageTitle: %@", imageTitle);
    return imageTitle;
}
- (NSString *)imageSubtitle
{
    NSLog(@"imageSubtitle: %@", [imageDate description]);
    return [imageDate description];
}

@end
