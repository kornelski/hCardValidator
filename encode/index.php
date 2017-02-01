<!DOCTYPE html>
<html>
<link rel=stylesheet href="/i/style.css">
<meta charset=UTF-8>
<meta name=robots content="index,nofollow">
<title>e-mail address obfuscator</title>
<h1>hCard-friendly e-mail address obfuscator</h1>
<p>It encodes e-mail addresses using random mix of <code>urlencode</code>, <abbr>HTML</abbr> entities and then generates markup that's as tricky as possible, while remaining <strong>valid and parseable by browsers</strong> and <abbr>XML</abbr>-compliant parsers.
<form id=email-encoder>
<div><label for=email>e-mail address:</label> <input id=email type=email name=addr required> <small>(<em>obviously</em>, these e-mails are not collected)</small></div>
<p><input type=submit value=Encode></p>
</form>
<?php

/**
 * obfuscates e-mail address
 * @param in_attribute - if true, will not put HTML comments in it (result will suitable for use in href)
 */
function html_encode_email_address($m,$in_attribute=true)
{
    $o='';
    if ($in_attribute)
    {
        for($i=0;$i<strlen($m);$i++)
        {
            // apply url-encoding at random just to be more confusing
            $o .= (mt_rand(0,100) > 60 || !ctype_alnum($m[$i]))?sprintf('%%%02x',ord($m[$i])):$m[$i];
        }
        $m = 'mailto:%20'.$o.'?'; $o=''; // query string is allowed in mailto:, even if empty
    }

    for($i=0;$i<strlen($m);$i++)
    {
        if (!$in_attribute && $i==strlen($m)>>1) $o .= '<!--
mailto:abuse@hotmail.com
</a>
-->&shy;';

        // random characters are encoded + few special characters for added trickyness.
        // <>& are encoded to protect encoder against XSS.
        if (mt_rand(0,100) > 40 || false !== strpos(" .:<>&",$m[$i]))
        {
            // mix of decimal and hexadecimal entities
            $format = (mt_rand(0,100) > 66) ? '&#%d;' :
                      '&#x%'.((mt_rand()&4)?'X':'x').';';
            $o .= sprintf($format, ord($m[$i]));
        }
        else
        {
            $o .= $m[$i];
        }
    }
    return $o;
}

if (isset($_GET['addr']))
{
    $addr = trim($_GET['addr']);

    // that's class attribute containing newlines and attribute-like syntax.
    // should be enough to confuse regex-based extractors
    $out = "<a\nclass='email\nhref=\"mailto:x@y\"\n'\nhref\n =\t'\t\n&#x20;" .
           html_encode_email_address($addr) .
           "\n'>" .
           html_encode_email_address($addr,false) .
           '</a>';

    echo '<pre style="padding:2em;border:1px dashed #eee"><code>'.htmlspecialchars($out).'</code></pre>';
    echo '<p>Test: '.$out.'</p>';
}
?>
<hr>
<p><a href="/">Return to the hCard Validator</a>.
