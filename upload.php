<?php

if( !isset($_FILES['f']) || !isset($_POST['v']) )
    return;

if( !strcmp($_POST['v'], 'no') )
    $v = false;
else if( !strcmp($_POST['v'], 'yes') )
    $v = true;
else
    return;

require_once( "distim.php" );

$dimh = new DistIMHost();
if( $dimh == null ) {
    if( $v ) echo "This service is temporarily unavailable, please try again in a few minutes.\n";
    return 0;
}

$ext = preg_replace( "/.*\./", "", $_FILES['f']['name'] );
$nn = ROOT . '/var' . $_FILES['f']['tmp_name'].".$ext";
move_uploaded_file( $_FILES['f']['tmp_name'], $nn );
$key = $dimh->upload($nn);
unlink($nn);

if( $v ) echo "<div align=\"center\"><br /><br />\n";
if( $key != null ) {
    if( $v ) echo "<a href=\"download.php?h=$key\" target=\"_blank\">http://" . $_SERVER['SERVER_NAME'] . "/download.php?h=$key</a>\n";
    else echo "http://" . $_SERVER['SERVER_NAME'] . "/download.php?h=$key";
} else {
    if( $v ) echo "This service is temporarily unavailable, please try again in a few minutes.\n";
}

if( $v ) echo "</div>\n";

?>
