use strict;
use warnings;
use JSON;
use File::Basename;
use lib dirname(__FILE__);

my $module = $ARGV[0];
my $func = $ARGV[1];
my $args_raw = do { local $/; <STDIN> };
my $args = $args_raw ? decode_json($args_raw) : [];

eval "require $module";
if ($@) { die $@; }

my $result;
if (ref($args) eq "ARRAY") {
    no strict "refs";
    $result = &{"${module}::${func}"}(@$args);
} else {
    no strict "refs";
    $result = &{"${module}::${func}"}($args);
}

print encode_json($result);