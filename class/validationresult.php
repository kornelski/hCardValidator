<?php

class ValidationResult
{
	/**
	 * just for display
	 */
    public $fileName;

	/**
	 * @see add() for structure
	 */
    public $errors = array();
    public $isValid;

	/**
	 * array of VCard objects extracted from validated document
	 */
    public $vcards = array();

    function __construct($fileName)
    {
        $this->isValid = true;
        $this->fileName = $fileName;
    }

	/**
	 * hcard.xslt creates bunch of nice elements, which aren't very useful in their Node'ish form.
	 * Cram everything into arrays and objects instead!
	 *
	 * @return void
	 */
    function addFromDoc(DOMDocument $doc)
    {
        $xp = new DOMXPath($doc);
        $xp->registerNamespace('v','http://pornel.net/hcard-validator'); // my hacks
        $xp->registerNamespace('c','http://www.w3.org/2006/03/hcard'); // let's pretend this is real

		// first get all errors that don't belong to any particular vcard
        foreach($xp->query('(//v:error|//v:warn|//v:info)[not(ancestor::c:vcard)]') as $node)
        {
            $args = array();
            foreach($xp->query('v:arg',$node) as $arg)
            {
              $args[] = $arg->textContent;
            }
            $this->add($node->localName,$node->getAttribute('id'),Controller::escapeXML($node->textContent),$args,$node->getAttribute('href'));
            $node->parentNode->removeChild($node); // clean up errors
        }

		// then extract vcards and their errors
        foreach($xp->query('//c:vcard') as $vcardNode)
        {
            $result = new ValidationResult($this->fileName);
            foreach($xp->query('(.//v:error|.//v:warn|.//v:info)',$vcardNode) as $node)
            {
				// build nice location string which won't be used anyway! :)
                $location = '';
                foreach($xp->query('ancestor::c:*',$node) as $parent)
                {
                    if ($parent->localName == 'vcard') break;
                    $location .= ($location ? ' » ':'').$parent->localName;
                }

				// args for i18n strings
                $args = array();
                foreach($xp->query('v:arg',$node) as $arg)
                {
                  	$args[] = $arg->textContent;
                }
                $result->add($node->localName,$node->getAttribute('id'),Controller::escapeXML($node->textContent),$args,$node->getAttribute('href'),$location);
                $node->parentNode->removeChild($node); // clean up errors
            }

            $vcard = new vCard($vcardNode,$result);
            $this->vcards[] = $vcard;
        }
    }

	/**
	 * add new error
	 * 
	 * @param $type - error, warn or info
	 * @param $error_class - @see localizedMessage
	 * @param $default_message - if message has more than one line, first line is used as header, rest is body.
	 * @param $args - @see localizedMessage
	 * @param $href - link to more detailed explanation of the error
	 * @param $location - (string) where the error has occured
	 */
    function add($type, $error_class, $default_message, array $args = array(), $href = NULL, $location = NULL)
    {
        if ($type == 'error') $this->isValid = false;

        $this->errors[] = array(
            'type'=>$type,
            'class'=>$error_class,
            'message'=>$default_message,
            'args'=>$args,
            'href'=>$href,
            'location'=>$location,
        );
    }
}
