<?php

require_once( "upload_service.php" );

class XSTO extends UploadService
{
    private $service = false;
    private $upload_tokens = false;

    function __construct()
    {
        parent::__construct();
        $this->server = "http://xs.to";
        $this->service = "upload.php";
    }

    public function name() {
        return "xsto";
    }

    public function upload( $file )
    {
        $this->filename = $file;

        if( !$this->validate_filename() ) {
            echo "we dont want your kind!";
            return -1;
        }

        $post = array( 'action' => 'doupload', 'prtype' => '1' );
        if( $this->is_url() )
            $post = array_merge( $post, array('fileurl' => $file) );
        else
            $post = array_merge( $post, array('thefile' => "@$file") );

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
        $this->follow_response();

        if( !$this->http_ok_status() ) {
            $this->response = false;
            return -1;
        }

        $match_str = "Direct link:";
        $match = stristr( $this->response, $match_str );
        $match = stristr( $match, "value" );
        $match = substr($match,7,100);
        $match = eregi_replace( "\">.*", "", $match );

        if( !substr($match, '') ) {
            echo "Parse error, update parser for $this->name(\n)";
            $this->response = null;
            return null;
        }

        $this->response = $match;
        return $match;
    }

    private function follow_response()
    {
        $match_str = "Location: ";
        $url = stristr( $this->response, $match_str );
        $url = substr( $url, 0, 200 );
        $url = eregi_replace( "$match_str|\r\n.*", "", $url );

        $curl = $this->curl;
        curl_setopt( $curl, CURLOPT_URL, $url );
        curl_setopt( $curl, CURLOPT_HEADER, true );
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
        $this->response = curl_exec( $curl );
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
