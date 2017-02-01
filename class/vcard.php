<?php

/**
 * Easy access to vCard DOM nodes extracted via XSLT.
 *
 * vcard->result holds vcard-specific errors.
 */
class vCard
{
    public $data, $result;
    private $flags;

    function __construct(DOMNode $node, ValidationResult $result)
    {
        $this->result = $result;
        $this->node = $node;
        $this->flags = [];

        $this->data = $this->nodeToArray($node);
        if (!is_array($this->data))
        {
            $this->data = []; // nodeToArray may return string if there are no elements!
        }
    }

    function flag($name)
    {
        if (!isset($this->flags[$name])) return false;

        return $this->flags[$name];
    }

    function __get($name)
    {
        if (isset($this->data[$name])) return $this->data[$name];
        return [];
    }

    function append($name,$value)
    {
        if (!isset($this->data[$name])) $this->data[$name] = [];
        $this->data[$name][] = $value;
    }

    function query($path)
    {
        $parts = explode('/',$path);
        $result = [];
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
        $org_names = [];
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
        $data = [];

        $hasElements = false;
        foreach($node->childNodes as $element)
        {
            if ($element->nodeType != XML_ELEMENT_NODE) continue;

            if ($element->namespaceURI == "http://www.w3.org/2006/03/hcard")
            {
                $hasElements = true;

                $name = $element->localName;
                if (!isset($data[$name])) $data[$name] = [];

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


	public function checkAndRemoveEmpty()
	{
        $this->flags['had_n'] = isset($this->data['n']); // even empty n property prevents implied nickname. this function will remove all empty props, so this needs to be saved now.

		$this->checkAndRemoveEmptyRecursive($this->data);
	}

	/**
	 * hcard.xslt may produce properties with empty values
	 * scan data (recursively) and remove properties (or subtrees) with empty values
	 *
	 * some properties can be empty - it's handled elsewhere
	 */
    private function checkAndRemoveEmptyRecursive(array &$data, $parent = NULL)
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

						// adr and value (combined) of tel/email are not allowed to be empty
                        if ($prop == 'adr' || ($prop == 'value' && $parent && ($parent == 'tel' || $parent == 'email')))
                        {
                            $warn_or_err = "error";
                        }

                        if ($parent) $this->result->add($warn_or_err,"empty_subprop","<code>%s</code> property of <code>%s</code> is empty",[$prop,$parent]);
                        else $this->result->add($warn_or_err,"empty_prop","<code>%s</code> property is empty",[$prop]);

                        unset($values[$idx]);
                    }
                }
                else
                {
                    $this->checkAndRemoveEmptyRecursive($v, $prop);
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
}
