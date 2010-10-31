<?php

require_once "class/loader.php";
require_once "class/validationresult.php";
require_once "class/vcard.php";
require_once "class/hcardvalidator.php";
require_once "class/controller.php";

require_once "sblamtest.php";

require_once "PHPTAL.php";
require_once "PHPTAL/GetTextTranslator.php";

try
{
    $c = new Controller();
    $c->init($_SERVER['HTTP_HOST'] == 'hcard');
    $c->run();
}
catch(Exception $e)
{
    @header('HTTP/1.1 500 oops');
    @header("Content-Type:application/xhtml+xml;charset=UTF-8");
    ?>
    <html xmlns="http://www.w3.org/1999/xhtml">
        <head><title>hCard Validator – Error</title></head>
        <body><h1>Internal Error</h1>
            <p>Because of an error in the validator it was impossible to check this document.</p>
            <?php if (ini_get('display_errors')) echo '<pre>'.Controller::escapeXML($e).'</pre>'; ?>
        </body>
    </html>
    <?php
}

