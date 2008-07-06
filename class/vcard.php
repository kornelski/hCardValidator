<?php


class vCard
{
    public $data, $result;
    private $flags;
    
    function __construct(DOMNode $node, ValidationResult $result)
    {        
        $this->result = $result;
        $this->node = $node; 
        $this->flags = array();
        
        $this->data = $this->nodeToArray($node);
        if (!is_array($this->data)) 
        {
            $this->data = array(); // nodeToArray may return string if there are no elements!
        }
        
        $this->flags['had_n'] = isset($this->data['n']); // even empty n property prevents implied nickname. validator will later remove empty props, so this needs to be saved now.
    }
    
    function flag($name)
    {
        if (!isset($this->flags[$name])) return false;
        
        return $this->flags[$name];
    }
    
    function __get($name)
    {    
        if (isset($this->data[$name])) return $this->data[$name];
        return array();
    }
    
    function append($name,$value)
    {
        if (!isset($this->data[$name])) $this->data[$name] = array();
        $this->data[$name][] = $value;
    }
    
    function query($path)
    {
        $parts = explode('/',$path);
        $result = array();
        $this->queryPart($parts,$this->data,$result);
        return $result;
    }    
    
    
    private function queryPart(array $parts, array $in, array &$result)
    {
        $prop = array_shift($parts);
        
        if (isset($in[$prop]) and is_array($in[$prop]))
        {
            if (count($parts))
            {
                foreach($in[$prop] as $k => $v)
                {
                    if (is_array($v))
                    {
                        $this->queryPart($parts, $v, $result);
                    }
                    else
                    {
                        echo "WTF? $k in $prop";
                    }
                }
            }
            else
            {
                foreach($in[$prop] as $k => $v)
                {
                    $result[] = $v;
                }
            }
        } 
    }
    
    function allOrgNames()
    {        
        $org_names = array();
        foreach($this->org as $org)
        {
            if (!empty($org['organization-name'])) foreach($org['organization-name'] as $name)
            {
                $org_names[] = $name;
            }
        }
        return $org_names;
    }
    
    private function nodeToArray(DOMNode $node)
    {
        $data = array();
        
        $hasElements = false;
        foreach($node->childNodes as $element)
        {
            if ($element->nodeType != XML_ELEMENT_NODE) continue;            
            
            if ($element->namespaceURI == "http://www.w3.org/2006/03/hcard")
            {            
                $hasElements = true;
        
                $name = $element->localName;
                if (!isset($data[$name])) $data[$name] = array();
            
                $data[$name][] = $this->nodeToArray($element);
            }
            else if ($element->namespaceURI == "http://pornel.net/hcard-validator")
            {
                if ($element->localName == 'flag')
                {                    
                    $this->flags[ $element->getAttribute('id') ] = ($element->textContent == '') ? true : $element->textContent;
                }
            }
        }
        
        if (!$hasElements)
        {
            return trim($node->textContent,' ');
        }
        else
        {
            return $data;
        }
    }
    
}