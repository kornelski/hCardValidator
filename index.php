<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

try
{
    require_once "common.php";

    if (isset($_GET['output']) && $_GET['output'] == 'json')
    {
        $template = new stdClass;
        header("Content-Type:text/plain;charset=UTF-8");
    }
    else
    {
        $template = new PHPTAL("tpl/main.html");
        require_once "phptal/PHPTAL/GetTextTranslator.php";

        $tr = new PHPTAL_GetTextTranslator();
        $tr->setLanguage('en', 'en_US');
        $tr->addDomain('main', './locale/');
        $tr->useDomain('main');
        $tr->setStructuredTranslations(true);
        $template->setTranslator($tr);

        if (isset($_SERVER['HTTP_USER_AGENT'],$_SERVER['HTTP_ACCEPT']) && $_SERVER['HTTP_ACCEPT'] == '*/*' && false !== strpos($_SERVER['HTTP_USER_AGENT'],'MSIE '))
    	header("Content-Type:application/xml;charset=UTF-8");
    	else header("Content-Type:application/xhtml+xml;charset=UTF-8");

    }
    
    $template->host = $_SERVER['HTTP_HOST'];
    $template->post = $_POST;
    $template->get = $_GET;
    $template->activetab = 'url';
    
    $template->filename = '';

    $validator = new hCardValidator();

    if (isset($_POST['feedback']))
    {
        $template->activetab = 'feedback';
        
        require_once "sblamtest.php";
        
        if (empty($_POST['feedback']))
        {
            $template->feedback_error = 'Please write your feedback';
        }
        else
        {
            $isspam = sblamtestpost(array('feedback','feedbackname'),'W4RBXxrfhASlRgs19K') >= 1;
            
            $msg = "From: ".cleanstring($_POST['feedbackname'])."\n".
            "IP: ".$_SERVER['REMOTE_ADDR'].(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])? '; '.cleanstring($_SERVER['HTTP_X_FORWARDED_FOR']):'')."\n".
            "UA: ".cleanstring($_SERVER['HTTP_USER_AGENT'])."\n".
            "Path: ".cleanstring($_SERVER['REQUEST_URI']).(!empty($_SERVER['HTTP_REFERER'])? '; '.cleanstring($_SERVER['HTTP_REFERER']):'')."\n".
            "\nMsg:\n\n".cleanstring($_POST['feedback']);
            
            if (!$isspam && mail("hcard@geekhood.net",_("hCard Validator feedback from ").
            substr(preg_replace('/[^a-z0-9_ !?:+-]+/i',' ',$_POST['feedbackname']),0,40), $msg, "Content-Type: text/plain;charset=UTF-8\r\nFrom: \"hCard Validator\" <hcard@geekhood.net>"))
            {                
                unset($_POST['feedback']); unset($_POST['feedbackname']);
                $template->feedback_ok = _("The message has been sent, thank you!");
            }
            else
            {
                $template->feedback_error = sprintf(_("Sending failed, sorry! Please <a href='%s'>e-mail your message instead</a>."),myhtmlspecialchars('mailto:hcard@geekhood.net?Subject='.rawurlencode("Feedback from ".$_POST['feedbackname']).'&body='.rawurlencode($_POST['feedback'])));
            }
        }
    }
    elseif (isset($_POST['fragment']))
    {
        $template->activetab = 'fragment';
        
        header("Cache-Control: max-age=5");

        $_POST['fragment'] = cleanstring($_POST['fragment']);

        $template->fragment = $_POST['fragment'];
        $template->result = $validator->validateXHTMLFragment($_POST['fragment']);
    }
    elseif (isset($_FILES['file']))
    {
        $template->activetab = 'file';
                
        $template->result = $validator->validateUpload($_FILES['file']);
        $template->source = file_get_contents($_FILES['file']['tmp_name']);
    }
    elseif (!empty($_GET['url']))
    {
        $template->activetab = 'url';
        
        header("Cache-Control: max-age=5");
        
        $result = $validator->validateURL(cleanstring($_GET['url']));
        $template->url = isset($result->url) ? $result->url : NULL;
        $template->result = $result;
        $template->source = isset($result->source) ? $result->source : NULL;
        
    }
    elseif (!empty($_GET['example']))
    {
        $template->activetab = 'example';
        
        header("Cache-Control: max-age=6000");
        
        foreach(glob('examples/*.htm*') as $file)
        {
            if ($file === $_GET['example'])
            {
                $template->result = $validator->validateFile($file);
                $template->filename = $file;
                $template->source = file_get_contents($file);
            }
        }
    }
    else
    {
        header("Cache-Control: max-age=100000");        
    }
    
    if ($template instanceof PHPTAL)
    {
        echo $template->execute();    
    }
    else
    {
        $out = array();
        $out['message'] = $template->result->isValid ? 'No errors found!' : 'Document contains errors';
        $out['type'] = $template->result->isValid ? 'success' : 'failure';
        
        if (!empty($template->url)) $out['url'] = $template->url;
        
        if (!empty($template->result))
        {
            $out['messages'] = array();
            $out['hcards'] = array();
            foreach($template->result->errors as $err)
            {
                $out['messages'][] = err_to_message($err);            
            }
            
            foreach($template->result->vcards as $cardnum => $vcard)
            {
                foreach($vcard->result->errors as $err)
                {
                    $out['messages'][] = err_to_message($err,$cardnum);            
                }           
                $out['hcards'][] = $vcard->data;     
            }            
        }
        
        if (!empty($template->source)) $out['source'] = array('code'=>$template->source);
        if (!empty($template->result) && !empty($template->result->parsedSource)) $out['parseTree'] = array('code'=>$template->result->parsedSource);
        
        echo json_encode($out);        
    }
}
catch(Exception $e)
{
    @header('HTTP/1.1 500 oops');
    @header("Content-Type:application/xhtml+xml;charset=UTF-8");
    ?>
    <html xmlns="http://www.w3.org/1999/xhtml">
        <head><title>hCard Validator – Error</title></head>
        <body><h1>Internal Error</h1>
            <p>Because of error in the validator it was impossible to check this document.</p>
            <?php if (ini_get('display_errors')) echo '<pre>'.myhtmlspecialchars($e).'</pre>'; ?>
        </body>
    </html>
    <?php
}

function err_to_message(array $err, $cardnum = NULL)
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
