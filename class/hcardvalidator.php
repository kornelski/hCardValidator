<?php

class hCardException extends Exception {}

class hCardValidator
{
    /**
     * how sad this is neccessary
     */
    private function sniffXHTMLLikeTagsoup($fileSource)
    {
        if (preg_match('/^(?:\xEF\xBB\xBF)?(<\?xml(?:[^"\']*|"[^"]*"|\'[^\']*\')*\?>\s*)*\s*(<!DOCTYPE\s+html\s+PUBLIC\s+"-\/\/W3C\/\/DTD (?:XHTML 1.0 Strict|XHTML 1.0 Transitional|XHTML 1.1)\/\/EN"|<html [^>]*xmlns=[\'"]http:\/\/www.w3.org\/1999\/xhtml[\'"])?/si',$fileSource,$m))
        {
            return !empty($m[1]) || !empty($m[2]);
        }
        return false;
    }

    private function makeSureHTMLDeclaresCharset($fileSource,$charset, $xhtml = false)
    {
        if (preg_match("/<head.*?<meta\s[^>]+content[^>]+\/[^>]+charset[^>a-z]+".preg_quote($charset)."/is",$fileSource))
        {
            return $fileSource;
        }
        
        $fileSource = preg_replace("/<meta\s[^>]+?content\s*=\s*[^>\/]+?\/[^>]+?charset\s*=\s*[^>]*>/is",'<!-- meta removed by hCard Validator -->',$fileSource);
        
        $headstart = (int)stripos($fileSource,'<head');
        $headend = (int)stripos($fileSource,'</head',$headstart);
        if (!$headend) $headend = (int)stripos($fileSource,'<body',$headstart);

        return substr($fileSource,0,$headend).'<meta http-equiv="Content-Type" content="text/html;charset='.myhtmlspecialchars($charset).'"'.($xhtml?'/':'').'><!-- meta inserted by hCard Validator -->'.substr($fileSource,$headend);
    }

    function validateURL($url)
    {
        $loader = new Loader();

        try
        {
            list($fileSource,$headers,$url) = $loader->fetchURL($url);
        }
        catch(LoaderException $e)
        {
            list($msgclass,$args) = $e->getMessageArgs();
            
            $result = new ValidationResult($e->getURL());
            $result->add("error",$msgclass,myhtmlspecialchars($e->getMessage()),$args);
            $result->url = $e->getURL();
            return $result;
        }

        $result = new ValidationResult($url);
	    $result->url = $url;

        return $this->validateSource($result, $url, $fileSource, isset($headers['content-type'])? ($headers['content-type']) : NULL);
    }
    
    function validateUpload(array $upload)
    {
        $result = new ValidationResult($upload['name']);
        
        if ($upload['error'])
        {
            $result->add("error","upload_failed","Upload failed",array());
            return $result;
        }
                
        return $this->validateSource($result, NULL, file_get_contents($upload['tmp_name']), $upload['type']);
    }
    
    private function validateSource(ValidationResult $result, $url, $fileSource, $contentType)
    {     
        if (!strlen($fileSource))
        {
            $result->add("error","empty_file","The file is empty!",array());
            return $result;
        }
        
           
  	    $type = 'text'; $subtype = 'xml'; $charset = NULL;
  	    if (!empty($contentType))
  	    {
  	        if (preg_match('/^\s*([^\/;,]+)\/([^\/;,]+)(?:\s*charset\s*=\s*[\'"]?([^\'",;]+))?/',$contentType,$m))
  	        {
	            $type = $m[1]; $subtype = $m[2];
	            if (!empty($m[3])) $charset = $m[3];
            }
        }
        
        if (!$charset)
        {
            if (preg_match('/<meta[^>]+charset\s*=\s*[\'"]?\s*([a-z0-9-]+)/i',$fileSource,$m))
            {
                $charset = $m[1];
            }
            else
            {   
                //$result->add("warn","missing_charset","No charset declaration in HTTP headers"); 
                $charset = 'UTF-8';
            }
        }
        
        $charset = strtoupper($charset);
        
        if ($charset == 'UTF8') $charset = 'UTF-8';

        $looks_like_xhtml = $this->sniffXHTMLLikeTagsoup($fileSource);
        
        $sent_as_xhtml = ($subtype == 'xml' || preg_match('/\+xml$/',$subtype));
        
        if (!$sent_as_xhtml && !$looks_like_xhtml && $subtype !== 'html')
        {
            $result->add("error","wrong_mime","Unsupported type\nFile was sent as: <code>%s</code> which doesn't look like a supported type.",array("$type/$subtype"));
            return $result;
        }

        $bozo = false;
        if (!empty($_GET['im_a']) && $_GET['im_a']=='bozo' && ($sent_as_xhtml || $looks_like_xhtml))
        {
            $bozo = true;
        }

	    $result->source = cleanstring($fileSource, $charset);

        $doc = NULL;
        if ($looks_like_xhtml || $sent_as_xhtml)
        {
            /// PHP's libxml apparently fails to support anything but western encodings
            if ($charset != 'UTF-8' && $charset != 'ISO-8859-1')
            {
                $fileSource = iconv($charset,'UTF-8//IGNORE',$fileSource);
                $this->makeSureHTMLDeclaresCharset($fileSource,$charset);
                $charset = 'UTF-8';                
            }
            
            $doc = new DOMDocument('1.0',$charset);
             $doc->encoding = $charset;
            if ($url) $doc->documentURI = $url;
            
            $doc->resolveExternals = $looks_like_xhtml; // resolve only known doctypes
            $this->clearLibxmlErrors();                       
            
            $doc->loadXML($fileSource);
            if (!$bozo) 
            {
                if (!$doc->documentElement) 
                {
                    if ($url)
                    {
                        $result->add("error","ill_formed_bozo","Document is not well-formed <abbr>XML</abbr>\nFailed to load XML. Can't validate tagsoup. <a href='%s'>Re-parse as tag soup</a>.",array("?im_a=bozo&url=".rawurlencode($doc->documentURI)."#result"),"http://hsivonen.iki.fi/producing-xml/");
                    }
                    else
                    {
                        $result->add("error","ill_formed","Document is not well-formed <abbr>XML</abbr>\nFailed to load XML.",array(),"http://hsivonen.iki.fi/producing-xml/");                         
                    }
                    $this->addLibxmlErrors($result,$doc);
                    return $result;
                }
                $this->addLibxmlErrors($result,$doc);
            }
        }

        if ($looks_like_xhtml && !$sent_as_xhtml && ($bozo || ($doc && !$doc->documentElement)))
        {
            if ($bozo) $result->add("error","bozo_mode","Parsing in tagsoup mode\nMisinterpreting everything as <abbr>HTML</abbr> tagsoup to deal with unparseable “<abbr>XHTML</abbr>”.",array(),"http://hsivonen.iki.fi/producing-xml/");

            $result->add("warn","x_tagsoup","Sending <abbr>XHTML</abbr> as <abbr>HTML</abbr> Considered Harmful\nReceived broken <abbr>XHTML</abbr> sent as <code>text/<strong>html</strong></code>.",array(),"http://hixie.ch/advocacy/xhtml");
        }

        if ($bozo || (!$looks_like_xhtml && !$sent_as_xhtml))
        {
            $doc = new DOMDocument('1.0',$charset);
            $doc->encoding = $charset;
            if ($url) $doc->documentURI = $url;
            
            $doc->resolveExternals = false;

            $fileSource = $this->makeSureHTMLDeclaresCharset($fileSource,$charset,true);

            $this->clearLibxmlErrors();
            $doc->loadHTML($fileSource);
            $doc = $this->processStylesheet($doc,'html2xhtml.xslt');
            $this->addLibxmlErrors($result,$doc);
        }

        $result = $this->validateDOM($result, $doc);
	    return $result;
    }

    function validateFile($filePath)
    {
        $result = new ValidationResult(basename($filePath));
        return $this->validateSource($result, NULL, file_get_contents($filePath), 'application/xhtml+xml;charset=UTF-8');
    }

    function validateXHTMLFragment($source)
    {        
        $result = new ValidationResult('XHTML fragment');
        $doc = $this->loadFragment($source, $result);
        return $this->validateDOM($result, $doc);
    }

    private function validateDOM(ValidationResult $result, DOMDocument $doc)
    {
        $doc = self::processStylesheet($doc,'include.xslt');
        $result->addFromDoc($doc);

        $doc->preserveWhiteSpace = false;
        $doc->formatOutput   = true;
        $result->parsedSource = $doc->documentElement ? $doc->saveXML($doc->documentElement) : $doc->saveXML();

        $doc = self::processStylesheet($doc,'hcard.xslt');
        
//        $doc->preserveWhiteSpace = false;
//        $doc->formatOutput   = true;
//        echo '<pre>'.htmlspecialchars($doc->saveXML($doc->documentElement)).'</pre>';
        $result->addFromDoc($doc);

        $uids = array();

        foreach($result->vcards as $vcard)
        {
            $this->validateVCard($vcard);
            
            foreach($vcard->query('uid/value') as $uid)
            {
                if (!isset($uids[$uid])) $uids[$uid] = 1; else $uids[$uid]++;
            }
            
            if (!$vcard->result->isValid) $result->isValid = false;
        }
        
        foreach($uids as $uid => $count)
        {
            if ($count > 1) $result->add("warn","repeated_uid","Uid <samp>%s</samp> used more than once\nIt's supposed to be <em>globally unique</em> identifier corresponding to the individual or resource",array($uid));
        }

        return $result;
    }

    private function checkAndRemoveEmpty(array &$data, vCard $vcard, $parent = NULL)
    {
        foreach($data as $prop => &$values)
        {
            foreach($values as $idx => &$v)
            {
                if (!is_array($v))
                {
                    if ('' === trim($v))
                    {
                        $warn_or_err = "warn";
                        
                        if ($prop == 'adr' || ($prop == 'value' && $parent && ($parent == 'tel' || $parent == 'email')))
                        {
                            $warn_or_err = "error";
                        }
                        
                        if ($parent) $vcard->result->add($warn_or_err,"empty_subprop","<code>%s</code> property of <code>%s</code> is empty",array($prop,$parent));
                        else $vcard->result->add($warn_or_err,"empty_prop","<code>%s</code> property is empty",array($prop));
                        
                        unset($values[$idx]);
                    }
                }
                else
                {
                    $this->checkAndRemoveEmpty($v, $vcard, $prop);
                    if (!count($v))
                    {
                        unset($values[$idx]);
                    }
                }
            }
            if (!count($values))
            {
                unset($data[$prop]);
            }
        }
    }

    private function validateVCardNames(vCard $vcard)
    {
        $needs_n = !$vcard->flag('had_n');
       
        
        if (!count($vcard->fn))
        {
            $vcard->result->add("error","no_fn","hCard must have <code>fn</code> property",array(),"http://microformats.org/wiki/hcard-authoring#The_Importance_of_Names");
        }
        else if (in_array($vcard->fn[0],$vcard->allOrgNames()))
        {
            $vcard->result->add("info","company","This hCard describes organization or company",array(),"http://microformats.org/wiki/hcard#Organization_Contact_Info");
                        
            $needs_n = false;
            if (count($vcard->n)) // ignore had_n, because empty n is allowed
            {
                $vcard->result->add("error","org_has_n","Company/organization hCard has <code>n</code> property",array(),"http://microformats.org/wiki/hcard#Organization_Contact_Info");
            }
        }
        else 
        {
            if ($vcard->flag('org_in_fn'))
            {
                $vcard->result->add("warn","org_fn_ignored","<code>org+fn</code> used, but names differ\nAlthough card has <code>org</code> property nested in <code>fn</code>, the <code>organization-name</code> is not identical to <code>fn</code> and card may not be interpreted as company's card.",array(),"http://microformats.org/wiki/hcard#Organization_Contact_Info");
            }
            
            if (!count($vcard->n) && !$vcard->flag('had_n'))
            {
                if (preg_match('/^(\S+?)(,?)\s+(\S+)$/u',$vcard->fn[0],$m))
                {
                    if ($m[2] || mb_strlen(rtrim($m[3],'.'))==1)
                    {
                        $fname = $m[1];
                        $gname = $m[3];
                    }
                    else
                    {
                        $fname = $m[3];
                        $gname = $m[1];
                    }

                    $vcard->append('n',array(
                            'given-name'=> array($gname),
                            'family-name'=> array($fname),
                        ));
                }
                elseif (preg_match('/^\S+$/',$vcard->fn[0]))
                {
                    $needs_n = false;
                    if (!in_array($vcard->fn[0], $vcard->nickname))
                    {
                        $vcard->result->add("info","implied_nickname","Implied nickname from <code>fn</code>",array(),"http://microformats.org/wiki/hcard#Implied_.22nickname.22_Optimization");
                        $vcard->append('nickname',$vcard->fn[0]);
                    }
                }
            }
        }


        if (!count($vcard->n) && $needs_n)
        {
            $vcard->result->add("error","no_n","hCard must have <code>n</code> property\nIt can be either set explicitly or implied from one- or two-word <code>fn</code>",array(),"http://microformats.org/wiki/hcard#Implied_.22n.22_Optimization");
        }

        if (count($vcard->fn) > 1)
        {
            $vcard->result->add("error","multi_fn","hCard must have <em>only one</em> <code>fn</code> property",array(),"http://microformats.org/wiki/hcard-singular-properties#fn");
        }

        if (count($vcard->n) > 1)
        {
            $vcard->result->add("error","multi_n","hCard must have <em>only one</em> <code>n</code> property",array(),"http://microformats.org/wiki/hcard-singular-properties#n");
        }
        
        foreach($vcard->query('n/given-name') as $name) $this->lookForHonorifics($vcard,'given-name',$name);
        foreach($vcard->query('n/family-name') as $name) $this->lookForHonorifics($vcard,'family-name',$name);
        foreach($vcard->query('n/additional-name') as $name) $this->lookForHonorifics($vcard,'additional-name',$name);        
    }

    private function validateVCardGEO(vCard $vcard)
    {
        if (count($vcard->geo) > 1) $vcard->result->add("error","mutli_geo","Multiple geo values",array(),"http://microformats.org/wiki/hcard-singular-properties#Physical_Properties");
        
        if (isset($vcard->data['geo'])) foreach($vcard->data['geo'] as &$geo)
        {
            if (isset($geo['value']))
            {
                $latlon = explode(';',$geo['value'][0]);
                unset($geo['value']);
                
                if (count($latlon) == 2)
                {
                    if (!isset($geo['latitude'])) $geo['latitude'] = array();
                    if (!isset($geo['longitude'])) $geo['longitude'] = array();
                                
                    $geo['latitude'][] = $latlon[0];
                    $geo['longitude'][] = $latlon[1];
                }
                else
                {
                    $vcard->result->add("error","geo_abbr_syntax","Value \"%s\" of geo element contains value that is not separated by semicolon",array($latlon[0]),"http://microformats.org/wiki/hcard-faq#How_does_GEO_work_with_ABBR");                    
                }
            }
        }
        unset($geo);

        foreach($vcard->query("geo/latitude") as $l)
        {
            $this->validateLatLon($l,'latitude',$vcard);
        }
        foreach($vcard->query("geo/longitude") as $l)
        {
            $this->validateLatLon($l,'longitude',$vcard);
        }
    }
    
    private function validateLatLon($value, $propname, vCard $vcard)
    {
        if (preg_match('/^[+-]?[0-9]+(\.[0-9]*)?$/',trim($value),$m))
        {            
            $angle = (float)$value;
            if (($propname == 'latitude' && ($angle < -90 || $angle > 90)) || ($propname == 'longitude' && ($angle < -180 || $angle > 360))) 
            {
                $vcard->result->add("error","geo_value_range","Value %s of %s out of range",array($propname,$value));
            }            
            else if (empty($m[1]) || strlen($m[1])<6) // this is 5 digits. allow less, to avoid bitching unneccessarily.
            {
                $vcard->result->add("warn","geo_precision","<code>%s</code> should be specified to 6 decimal places\nThis will allow for granularity
                   within a meter of the geographical position.",array($propname));
            }
            
        }
        else
        {
            $vcard->result->add("error","geo_value","Syntax error %s in %s",array($propname,$value));
        }

    }
    
    private function checkemaildomain($domain)
    {
        if (0)//function_exists('apc_fetch') && ($res = apc_fetch('mx_'.$domain)))
        {
            return 'y'==$res;
        }
        
        $lookup = gethostbyname($domain);
        $res = ($lookup && $lookup != $domain) || getmxrr($domain,$whatever);
        
        if (0)//function_exists('apc_store'))
        {
            apc_store('mx_'.$domain, $res?'y':'n', 3600);
        }
        
        return $res;
    }
    
    const PROTOCOL_REGEX = '/^([a-z][a-z0-9+-]+):/i';
    
    private function validateVCardURLs(vCard $vcard)
    {   
        if (isset($vcard->data['email'])) foreach($vcard->data['email'] as &$email)
        {            
            if (isset($email['type'])) $this->checkEmailTypes($vcard,$email['type']);
            
            if (isset($email['value'])) foreach($email['value'] as &$val)
            {
                $val = trim($val);
                if (preg_match(self::PROTOCOL_REGEX,strtolower($val),$m))
                {                
                    if ($m[1]=='http' || $m[1]=='https')
                    {
                        $vcard->result->add("error","email_http","e-mail property uses <abbr>HTTP</abbr> protocol",array(), "http://microformats.org/wiki/hcard-faq#X2V_does_not_convert_email_with_name_as_plain_text");
                    }
                    else if ($m[1] != 'mailto' || !preg_match('/^mailto:\s*([^\?]*)/i',$val,$emailvalonly))
                    {
                        $vcard->result->add("error","email_non_mailto","e-mail property uses non-mail '%s' protocol",array($m[1]), "http://microformats.org/wiki/hcard-faq#X2V_does_not_convert_email_with_name_as_plain_text");
                    }
                    else 
                    {   
                        if ($emailvalonly[1]) $val = rawurldecode($emailvalonly[1]); // url-decode e-mail part. regexp above got rid of protocol and query-string (if any)
                        
                        if (preg_match('/^[^\s]+@((?:[a-z0-9\x80-\xFF][a-z0-9\x80-\xFF-]*\.)+[a-z]{2,6})\s*$/i',$val,$m)) 
                        {
                            if (!$this->checkemaildomain($m[1]))
                            {
                                $vcard->result->add("error","email_domain","e-mail's domain %s lookup failed",array($m[1]));
                            }
                        }
                        else
                        {
                            $vcard->result->add("error","email_value","Syntax error in e-mail",array($val),"http://microformats.org/wiki/hcard-faq#X2V_does_not_convert_email_with_name_as_plain_text");
                        }
                    }
                }
                else
                {
                    $vcard->result->add("error","email_no_protocol","e-mail lacks protocol\nUse mailto:.",array(), "http://microformats.org/wiki/hcard-faq#X2V_does_not_convert_email_with_name_as_plain_text");
                }   
            } 
        }        
        
        foreach($vcard->url as $url)
        {
            $url = trim($url);
            
            if (preg_match(self::PROTOCOL_REGEX,strtolower($url),$m))
            {
                switch($m[1])
                {
                    case 'gtalk':
                    case 'jabber': $vcard->result->add("error","jabber_protocol","You should use <code>xmpp:</code> protocol rather than <code>%s:</code>",array($m[1]));
                    break;
                }
            }
            elseif (preg_match('/\.\.(\/|$)/',$url))
            {
                $vcard->result->add("warn","relative_url","Relative URL %s",array($url));
            }
        }        
    }

    private function checkVCardImage(vCard $vcard, $propname)
    {
        if (isset($vcard->data[$propname])) foreach($vcard->data[$propname] as $url)
        {
            $url = trim($url);
            
            if (preg_match(self::PROTOCOL_REGEX,strtolower($url),$m))
            {
                if ($m[1] == 'data')
                {
                    if (preg_match('/^data:\s*(([a-z0-9.-]+)\/[a-z0-9.+-]+)?\s*(;\s*charset\s*=[^,;]+)?\s*(;\s*base64)?\s*,(.+)$/',$url,$m))
                    {
                        list(,$mime,$type,$charset,$base64,$data) = $m; unset($m);
						if ($mime == 'image/svg+xml')
						{}
						else if ($type == 'image')
                        {
                            if ($base64) $data = base64_decode($data); else $data = rawurldecode($data);
                    
                            $tmpnam = tempnam(sys_get_temp_dir(),'hcardphoto'); if (!$tmpnam || !file_put_contents($tmpnam, $data)) throw new Exception("tmpnam failed");
                            $info = @getimagesize($tmpnam); 
                            @unlink($tmpnam);
                            
                            if (!$info || $info[0] < 3 || $info[1] < 3)
                            {
                                $vcard->result->add("error","photo_data_invalid","Content of <code>%s</code> property's <code>data:</code> <abbr>URI</abbr> does not appear to be an image",array($propname));
                               // echo base64_encode($data);
                            }                           
                        }
                        else
                        {
                            $vcard->result->add("error","photo_data_not_image","<code>data:</code> <abbr>URI</abbr> of <code>%s</code> does not declare <code>image/*</code> <abbr>MIME</abbr> type\nFound type <samp>%s</samp>.",array($propname,$mime));
                        }
                    }
                    else $vcard->result->add("error","data_uri_syntax","Syntax of <code>data:</code> <abbr>URI</abbr> is invalid");
                }
                else if ($m[1] != 'http' && $m[1] != 'https')
                {
                    $vcard->result->add("warn","photo_protocol","Unusual protocol used for <code>%s</code> property",array($propname));
                }
            }            
        }
    }

    private function validateVCardTels(vCard $vcard)
    {   
        foreach($vcard->tel as $tel)
        {
            if (!empty($tel['type'])) $this->checkTelTypes($vcard,$tel['type']);
            
            if (!empty($tel['value'])) foreach($tel['value'] as $val)
            {                
                $val = preg_replace('/\sx\s|;\s*ext\s*=.*|ext\./',' ',$val); // remove phone extension
                if (preg_match('/\pL/u',$val))
                {
                    $vcard->result->add("error","tel_letters","Telephone <samp>%s</samp> contains letters.\nDon't add any prefixes. Letters in the number <em>must</em> be converted to digits.",array($val),"http://microformats.org/wiki/hcard#Property_Notes");
                }
            }
        }
    }

    private function lookForHonorifics(vCard $vcard, $propname, $value)
    {
        if (preg_match('/(?:\s|^)(Mr|Ms|Mrs|Miss|Jr|Sr|[dD]r|M\. ?D|Ph. ?D|Esq|[Mm]gr|[Ii]nż|[Pp]rof|[Dd]oc|Pvt|2\/?Lt|Pfc|1\/?Lt|Lt|Spc|Capt|Sgt|Maj|Sgt|Col|Gen|Sir|R. ?D. ?O. ?N)(?:[.\s]|$)/',$value,$m))
        {
            if ($vcard->fn) $fn = $vcard->fn[0]; else $fn = $value;
            
            $vcard->result->add("error","honorific_name","Honorific prefix/suffix <samp>%s</samp> found in <code>%s</code> property",array($m[1],$propname,'http://tools.microformatic.com/query/xhtml/best-guess/'.rawurlencode($fn)));
        }
    }
    
    // based on regexp by Ted Cambron
    const ISO8601_REGEX = '/^((?:1[89]|20)\d{2}(?:(?:-?(?:00[1-9]|0[1-9][0-9]|[1-2][0-9][0-9]|3[0-5][0-9]|36[0-6]))?|(?:-?(?:1[0-2]|0[1-9]))?|(?:-?(?:1[0-2]|0[1-9])-?(?:0[1-9]|[12][0-9]|3[01]))?|(?:-?W(?:0[1-9]|[1-4][0-9]5[0-3]))?|(?:-?W(?:0[1-9]|[1-4][0-9]5[0-3])-?[1-7])?)?)(?:T?(?:2[0-3]|[01]\d):?[0-5]\d(?::?[0-5]\d(?:\.\d+)?)?(?:[+-]\d\d:?\d\d|Z?))?$/i';
    
    private function checkPastISODate(vCard $vcard, $propname, $value)
    {
        if (preg_match(self::ISO8601_REGEX, $value, $m))
        {
            if ((int)substr($m[1],0,4) > (int)date("Y"))
            {
                $vcard->result->add("error","future_date","<code>%s</code> date in future",array($propname));
            }
        }
        else
        {
            $vcard->result->add("error","date_syntax","Invalid syntax of <code>%s</code> date\n'<samp>%s</samp>' is invalid <abbr>ISO 8601</abbr> format. Use <code><var>yyyy</var>-<var>mm</var>-<var>dd</var></code> or <code><var>yyyy</var>-<var>mm</var>-<var>dd</var>T<var>hh</var>:<var>mm</var>:<var>ss</var>Z</code>.",array($propname,$value),"http://microformats.org/wiki/datetime-design-pattern");
        }
    }
    
    private function validateVCardDateTime(vCard $vcard)
    {
        if (count($vcard->tz) > 1) $vcard->result->add("error","multi_tz","Multiple time zones",array(),"http://microformats.org/wiki/hcard-singular-properties#Physical_Properties");
        
        foreach($vcard->tz as $tz) if (!preg_match('/^[+-]([01][0-9]|2[0-3]):?([0-5][0-9])(?:$|;)/',$tz)) // ignore everything after ; (examples include it)
        {
            $vcard->result->add("error","tz_value","Value of <code>tz</code> property is not an <abbr>ISO 8601 UTC</abbr> offset\nFound <samp>%s</samp>, but expected <code>+00:00</code> format.",array($tz));
        }
        
        if (count($vcard->bday) > 1) $vcard->result->add("error","multi_bday","Multiple birth dates",array(),"http://microformats.org/wiki/hcard-singular-properties#Physical_Properties");
        foreach($vcard->bday as $bday)
        {
            $this->checkPastISODate($vcard,'bday',$bday);
        }
        
        if (count($vcard->rev) > 1) $vcard->result->add("error","mutli_rev","Multiple rev values",array(),"http://microformats.org/wiki/hcard-singular-properties#Entire_vCard_Properties");
        foreach($vcard->rev as $rev)
        {
            $this->checkPastISODate($vcard,'rev',$rev);
        }        
    }
    
    private function checkAdrTypes(vCard $vcard,array $types)
    {
        foreach($types as $type)
        {
            if (!in_array(strtolower($type),array('dom','intl','parcel','postal','home','work','pref')))
            {
                $vcard->result->add("error","adr_type","Invalid address type %s\nMust be one of: <code>dom</code>, <code>intl</code>, <code>parcel</code>, <code>postal</code>, <code>home</code>, <code>work</code>, <code>pref</code>",array($type),"http://microformats.org/wiki/adr-cheatsheet#Properties_.28Class_Names.29");
            }
        }
    }
    
    private function checkTelTypes(vCard $vcard, array $types)
    {
        foreach($types as $type)
        {
            if (!in_array(strtolower($type),array('home','work','msg','pref','voice','fax','cell','video','pager','bbs','modem','car','isdn','pcs')))
            {
                $vcard->result->add("error","tel_type","Invalid telephone type %s\nMust be one of: <code>home</code>, <code>work</code>, <code>msg</code>, <code>pref</code>, <code>voice</code>, <code>fax</code>, <code>cell</code>, <code>video</code>, <code>pager</code>, <code>bbs</code>, <code>modem</code>, <code>car</code>, <code>isdn</code>, <code>pcs'</code>.",array($type),"http://microformats.org/wiki/adr-cheatsheet#Properties_.28Class_Names.29");
            }
        }
    }
    
    private function checkEmailTypes(vCard $vcard, array $types)
    {
        foreach($types as $type)
        {
            if (!in_array(strtolower($type),array('internet','pref','x400')))
            {
                $vcard->result->add("error","email_type","Invalid e-mail type %s\nMust be one of: <code>internet</code>, <code>pref</code>, <code>x400</code>.",array($type),"http://microformats.org/wiki/adr-cheatsheet#Properties_.28Class_Names.29");
            }
        }
    }
    
    private function validateVCardAdrs(vCard $vcard)
    {
        foreach($vcard->adr as $adr)
        {
            if (isset($adr['type'])) $this->checkAdrTypes($vcard,$adr['type']);
        }
        
        foreach($vcard->label as $lab)
        {
            if (isset($lab['type'])) $this->checkAdrTypes($vcard,$lab['type']);
            
            if (isset($lab['value'])) foreach($lab['value'] as $value)
            {
                if (false === strpos(trim($value),"\n")) 
                {
                    $vcard->result->add("warn","single_line_label","Address <code>label</code> has only one line\nYou must use <code>&lt;pre></code> to preserve line breaks or insert them explicitly using <code>&lt;br/></code>.");
                }
            }
        }
    }
        
    private function validateVCard(vCard $vcard)
    {
        $this->checkAndRemoveEmpty($vcard->data,$vcard);
        
        $this->validateVCardNames($vcard);

        $this->validateVCardGEO($vcard);

        $this->validateVCardURLs($vcard);
        
        $this->checkVCardImage($vcard,'photo');
        $this->checkVCardImage($vcard,'logo');
        
        $this->validateVCardTels($vcard);
        
        $this->validateVCardAdrs($vcard);
        
        $this->validateVCardDateTime($vcard);
        
        
        if (count($vcard->class) > 1) $vcard->result->add("error","mutli_class","Multiple class values",array(),"http://microformats.org/wiki/hcard-singular-properties#Entire_vCard_Properties");
        if (count($vcard->uid) > 1) $vcard->result->add("error","mutli_uid","Multiple uid values",array(),"http://microformats.org/wiki/hcard-singular-properties#Entire_vCard_Properties");
    }

    private function addLibxmlErrors(ValidationResult $result, $doc)
    {
        $dontrepeat = array();
        $dontrepeat_codes = array();
        $skipped = 0;

        foreach(libxml_get_errors() as $err)
        {
            if (isset($dontrepeat[$err->message])) {$skipped++; continue;}
            if (empty($dontrepeat_codes[$err->code])) $dontrepeat_codes[$err->code] = 0;
            if ($dontrepeat_codes[$err->code] > 3) {$skipped++; continue;}

            $result->add( $err->level == LIBXML_ERR_WARNING ? 'warn' : 'error', 'libxml_'.$err->code, "%s",array($err->message), NULL, "Line ".$err->line.', column '.$err->column );
            $dontrepeat[$err->message] = true;
            $dontrepeat_codes[$err->code]++;
        }

        if ($skipped > 2)
        {
            $result->add("error","too_many_errors","%s duplicate <code>XML</code> errors not shown",array($skipped));
        }
    }

    private function clearLibxmlErrors()
    {
        libxml_use_internal_errors(true);
        libxml_clear_errors();
    }

    private function loadFragment($source, ValidationResult $result)
    {
        $doc = new DOMDocument('1.0','UTF-8');
        $doc->resolveExternals = true;
        $this->clearLibxmlErrors();
        
        if (!$this->sniffXHTMLLikeTagsoup($source))
        {
            $source = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
            <html xmlns="http://www.w3.org/1999/xhtml"><head profile="http://www.w3.org/2006/03/hcard"><title>XHTML fragment</title></head><body>'.$source.'</body></html>';
        }
        
        @$doc->loadXML($source);
        $this->addLibxmlErrors($result,$doc);
        return $doc;
    }

    private function loadDOM($filePath, ValidationResult $result)
    {
        $doc = new DOMDocument();
        $this->clearLibxmlErrors();
        $doc->resolveExternals = true;
        $start = microtime(true);
        @$doc->load($filePath);
        $this->addLibxmlErrors($result,$doc);
        $dur = microtime(true) - $start;
        return $doc;
    }

    static $xsltCache = array();
    private static function processStylesheet(DOMDocument $doc, $fileName)
    {
        if (empty(self::$xsltCache[$fileName]))
        {            
            libxml_use_internal_errors(false);
            $stylesheet = new DOMDocument();
            if (!$stylesheet->load('xslt/'.$fileName)) throw new Exception("Stylesheet $fileName is not well-formed");

            $xslt = new XSLTProcessor();
            if (!$xslt->importStylesheet($stylesheet)) throw new Exception("Stylesheet $fileName is invalid");
            
            self::$xsltCache[$fileName] = $xslt;
        }
        else
        {
            $xslt = self::$xsltCache[$fileName];
        }        
        return $xslt->transformToDoc($doc);
    }
}
