<?php

require_once( "upload_service.php" );

class ImageCross extends UploadService
{
    private $service = false;
    private $upload_tokens = false;

    function __construct()
    {
        parent::__construct();
        $server = rand(1, 7);
        $this->server = "http://hosting0$server.imagecross.com";
        $this->service = "basicg.php";
    }

    public function name() {
        return "imagecross";
    }

    public function upload( $file )
    {
        $this->filename = $file;

        if( !$this->validate_filename() ) {
            echo "we dont want your kind!";
            return -1;
        }

        $post = array( 'userfile' => "@$file" );

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
            echo "Error connecting to host:" . curl_error( $curl );
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

        $match_str = "<tr><td><h6>Html:</h6</td><td><input type=\"text\" name=\"thetext\" onClick=\"this.focus();this.select()\" size=\"60\" value='<a href=\"http://www.imagecross.com/\"><img src=\"";
        $match = stristr( $this->response, $match_str );
        $match = substr($match, strlen($match_str),200);
        $match = eregi_replace( "\"></a><br><a.*", "", $match );

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
        if( $ext == "jpg" || $ext == "png" || $ext == "gif" || $ext == "bmp" ||
            $ext == "jpeg" || $ext == "tif" || $ext == "tiff" )
        {
            return 1;
        }
        return 0;
    }
}

?>
