<?php

require_once( "upload_service.php" );

class FreeImageHosting extends UploadService
{
    private $service = false;
    private $upload_tokens = false;

    function __construct()
    {
        parent::__construct();
        $this->server = "http://www.freeimagehosting.net";
        $this->service = "upload.php";
    }

    public function name() {
        return "freeimagehosting";
    }

    public function upload( $file )
    {
        $this->filename = $file;

        if( !$this->validate_filename() ) {
            echo "we dont want your kind!";
            return -1;
        }

        $post = array( 'submit'     => 'Upload Image',
                       'attached'   => "@$file" );

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

        $match_str = "To insert this image in a forum post";
        $match = stristr( $this->response, $match_str );
        $match = eregi_replace( ".*\[img\]|\[\/img\].*", "", $match );

        if( !substr($match, '') ) {
            echo "Parse error, update parser for $this->name(\n)";
            $this->response = null;
            return null;
        }

        $this->response = $match;
        return $match;
    }

    private function validate_filename()
    {
        if( !$this->filename || $this->is_url() )
            return 0;

        $ext = $this->get_extension();
        if( $ext == "jpg" || $ext == "png" || $ext == "gif" || $ext == "bmp" )
        {
            return 1;
        }
        return 0;
    }
}

?>
