<?php

/* uploads a file to a server */
abstract class UploadService
{
    protected $server = false;
    protected $response = false;
    protected $filename = false;
    protected $extension = false;
    protected $curl;

    function __construct()
    {
        $this->curl = curl_init();
    }

    function __destruct() {
        curl_close( $this->curl );
    }

    abstract protected function upload( $file );
    abstract protected function name();

    public function ping()
    {
        if(!$this->server)
            return 88888;

        $curl = $this->curl;
        $start_time = microtime( true );
        curl_setopt( $curl, CURLOPT_URL, "$this->server" );
        curl_setopt( $curl, CURLOPT_HEADER, true );
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $curl, CURLOPT_NOBODY, true );
        $this->response = curl_exec( $curl );
        $end_time = microtime( true );

        curl_setopt( $curl, CURLOPT_NOBODY, false );

        if( $this->http_ok_status() ) {
            return $end_time - $start_time;
        }
        return 99999;
    }

    protected function http_ok_status()
    {
        $ok_status = "/HTTP\/\d\.\d 200 OK/";
        $status = preg_match( $ok_status, $this->response );
        return $status;
    }

    public function get_url() {
        if( $this->response ) {
            return $this->response;
        }
        return -1;
    }

    public function login( $username, $password ) {
        return 0;
    }

    public function is_url()
    {
        return ereg( "((https?|ftp|gopher|telnet|file|notes|ms-help):((//)|(\\\\))+[\w\d:#@%/;$()~_?\+-=\\\.&]*)",
                     $this->filename );
    }

    public function get_extension()
    {
        if( !$this->filename )
            return -1;

        if( $this->extension )
            return $this->extension;

        $pattern = "/.*\./";
        $this->extension = preg_replace( $pattern, "", $this->filename );
        return $this->extension;
    }
}

?>
