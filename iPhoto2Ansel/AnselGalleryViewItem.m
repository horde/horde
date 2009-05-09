//
//  AnselGalleryViewItem.m
//  iPhoto2Ansel
//
//  Implementation of the IKImageBrowserItem protocol

//  Created by Michael Rubinsky on 5/7/09.
//  Copyright 2009 __MyCompanyName__. All rights reserved.
//

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
    return imageTitle;
}

- (NSString *)imageSubtitle
{
    return [imageDate description];
}

@end
