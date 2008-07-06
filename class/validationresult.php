<?php

class ValidationResult
{
    public $fileName;
    public $errors = array();
    public $isValid;
    public $vcards = array();
    
    function __construct($fileName)
    {
        $this->isValid = true;
        $this->fileName = $fileName; 
    }
    
    function addFromDoc(DOMDocument $doc)
    {        
        $xp = new DOMXPath($doc);
        $xp->registerNamespace('v','http://pornel.net/hcard-validator');
        $xp->registerNamespace('c','http://www.w3.org/2006/03/hcard');
        
        foreach($xp->query('(//v:error|//v:warn|//v:info)[not(ancestor::c:vcard)]') as $node)
        {
            $args = array();
            foreach($xp->query('v:arg',$node) as $arg)
            {
              $args[] = $arg->textContent;
            }
            $this->add($node->localName,$node->getAttribute('id'),myhtmlspecialchars($node->textContent),$args,$node->getAttribute('href'));
            $node->parentNode->removeChild($node); // clean up errors
        }
        
        foreach($xp->query('//c:vcard') as $vcardNode)
        {
            $result = new ValidationResult($this->fileName);
            foreach($xp->query('(.//v:error|.//v:warn|.//v:info)',$vcardNode) as $node)
            {
                $location = '';
                foreach($xp->query('ancestor::c:*',$node) as $parent)
                {
                    if ($parent->localName == 'vcard') break;
                    $location .= ($location ? ' » ':'').$parent->localName;
                }
                $args = array();
                foreach($xp->query('v:arg',$node) as $arg)
                {
                  $args[] = $arg->textContent;
                }
                $result->add($node->localName,$node->getAttribute('id'),myhtmlspecialchars($node->textContent),$args,$node->getAttribute('href'),$location);
                $node->parentNode->removeChild($node); // clean up errors
            }       
            
            $vcard = new vCard($vcardNode,$result);                            
            $this->vcards[] = $vcard;            
        }
    }
    
    function localizedMessage($error_class, $default_message, array $args = array())
    {
        $txt = dgettext("errors",$error_class);
        if (!$txt || $txt === $error_class) $txt = $default_message;
        
        if (count($args))
        {
          foreach($args as &$ar)
          {
            $ar = myhtmlspecialchars($ar);
          }
          array_unshift($args,$txt);
          $txt = call_user_func_array('sprintf',$args);
        }        
        return $txt;
    }
    
    function add($type, $error_class, $default_message, array $args = array(), $href = NULL, $location = NULL)
    {
        $message = $this->localizedMessage($error_class, $default_message, $args);
        $more = NULL;
        
        $t = explode("\n",$message,2);
        if (count($t)==2)
        {
            list($message,$more) = $t;
        }
        
        if ($type == 'error') $this->isValid = false;
        
        $this->errors[] = array(
            'type'=>$type,
            'class'=>$error_class,
            'message'=>$message,
            'more'=>$more,
            'href'=>$href,
            'location'=>$location,
        );
    }
}
