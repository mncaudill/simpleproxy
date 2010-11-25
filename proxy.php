<?php 

    # SimpleProxy
    # Author: Nolan Caudill
    # Date: 2010/11/24

    $host_regex = "#^(www\.)?nolancaudill\.com$#";

    # If you'd like to allow/disallow different things,
    # this is where you'd put it.
    function print_header($resource, $header) {
        if(preg_match('#^content-type:#i', $header)) { 
            if(!preg_match('#^content-type: image/#i', $header)) {
                print "Invalid type.";
                exit;
            }
        }

        $length = strlen($header);
        header($header);
        return $length;
    }

    $url = $_GET['url'];

    if(!$url) {
        print "What are you doing here?";
        exit;
    }

    # If we are trying to proxy ourselves, just stop it.
    $url_parts = parse_url($url);
    if(preg_match($host_regex, $url_parts['host'])) {
        print "Recursion is sometimes not a good thing...<br/>";
        exit;
    }

    # If the referrer is not what we are expecting, just redirect to URL.
    $referrer_parts = parse_url($_SERVER['HTTP_REFERER']);
    if(!preg_match($host_regex, $referrer_parts['host'])) {
        header('Location: ' . $url);
        exit;
    }

    # Curling: Canada's pasttime
    # This basically mirrors the headers and then fetches 
    # the content. The idea is to let the origin site sets
    # its own cache expiration stuff, but HTTPS screws most of
    # that up anyway. Oh, well.

    # I'm using this streaming object so that the file won't be held
    # all in memory at the same time.
    class CurlStream
    {
        function stream_open($path, $mode, $options, &$opened_path) {
            return true;
        }

        function stream_write($data) {
            print $data;
            return strlen($data);
        }
    }

    stream_wrapper_register("nolansproxy", "CurlStream");
    $fp = fopen("nolansproxy://doesntmatter", "r+");

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADERFUNCTION, 'print_header');
    curl_exec($ch);
    curl_close($ch);

    fclose($fp);

