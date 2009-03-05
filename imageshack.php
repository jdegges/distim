<?php

require_once( "upload_service.php" );

class ImageShack extends UploadService
{
    private $file_service = false;
    private $url_service = false;

    function __construct()
    {
        parent::__construct();
        $this->server = "http://www.imageshack.us";
        $this->file_service = "";
        $this->url_service = "transload.php";
    }

    public function name() {
        return "imageshack";
    }

    public function upload( $file )
    {
        $this->filename = $file;

        if( !$this->validate_filename() ) {
            echo "we dont want your kind!";
            return -1;
        }

        $post = array( 'xml'            =>  'yes',
                       'uploadtype'     =>  'on',
                       'email'          =>  '',
                       'refer'          =>  '',
                       'brand'          =>  '',
                       'MAX_FILE_SIZE'  =>  '13145728',
                       'rembar'         =>  '1' );
        $url = "";
        if( $this->is_url() ) {
            $this->server = "http://www.imageshack.us";
            $url = "$this->server/$this->url_service";
            $post = array_merge( $post, array('url' => $file) );
        } else {
            $this->server = "http://load.imageshack.us";
            $url = "$this->server$this->file_service";
            $post = array_merge( $post, array('fileupload' => "@$file", 'url' => 'paste image url here' ) );
        }

        $curl = $this->curl;
        curl_setopt( $curl, CURLOPT_URL, $url );
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

        $match_str = ".*<image_link>|</image_link>.*";
        $match = eregi_replace( $match_str , "", $this->response );

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
        if( !$this->filename )
            return 0;

        $ext = $this->get_extension();
        if( $ext == 'jpg'  || $ext == 'jpeg' || $ext == 'png' ||
            $ext == 'gif'  || $ext == 'bmp'  || $ext == 'tif' ||
            $ext == 'tiff' || $ext == 'pdf' )
        {
            return 1;
        }
        return 0;
    }
}

?>
