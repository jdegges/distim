<?php

require_once( "upload_service.php" );

class TinyPic extends UploadService
{
    private $service = "upload.php";
    private $upload_tokens = false;

    function __construct()
    {
        parent::__construct();
        $server = rand( 1, 5 );
        $this->server = "http://s$server.tinypic.com";
        $this->service = "upload.php";
    }

    public function name() {
        return "tinypic";
    }

    public function upload( $file )
    {
        $this->filename = $file;

        if( !$this->validate_filename() ) {
            echo "we dont want your kind!";
            return -1;
        }

        $this->get_upload_tokens();

        $curl = $this->curl;

        curl_setopt( $curl, CURLOPT_URL, "$this->server/$this->service" );
        curl_setopt( $curl, CURLOPT_POST, true );
        curl_setopt( $curl, CURLOPT_HEADER, true );
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $curl, CURLOPT_TIMEOUT, 240 );
        curl_setopt( $curl,
                     CURLOPT_POSTFIELDS,
                     array_merge( $this->upload_tokens,
                                  array( 'domain_lang'        =>  'en',
                                         'action'             =>  'upload',
                                         'MAX_FILE_SIZE'      =>  '500000000',
                                         'the_file'           =>  "@$file",
                                         'file_type'          =>  'image',
                                         'video-settings'     =>  'hd' )));
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

        $match_str = "<input type=\"hidden\" name=\"pic\" value=\"";
        $match = stristr( $this->response, $match_str );
        $match = substr( $match, 0, 50 );
        $match = eregi_replace( "$match_str|\" />.*", "", $match );

        if( !substr($match, '') ) {
            echo "Parse error, update parser for $this->name(\n)";
            $this->response = null;
            return null;
        }

        $this->response = "$this->server/$match." . $this->get_extension();
        return $this->response;
    }

    private function get_upload_tokens()
    {
        $curl = $this->curl;
        curl_setopt( $curl, CURLOPT_URL, "http://tinypic.com/index.php" );
        curl_setopt( $curl, CURLOPT_HEADER, true );
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
        $response = curl_exec( $curl );

        $match_str = "<form action=";
        $server = $response;
        $server = eregi_replace( ".*<form action=\"http://s|\.tinypic.com/upload.php\" method=\"post\".*", "", $server );
        $this->server = "http://s$server.tinypic.com";

        $match_str = "<input type=\"hidden\" name=\"UPLOAD_IDENTIFIER\" id=\"uid\" value=\"";
        $uid = stristr( $response, $match_str );
        $uid = substr( $uid, 0, 200 );
        $uid = eregi_replace( "$match_str|\" />.*", "", $uid );

        $match_str = "<input type=\"hidden\" name=\"upk\" value=\"";
        $upk = stristr( $response, $match_str );
        $upk = substr( $upk, 0, 200 );
        $upk = eregi_replace( "$match_str|\" />.*", "", $upk );

        $this->upload_tokens = array( "UPLOAD_IDENTIFIER" => $uid,
                                      "upk"               => $upk );
        return 1;
    }

    private function validate_filename()
    {
        if( !$this->filename )
            return 0;

        $ext = $this->get_extension();
        if( $ext == "jpg" || $ext == "png" || $ext == "gif" || $ext == "bmp" )
            return 1;
        return 0;
    }

    public function ping() {
        $real_server = $this->server;
        $this->server .= "/favicon.ico";

        $ping = parent::ping();

        $this->server = $real_server;

        return $ping;
    }
}

?>
