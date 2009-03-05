<?php

if( !isset($_GET['h']) )
    return 0;

require_once( "distim.php" );

$dimh = new DistIMHost();
if( $dimh == null ) {
    echo "<div align=\"center\"><br /><br />\n";
    echo "This service is temporarily unavailable.\n";
    echo "</div>\n";
}

$loc = $dimh->download($_GET['h']);
if( $loc != null )
    Header( "Location: $loc" );

echo "<div align=\"center\"><br /><br />\n";
echo "Either the image you requested does not exist or the service is temporarily unavailable.\n";
echo "</div>\n";

?>
