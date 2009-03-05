<?php

require_once( "settings.php" );
require_once( "common.php" );
require_once( "upload_service.php" );
require_once( "tinypic.php" );
require_once( "imageshack.php" );
require_once( "xsto.php" );
require_once( "imagecross.php" );
require_once( "supload.php" );
require_once( "freeimagehosting.php" );
require_once( "gdata.php" );

class DistIMHost
{
    private $services = false;
    private $connection = false;

    function __construct()
    {
        try {
            $this->services = array( new ImageShack(),  new TinyPic(),
                                     new XSTO(),        new ImageCross(),
                                     new Supload(),     new FreeImageHosting() );
            $this->connection = new DB();
        } catch (Exception $e) {
            echo $e->getMessage();
            return null;
        }
    }

    public function upload( $file )
    {
        if( $this->connection == null )
            return null;
        return $this->upload_to_best( $file );
    }

    public function upload_to_best( $file )
    {
        $crc = $this->hash( $file );
        if( $crc == null ) {
            return null;
        }

        try{ $row = $this->connection->getUniqueRow( 'name', $crc ); }
        catch (Exception $e){ return null; }
        if( $row != null ) {
            return $crc;
        }

        $bserv = $this->get_best_service();
        try{ $url = $bserv->upload( $file ); }
        catch (Exception $e){ return null; }

        if( strlen($url) <= 2 ) {
            return null;
        }

        $urls = array( 'name'           =>  $crc,
                       $bserv->name()   =>  $url );
//        foreach( $this->services as $service ) {
//            $urls = array_merge( $urls, array($service->name() => $url) );
//        }

        try{ $this->connection->insertRow($urls); }
        catch (Exception $e){ return null; }

        return $crc;
    }

    public function upload_to_all( $file )
    {
        $crc = $this->hash( $file );
        $row = $this->connection->getUniqueRow( 'name', $crc );
        if( $row ) {
            return $crc;
        }

        $urls = array( 'name' => $crc );
        foreach( $this->services as $service )
        {
            $url = $service->upload( $file );
            $urls = array_merge( $urls,
                                 array($service->name() => $url) );
        }
        $this->connection->insertRow($urls);
        return $crc;
    }

    public function download( $hash )
    {
        $row = $this->connection->getUniqueRow( 'name', $hash );
        if( $row ) {
            $bs = $this->get_best_service();
            if( $bs != null ) {
                return $row[$bs->name()];

            }
        }

        return null;
    }

    private function get_best_service()
    {
        $best_ping = 99999;
        $best_service = null;

        $rs = rand( 0, count($this->services)-1 );
        return $this->services[$rs];

        foreach( $this->services as $service )
        {
            $ping = $service->ping();
            if( $ping < $best_ping ) {
                $best_service = $service;
                $best_ping = $ping;
            }
        }

        return $best_service;
    }

    public function update_database()
    {
        if( $this->connection == null )
            return null;

        try{ $block = $this->connection->getUniqueRows( 'mirrored', '0' ); }
        catch (Exception $e){ echo $e->getMessage(); return null; }
        if( $block == null ) {
            return true;
        }

        foreach( $block as $row) {
            $servs = array();
            $keys = array_keys( $row );
            $mirrored = '1';
            $hash = '';
            $url = '';
            for( $i = 0, $s = 0; $i < sizeof($row); $i++ ) {
                if( !strcmp($keys[$i], 'name') ) {
                    $hash = $row[$keys[$i]];
                    continue;
                }
                if( !strcmp($keys[$i], 'mirrored') ) {
                    continue;
                }
                if( !is_url($row[$keys[$i]]) ) {
                    if( isset($this->services[$i-1]) ) {
                        array_push( $servs, $this->services[$i-1] );
                    } else
                        $mirrored = '0';
                }
                if( !strcmp($url,'') && is_url($row[$keys[$i]]) ) {
                    $url = $row[$keys[$i]];
                }
            }

            if( !strcmp($hash, '') )
                die("WTF");

            /* download url & write to tempfile */
            if( sizeof($servs) == 0 ) {
                continue;
            }

            $data = file_get_contents( $url );
            $tempfn = tempnam( ROOT."/var/tmp/", "ack" );
            $ext = get_extension( $url );
            move_uploaded_file( $tempfn, "$tempfn.$ext" );
            $tempfn = "$tempfn.$ext";
            $h = fopen( $tempfn, "w" );
            fwrite( $h, $data );
            fclose( $h );

            /* upload to hosts */
            foreach( $servs as $serv ) {
                try{ $url = $serv->upload( $tempfn ); }
                catch (Exception $e){ unlink($tempfn); continue; }
                if( $url == null ) {
                    unlink( $tempfn );
                    continue;
                }
                $row[$serv->name()] = $url;
            }
            unlink( $tempfn );

            /* mark new row as mirrored */
            if( $mirrored )
                $row['mirrored'] = $mirrored;
            try{ $this->connection->updateRows( 'name', $hash, $row ); }
            catch (Exception $e){ continue; }
        }
        return true;
    }

    private function hash($filename) {
        $str = file_get_contents($filename);
        if( $str == FALSE ) {
            return null;
        }
        return abs(crc32($str));
    }
}

?>
