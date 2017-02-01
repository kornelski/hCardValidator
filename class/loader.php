<?php

class LoaderException extends Exception
{
    private $url;

    function getURL(){return $this->url;}

	/**
	 * i18nable messages must pass variable bits separately
	 * @return array(i18n key, array(vars))
	 */
    function getMessageArgs(){return [$this->msg_class,$this->args];}

    function __construct($default_msg, $msg_class, array $args = [],$url = NULL)
    {
        parent::__construct($default_msg);
        $this->url = $url;
        $this->args = $args;
        $this->msg_class = $msg_class;
    }
}

class LoaderURL
{
    function __construct($url)
    {
        list($hostname,$ip,$path) = $this->paranoidURLSanitization($url);

        $this->hostname = $hostname;
        $this->ip = $ip;
        $this->path = $path;
    }

    private $hostname, $ip, $path;

    function __toString(){return $this->getURL();}

    function getURL()
    {
        return 'http://'.$this->hostname.$this->path;
    }

    function getIPURL()
    {
        return 'http://'.$this->ip.$this->path;
    }

    function getHost()
    {
        return $this->hostname;
    }

    function getPath()
    {
        return $this->path;
    }

	/**
	 * @return array($hostname,$ip,$path)
	 */
    private function paranoidURLSanitization($url)
    {
        $standard_excuse = "URL not allowed\nPardon me being paranoid about security, but this URL doesn't look innocent enough (HTTP with hostname and without authentication or ports please).";

        $url = trim($url);
        $url = preg_replace_callback('/([\x00-\x20\x7f-\xff]+)/',function($m){return rawurlencode($m[0]);},$url);

        if (!preg_match('/https?:\/\//',$url)) $url = 'http://'.$url;

		// PHP's built-in filter is rather weak, so don't stop there
        $url = filter_var($url,FILTER_SANITIZE_URL);
        $url = filter_var($url,FILTER_VALIDATE_URL,FILTER_FLAG_HOST_REQUIRED|FILTER_FLAG_SCHEME_REQUIRED);

        if (!preg_match('/^https?:\/\/((?:[a-z0-9][a-z0-9-]*\.)+[a-z]{2,6})\.?(\/[^#]*)?(?:#.*)?$/i',$url,$m))
        {
            throw new LoaderException($standard_excuse,"invalid_url",[],$url);
        }

        $hostname = strtolower($m[1]);
        $path = isset($m[2]) ? $m[2] : '/';

        if (preg_match('/\.sblam\.com$|api\.geekhood.net$|\.local$/',$hostname))
        {
            throw new LoaderException($standard_excuse,"invalid_url",[],$url);
        }

        $ip = gethostbyname($hostname);
        if (!$ip || $ip == $m[1])
        {
            throw new LoaderException("Can't find hostname.","dns_error",[$hostname],'http://'.$hostname.$path);
        }

        if (!($ip = filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4|FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE)))
        {
            throw new LoaderException($standard_excuse,"invalid_url",[],'http://'.$hostname.$path);
        }

        return [$hostname,$ip,$path];
    }
}

class Loader
{
	/**
	 * get URL content from teh internets
	 *
	 * @return array($content,$response_headers,$url);
	 */
    function fetchURL($url)
    {
        $lurl = new LoaderURL($url);

		// send XFF header
		$xff = $_SERVER['REMOTE_ADDR'];

		// if there's existing one, append
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && preg_match('/^[ ,0-9.]+$/',$_SERVER['HTTP_X_FORWARDED_FOR']))
        {
            $xff .= ', '.trim($_SERVER['HTTP_X_FORWARDED_FOR']);
        }

        $headers = [
            'Accept' => 'application/xhtml+xml, application/xml;q=0.9, text/html;q=0.1', // Apache with Multiviews doesn't seem to like it. Poor baby.
            'User-Agent' => 'hCardValidator/1 PHP/5 (http://hcard.geekhood.net)',
            'Referer' => 'http://'.$_SERVER['HTTP_HOST'].'/',
            'X-Forwarded-For' => $xff,
        ];

        return $this->performURLFetch($lurl,$headers);
    }

    /**
     * HTTP response parser
     */
    private function getHeaders($wrapper_data)
    {
        $response_headers = [];

        if ($wrapper_data && is_array($wrapper_data)) foreach($wrapper_data as $h)
        {
            if (preg_match('/^([^\s]+)\s*:\s*(.*)$/i',$h,$m))
            {
                $response_headers[strtolower($m[1])] = $m[2];
            }
            else if (preg_match('/^HTTP\/1\..\s+(\d+)/',$h,$m))
            {
                $response_headers['status'] = $m[1];
            }
        }

        return $response_headers;
    }

    private function throwError(LoaderURL $lurl,array $response_headers)
    {
        if (empty($response_headers['status']))
        {
            throw new LoaderException("Server may be down.","request_failed",[$lurl->getHost()],$lurl->getURL());
        }

        if ($response_headers['status'] == 404 || $response_headers['status'] == 410)
        {
            throw new LoaderException("File not found","file_not_found",[$lurl->getPath(),$lurl->getHost()],$lurl->getURL());
        }

        if ($response_headers['status'] >= 300 && $response_headers['status'] < 400)
        {
            throw new LoaderException("Invalid redirect","invalid_redirect",[],$lurl->getURL());
        }

        throw new LoaderException("HTTP error","http_error",[$response_headers['status']],$lurl->getURL());
    }

    private function performURLFetch(LoaderURL $lurl,array $request_headers, $redirects_allowed = 5)
    {
        global $http_response_header; // PHP is crap. This is automagic variable that's used instead of something that makes sense to sober people.

        $headers_string = '';
        $request_headers['Host'] = $lurl->getHost();
        foreach($request_headers as $k => $v)
        {
            $headers_string .= "$k: ".preg_replace('/\s+/',' ',$v)."\r\n";
        }

        $ctx = stream_context_create([
            'http'=>[
                'method'=>'GET',
                'max_redirects'=>0, // I want to handle redirects myself
                'timeout'=>10,
                'header'=>$headers_string,
        ]]);

        $http_response_header = NULL; // PHP is crap²
        $fp = @fopen($lurl->getIPURL(),"rb",false,$ctx);
        if (!$fp)
        {
            $response_headers = $this->getHeaders($http_response_header);
        }
        else
        {
            $metadata = stream_get_meta_data($fp);
            if (empty($metadata['wrapper_data'])) throw new Exception("PHP Sucks");
            $response_headers = $this->getHeaders($metadata['wrapper_data']);
        }

        if (empty($response_headers['status']) || $response_headers['status'] < 100 || $response_headers['status'] >= 400)
        {
            $this->throwError($lurl,$response_headers);
        }
        elseif ($response_headers['status'] >= 300 && $response_headers['status'] < 400)
        {
            if (empty($response_headers['location']) || $redirects_allowed <= 0)
            {
                $this->throwError($lurl,$response_headers);
            }
            $newurl = new LoaderURL($response_headers['location']);
            return $this->performURLFetch($newurl, $request_headers, $redirects_allowed - 1);
        }
        else
        {
            $maxlen = 800000;
            $file = stream_get_contents($fp,$maxlen);
            if (strlen($file) >= $maxlen)
            {
                throw new LoaderException("File larger than maximum size","maximum_file_size",[round($maxlen/1000)],$url);
            }
            return [$file,$response_headers,$lurl->getURL()];
        }
    }

}
