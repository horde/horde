//
//  AnselGalleryViewItem.m
//  iPhoto2Ansel
//
//  Created by Michael Rubinsky on 5/7/09.
//  Copyright 2009 __MyCompanyName__. All rights reserved.
//

#import "AnselGalleryViewItem.h"

@implementation AnselGalleryViewItem
@synthesize image;
@synthesize imageID;

- (id)initWithURL:(NSURL *)theURL
{
    [super init];
    image = [theURL retain];
    imageID = [[theURL absoluteString] retain];
    return self; 
}

- (void)dealloc
{
    [image release];
    [imageID release];
    [super dealloc];
}

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
@end
