/**
 *  TURAnselConstants.m
 *  ApertureToAnselExportPlugin
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org)
 *
 * @license http://opensource.org/licenses/bsd-license.php
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 */

#import "TURAnselConstants.h"

// Constants for the API parameter names.
NSString * const kTURAnselAPIParamScope             = @"scope";
NSString * const kTURAnselAPIParamGalleryParams      = @"galleryParams";
NSString * const kTURAnselAPIParamPerms             = @"perms";
NSString * const kTURAnselAPIParamParent            = @"parent";
NSString * const kTURAnselAPIParamAllLevels         = @"allLevels";
NSString * const kTURAnselAPIParamOffset            = @"offset";
NSString * const kTURAnselAPIParamCount             = @"count";
NSString * const kTURAnselAPIParamUserOnly          = @"userOnly";
NSString * const kTURAnselAPIParamImageId           = @"imageId";
NSString * const kTURAnselAPIParamGalleryId         = @"galleryId";
NSString * const kTURAnselAPIParamThumbnailStyle    = @"thumbnailStyle";
NSString * const kTURAnselAPIParamFullPath          = @"fullPath";
NSString * const kTURAnselAPIParamImageData         = @"imageData";
NSString * const kTURAnselAPIParamSetAsDefault      = @"default";
NSString * const kTURAnselAPIParamAdditionalData    = @"additionalData";
NSString * const kTURAnselAPIParamEncoding          = @"encoding";

NSString * const kTURAnselAPIParamSingleParameter   = @"params";
NSString * const kTURAnselAPIParamView              = @"view";
NSString * const kTURAnselAPIParamFull              = @"full";

// Ansel gallery attribtues.
NSString * const kTURAnselGalleryKeyId              = @"share_id";
NSString * const kTURAnselGalleryKeyName            = @"attribute_name";
NSString * const kTURAnselGalleryKeyDescription     = @"attribute_desc";
NSString * const kTURAnselGalleryKeyImages          = @"attribute_images";
NSString * const kTURAnselGalleryKeyDefaultImage    = @"attribute_default";