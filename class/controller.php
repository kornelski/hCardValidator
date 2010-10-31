<?php

class Controller
{
    function init($debug)
    {
        $this->initPHP($debug);
        $this->initCatalog();
        $this->initTranslator($this->getLanguage());
    }

    private function initPHP($debug)
    {
        ini_set('display_errors', $debug ? 1 : 0);
        error_reporting($debug ? E_ALL : 0);

        date_default_timezone_set('UTC');

        if (!function_exists('iconv')) throw new Exception("Iconv not installed");
        if (!function_exists('bindtextdomain')) throw new Exception("Gettext not installed");
        if (!function_exists('mb_strlen')) throw new Exception("mbstring not installed");
        if (!class_exists('XSLTProcessor')) throw new Exception("XSLTProcessor not installed");
    }

    /** Set up local XML Catalog (env variable read by libxml) to avoid hitting W3C website whenever document with DOCTYPE is validated */
    private function initCatalog()
    {
        $catalogfile = dirname(dirname(__FILE__)).'/xmlcatalog.xml';
        putenv('XML_CATALOG_FILES='.$catalogfile);
        $_ENV['XML_CATALOG_FILES'] = $catalogfile;
    }

    private $translator;
    private function initTranslator($lang)
    {
        $this->translator = new PHPTAL_GetTextTranslator();

        if ($lang == 'pl')
        {
            $this->translator->setLanguage('pl_PL.utf8','pl_PL.UTF-8','pl_PL','pl','en_US.utf8','en_US.UTF-8','en_US','en');
        }
        else
        {
            $this->translator->setLanguage('en_US.utf8','en_US.UTF-8','en_US','en');
        }

        $this->translator->addDomain('main', './locale/');
        $this->translator->addDomain('props', './locale/');
        $this->translator->addDomain('errors', './locale/');
        $this->translator->useDomain('main');
    }

    /**
     * translate hcard property (e.g. fn) to something more recognizable by humans
     */
    public function readablePropertyName($propname)
    {
        return dgettext("props",$propname);
    }

    private function getLanguage()
    {
        // subdomains have fixed language
        if (substr($_SERVER['HTTP_HOST'],0,3)=='pl.')
        {
            return 'pl';
        }
        else if (substr($_SERVER['HTTP_HOST'],0,3)=='en.')
        {
            return 'en';
        }
        // otherwise Accept is used
        else if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE']) && preg_match('/\bpl(\b|_)/',$_SERVER['HTTP_ACCEPT_LANGUAGE']))
        {
            return 'pl';
        }
        return 'en';
    }

    function run()
    {
        $out = array(
            'filename' => '',
            'host' => $_SERVER['HTTP_HOST'],
            'post' => $_POST,
            'get' => $_GET,
            'lang' => $this->getLanguage(),
        );

        if (isset($_POST['feedback']))
        {
            $out['activetab'] = 'feedback';
            $out = array_merge($out, $this->feedback());
        }
        elseif (isset($_POST['fragment']))
        {
            $out['activetab'] = 'fragment';
            $out = array_merge($out, $this->fragment($_POST['fragment']));
        }
        elseif (isset($_FILES['file']))
        {
            $out['activetab'] = 'file';
            $out = array_merge($out, $this->file($_FILES['file']));
        }
        elseif (!empty($_GET['url']))
        {
            $out['activetab'] = 'url';
            $out = array_merge($out, $this->url($_GET['url']));
        }
        elseif (!empty($_GET['example']))
        {
            $out['activetab'] = 'example';
            $out = array_merge($out, $this->example($_GET['example']));
        }
        else
        {
            $out['activetab'] = 'url';
            $out = array_merge($out, $this->main());
        }

        if (isset($out['result'])) $this->localizeValidationResult($out['result']);

        if (isset($out['cache_control']))
        {
            header("Cache-Control: ".$out['cache_control']);
        }

        if (isset($_GET['output']) && $_GET['output'] == 'json')
        {
            $this->jsonOutput($out);
        }
        else
        {
            $this->phptalOutput($out);
        }
    }

    private function localizeValidationResult(ValidationResult $res)
    {
        foreach($res->errors as &$error)
        {
            $message = $this->localizedMessage($error['class'], $error['message'], $error['args']);

            $more = NULL;
            $t = explode("\n",$message,2);
            if (count($t)==2)
            {
                list($message,$more) = $t;
            }

            unset($error['args']);
            $error['message'] = $message;
            $error['more'] = $more;
        }

        foreach($res->vcards as $vcard)
        {
            $this->localizeValidationResult($vcard->result);
        }
    }

    /**
	 * try to translate message using $error_class as a key, use $default_message otherwise
	 * HTML is allowed in message. Escaped in args.
	 *
	 * @param $default_message - ngettext will be used if it contains %d
	 * @param $error_class - used as translation key.
	 * @param $args - parameters to be substituted for %s in localized strings
	 * @return string
	 */
    private function localizedMessage($error_class, $default_message, array $args = array())
    {
        if (false !== strpos($default_message,'%d'))
        {
            $txt = dngettext("errors",$error_class, $error_class, $args[0]);
        }
        else
        {
            $txt = dgettext("errors",$error_class);
        }

        if (!$txt || $txt === $error_class) $txt = $default_message;

        if (count($args))
        {
            foreach($args as &$ar)
            {
                $ar = str_replace("\n"," ",self::escapeXML($ar));
            }
            array_unshift($args,$txt);

            $txt = call_user_func_array('sprintf',$args);
        }
        return $txt;
    }

    /**
     * Validator's hostname without language prefix
     */
    private function getBaseHostname()
    {
        return preg_replace('/^[a-z]{2}\.|:\+/','',$_SERVER['HTTP_HOST']);
    }

    private function phptalOutput(array $out)
    {
        $template = new PHPTAL('tpl/main.html');

        $template->set('basehostname', $this->getBaseHostname());

        foreach($out as $k => $v)
        {
            $template->set($k,$v);
        }

        $template->setTranslator($this->translator);

        // help IE fail in less scary way
        if (isset($_SERVER['HTTP_USER_AGENT'],$_SERVER['HTTP_ACCEPT']) && $_SERVER['HTTP_ACCEPT'] == '*/*' && false !== strpos($_SERVER['HTTP_USER_AGENT'],'MSIE '))
            header("Content-Type:application/xml;charset=UTF-8");
        else
            header("Content-Type:application/xhtml+xml;charset=UTF-8");

        echo $template->execute();
    }

    private function jsonOutput(array $out)
    {
        $api = array();
        $api['message'] = $out['result']->isValid ? 'No errors found!' : 'Document contains errors';
        $api['type'] = $out['result']->isValid ? 'success' : 'failure';

        if (!empty($out['url'])) $api['url'] = $out['url'];

        if (!empty($out['result']))
        {
            $api['messages'] = array();
            $api['hcards'] = array();
            foreach($out['result']->errors as $err)
            {
                $api['messages'][] = $this->errorToAPIMessage($err);
            }

            foreach($out['result']->vcards as $cardnum => $vcard)
            {
                foreach($vcard->result->errors as $err)
                {
                    $api['messages'][] = $this->errorToAPIMessage($err,$cardnum);
                }
                $api['hcards'][] = $vcard->data;
            }
        }

        if (!empty($out['source'])) $api['source'] = array('code'=>$out['source']);
        if (!empty($out['result']) && !empty($out['result']->parsedSource)) $api['parseTree'] = array('code'=>$out['result']->parsedSource);

        header("Content-Type:application/json;charset=UTF-8");
        header("Content-Disposition:inline; filename=\"hcardvalidator.json\"");
        echo json_encode($api);
    }

    private function errorToAPIMessage(array $err, $cardnum = NULL)
    {
        $msg = html_entity_decode(strip_tags($err['message']),ENT_QUOTES,'UTF-8');
        $message = array(
            'id'=>$err['class'],
            'type'=>$err['type'] == 'error' ? 'error' : 'info',
            'message'=>$msg,
        );

        if ($msg !== $err['message']) $message['message_html']=$err['message'];

        if ($err['type'] == 'warn') $message['subtype']= 'warning';
        if (trim($err['more'])) $message['description_html']=$err['more'];
        if ($err['href']) $message['help_url']=$err['href'];
        if ($err['location']) $message['location'] = $err['location'];
        if ($cardnum !== NULL) $message['related_hcard_index'] = $cardnum;

        return $message;
    }

    /**
     * ensure that UTF-8 is valid and does not contain any characters forbidden in XML
     */
    public static function cleanUTF8($str, $charset = 'UTF-8')
    {
        if (function_exists('iconv')) {
            $str = @iconv($charset,'UTF-8//IGNORE',$str);
        }
        $str = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+|\xC2[\x80-\x9F]/','',$str);
        return $str;
    }

    public static function escapeXML($str)
    {
        return htmlspecialchars(self::cleanUTF8($str));
    }


    function feedback()
    {
        $out = array();

        $out['feedbackname'] = isset($_POST['feedbackname']) ? self::cleanUTF8($_POST['feedbackname']) : NULL;
        $out['feedback'] = isset($_POST['feedback']) ? self::cleanUTF8($_POST['feedback']) : NULL;

        if (!$out['feedback'])
        {
            $out['feedback_error'] = $this->translator->translate('Please write your feedback');
        }
        else
        {
            $msg = "From: ".$out['feedbackname']."\n".
            "IP: ".$_SERVER['REMOTE_ADDR'].(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])? '; '.self::cleanUTF8($_SERVER['HTTP_X_FORWARDED_FOR']):'')."\n".
            "UA: ".self::cleanUTF8($_SERVER['HTTP_USER_AGENT'])."\n".
            "Path: ".self::cleanUTF8($_SERVER['REQUEST_URI']).(!empty($_SERVER['HTTP_REFERER'])? '; '.self::cleanUTF8($_SERVER['HTTP_REFERER']):'')."\n".
            "\nMsg:\n\n".$out['feedback'];

            $isspam = sblamtestpost(array('feedback','feedbackname'),'W4RBXxrfhASlRgs19K') >= 1;
            if (!$isspam && mail("hcard@geekhood.net","hCard Validator feedback from ".
            substr(preg_replace('/[^a-z0-9_ !?:+-]+/i',' ',$_POST['feedbackname']),0,40), $msg, "Content-Type: text/plain;charset=UTF-8\r\nFrom: \"hCard Validator\" <hcard@geekhood.net>"))
            {
                unset($out['feedback']); unset($out['feedbackname']);
                $out['feedback_ok'] = $this->translator->translate("The message has been sent, thank you!");
            }
            else
            {
                $out['feedback_error'] = sprintf($this->translator->translate("Sending failed, sorry! Please <a href='%s'>e-mail your message instead</a>."),self::escapeXML('mailto:hcard@geekhood.net?Subject='.rawurlencode("Feedback from ".$_POST['feedbackname']).'&body='.rawurlencode($_POST['feedback'])));
            }
        }
        return $out;
    }

    private function fragment($source)
    {
        $out = array();
        $out['cache_control'] = "max-age=600";

        $validator = new hCardValidator();
        $out['fragment'] = self::cleanUTF8($source);
        $out['result'] = $validator->validateXHTMLFragment($out['fragment']);
        return $out;
    }

    private function file(array $FILE)
    {
        $out = array();
        $validator = new hCardValidator();
        $out['result'] = $validator->validateUpload($FILE);
        $out['source'] = file_get_contents($FILE['tmp_name']);
        return $out;
    }

    private function url($url)
    {
        $out = array();
        $out['cache_control'] = "max-age=5";

        $validator = new hCardValidator();
        $result = $validator->validateURL(self::cleanUTF8($url));
        $out['url'] = isset($result->url) ? $result->url : NULL;
        $out['result'] = $result;
        $out['source'] = isset($result->source) ? $result->source : NULL;
        return $out;
    }

    private function example($examplefile)
    {
        $out = array();
        $out['cache_control'] = "max-age=".(3600*24*31);

        $validator = new hCardValidator();
        foreach(glob('examples/*.htm*') as $file)
        {
            if ($file === $examplefile)
            {
                $out['result'] = $validator->validateFile($file);
                $out['filename'] = $file;
                $out['source'] = file_get_contents($file);
                return $out;
            }
        }
        throw new Exception("Can't find file");
    }

    private function main()
    {
        $out = array();
        $out['cache_control'] = "max-age=".(3600*24*1);
        return $out;
    }
}

