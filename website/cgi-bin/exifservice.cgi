#!/usr/bin/perl -w
use strict;
use warnings;
use lib 'ExifTool/lib';
use CGI qw(:standard);
use JSON;
use Image::ExifTool;

print "Content-Type: application/json; Charset=UTF-8\n\n";

my $query, my $img, my $folder, my $file, my $ext;
$query = CGI->new;
# this has to be a physical path, not a path aliased by the webserver!
$folder = '/media/sf_Bilder/';
$img = $query->url_param('img');
$img = '2015-09-Australia/2015-09-Australia-001.nef';
$ext = substr($img, rindex($img, '.') + 1);
$file = substr($img, 0, rindex($img, '.'));



my $exifTool = new Image::ExifTool;
#$exifTool->Options(CoordFormat => "%+.8f", DateFormat => "%d.%m.%Y %H:%M:%S", json => 1);
$exifTool->Options(Sort => 'Tag');

#The 'quote word' function qw() is used to generate a list of words.
# use ImageSize instead of ImageWidth and ImageHeight which are double and contain the thumbnail size
my @tags, my $exif, my $xmp;

@tags = qw(exif:all);
$exif = $exifTool->ImageInfo($folder.$img);
foreach (keys %$exif) {
    print "$_ => $$exif{$_}\n";
}


@tags = qw(xmp-crs:all);
$xmp = $exifTool->ImageInfo($folder.$file.'.xmp', \@tags);

print "\n\n-------------\n\n";
@tags = qw(Nikon:all);
my $nikon = $exifTool->ImageInfo($folder.$img, \@tags);
foreach (keys %$nikon) {
    print "$_ => $$nikon{$_}\n";
}


#foreach (keys %$exif) {
#    print "$_ => $$exif{$_}\n";
#}
#print '{"exif":'.to_json($exif).', '."\n".'"xmp":'.to_json($xmp).'}';
#print '{"nikon":'.to_json($nikon).'}';
#print '{"xmp":'.to_json($xmp).'}';
