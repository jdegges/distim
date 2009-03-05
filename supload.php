<?php

require_once( "upload_service.php" );

class Supload extends UploadService
{
    private $service = false;
    private $upload_tokens = false;

    function __construct()
    {
        parent::__construct();
        $this->server = "http://s3.supload.com";
        $this->service = "newupload.php";
    }

    public function name() {
        return "supload";
    }

    public function upload( $file )
    {
        $this->filename = $file;

        if( !$this->validate_filename() ) {
            echo "we dont want your kind!";
            return -1;
        }

        $post = array( 'submit' => 'upload images now',
                       'width'  => '1600',
                       'height' => '3200',
                       'thumbnails' => 'thumbnails',
                       'file[]' => "@$file" );
        /*
        if( $this->is_url() )
            $post = array_merge( $post, array('fileurl' => $file) );
        else
            $post = array_merge( $post, array('thefile' => "@$file") );
        */

        $curl = $this->curl;
        curl_setopt( $curl, CURLOPT_URL, "$this->server/$this->service" );
        curl_setopt( $curl, CURLOPT_POST, true );
        curl_setopt( $curl, CURLOPT_HEADER, true );
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $curl, CURLOPT_TIMEOUT, 240 );
        curl_setopt( $curl, CURLOPT_POSTFIELDS, $post );
        curl_setopt( $curl, CURLOPT_HTTPHEADER, array('Expect: ') );
        $this->response = curl_exec( $curl );

        if( $this->response == false ) {
            echo "Error uploading file: " . curl_error($curl);
            return null;
        }

        $this->parse();
        return $this->get_url();
    }

    private function parse()
    {
        if( !$this->http_ok_status() ) {
            $this->response = false;
            return -1;
        }

        $match_str = "Direct Link Codes";
        $match = stristr( $this->response, $match_str );
        $match = eregi_replace( ".*\/free\/", "", $match );
        $match = eregi_replace( "\/view\/.*", "", $match );

        if( !substr($match, '') ) {
            echo "Parse error, update parser for $this->name(\n)";
            $this->response = null;
            return null;
        }

        $this->response = "$this->server/files/default/$match";
        return $this->response;
    }

    private function validate_filename()
    {
        if( !$this->filename )
            return 0;

        $ext = $this->get_extension();
        if( $ext == "jpg" || $ext == "png" || $ext == "gif" || $ext == "bmp" ||
            $ext == "jpeg" || $ext == "tif" || $ext == "tiff" )
        {
            return 1;
        }
        return 0;
    }
}

?>
