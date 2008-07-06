<!DOCTYPE html>
<html>
<link rel=stylesheet href="/i/style.css">
<meta charset=UTF-8>
<meta name=robots content="index,nofollow">
<title>e-mail address obfuscator</title>
<h1>hCard-friendly e-mail address obfuscator</h1>
<p>It encodes address using <code>urlencode</code>, <abbr>HTML</abbr> entities and then generates markup that's as ugly as possible, while remaining valid and parseable by browsers and <abbr>XML</abbr>-compliant parsers. <strong>It's not bullet-proof.</strong>
<form>
<div><label for=email>e-mail address:</label> <input id=email type=email name=addr required></div>
<p><input type=submit value=Encode></p>
</form>
<?php

function enkoduj($m,$url=true)
{
 $o='';
 if ($url)
 {
   for($i=0;$i<strlen($m);$i++)
   {
     $o .= (mt_rand(0,100) > 60 || !ctype_alnum($m{$i}))?sprintf('%%%02x',ord($m{$i})):$m{$i};
   }
   $m = 'mailto: '.$o.'?'; $o='';
 }
 for($i=0;$i<strlen($m);$i++)
 {
   if (!$url && $i==strlen($m)>>1) $o .= '<!--
mailto:abuse@hotmail.com
</a>
-->&shy;';
   $o .= (mt_rand(0,100) > 40 || $m{$i}==' ' || $m{$i}=='.' || $m{$i}==':')?sprintf((mt_rand(0,100) > 66)?'&#%d;':'&#x%'.((mt_rand()&4)?'X':'x').';',ord($m{$i})):$m{$i};
 }
 return $o;
}

if (isset($_GET['addr']))
{
$addr = trim(strip_tags($_GET['addr']));

$out = '<a 
class=\'email
href="mailto:me"
\' 
href 
= \''."\t".'
&#x20;'.enkoduj($addr).'
\'>'.enkoduj($addr,false).'</a>';


echo '<pre style="padding:2em;border:1px dashed #eee"><code>'.htmlspecialchars($out).'</code></pre>';
echo '<p>Test: '.$out.'</p>';
}
?>
<hr>
<p><a href="/">Return to the hCard Validator</a>.