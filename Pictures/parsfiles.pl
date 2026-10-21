#!/usr/bin/perl
use File::Basename;
use File::Path "make_path";
use File::Copy "copy";
use File::Spec;
use File::Glob ':glob';

use strict;
use warnings;
binmode STDOUT, ":utf8";
use utf8;
use JSON;

use constant false => 0;
use constant true  => 1;

print  "Start Script\n";
my ($filename, $scriptdirs, $suffix) = fileparse($0);
print "$scriptdirs\n";

print  "Create Working dirs\n";
my @dirs = grep { -d } glob $scriptdirs."*";

my @allGalleriesConfig;

foreach my $working_dir (@dirs) {
    # Only parse if image_org is available
    my $org_images = $working_dir."/org_images";
    #print ($working_dir ."\n");
    #print ($org_images ."\n");

    if (-e $org_images and -d $org_images) {
        my $galleryTitle = $working_dir;
        $galleryTitle =~ s/.*\/(.*)/$1/;

        push @allGalleriesConfig, { name => $galleryTitle, dir => $galleryTitle };

        my @imgFilesArray = ();
        print ("Parsing : ". $galleryTitle ."\n");
        my $thumbs_dir = $working_dir."/thumbs/";
        if ( !-d $thumbs_dir ) {
           make_path $thumbs_dir or die "Failed to create path: $thumbs_dir";
        }
        my $target_images_dir = $working_dir."/images/";
        if ( !-d $target_images_dir ) {
           make_path $target_images_dir or die "Failed to create path: $target_images_dir";
        }
        print (" $org_images \n");
        my @files = bsd_glob("$org_images/*.jpg", GLOB_NOCASE);
        if (! -e $org_images."/cover.jpg") {
           my $coverImg = splice(@files, rand @files, 1);
	        copy ($coverImg, $org_images."/cover.jpg");
           @files = bsd_glob("$org_images/*.jpg", GLOB_NOCASE);
        }

        
        if (-e $thumbs_dir."cover.jpg" || -e $target_images_dir."cover.jpg"){
            print  " ------------ DO NOT  Parse images --\n";
            print  " -- ". $thumbs_dir."cover.jpg\n";
            print  "      OR \n";
            print  " -- ".$target_images_dir."cover.jpg\n";
            print  " ALREADY avaiable\n";
            print  " REMOVE cover.jpg from \"$thumbs_dir\" and \"$target_images_dir\" to parse this directory again!\n";
            print  " ---------------------------------------- \n\n";
        }else{
            print  " ------------ Start Parsing images --\n";

            foreach my $file (@files) {
               #print $file . "\n";
               my $source_img = $file;
               
               my $img_name = lc $source_img;
               $img_name =~ s/.*\/(.*)/$1/;
               push @imgFilesArray, $img_name;

               my $target_img = lc $source_img;
               my ($filename, $dirs, $suffix) = fileparse($target_img);

            #   print $filename . "\n";

               $target_img = $thumbs_dir.$filename;
               # print $source_img . "\n";
            #  print $target_img . "\n";
               system 'smartcrop --width 100 --height 100 --faceDetection --outputFormat jpg "'. $source_img .'" "'. $target_img .'"';

               $target_img = $target_images_dir.$filename;
            #  print $source_img . "\n";
            #  print $target_img . "\n";
               system 'convert "'. $source_img .'" -resize 640x640\> "'. $target_img .'"';
            }



            system 'smartcrop --width 400 --height 400 --faceDetection --outputFormat jpg "'. $org_images."/cover.jpg" .'" "'. $target_images_dir."/cover.jpg" .'"';

            print  " --------- Finished Parsing images --\n";
            my $config_data = {
                  'imgFilesArray'=> \@imgFilesArray,
                  'galleryTitle' => $galleryTitle            
            };

            open my $fh, ">", $working_dir."/config.json";
            print " --------------- Write config.json --\n";
            print $fh encode_json($config_data);
            close $fh;
        }

    }

}

open my $fh, ">", $scriptdirs."/galleries_config.json";
print " --------------- Write galleries_config.json --\n";
print $fh encode_json( \@allGalleriesConfig );
close $fh;
