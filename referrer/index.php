<?php
header('Vary: Referer');

if (!empty($_SERVER['HTTP_REFERER']) && preg_match('!^https?://!',$_SERVER['HTTP_REFERER']))
{ 
    header('HTTP/1.1 302 go');
    $url = 'http://'.$_SERVER['HTTP_HOST'].'/?url='.rawurlencode($_SERVER['HTTP_REFERER']);
    header('Location: '.$url);
    ?><!DOCTYPE html>
    <title>Validate by Referer</title>
    <meta charset=UTF-8>
    <link rel="stylesheet" href="/i/style.css">
    <h1>Validating…</h1>
    <p><a href="<?php htmlspecialchars($url);?>">Proceed</a>.
    <?php
}
else
{
?><!DOCTYPE html>
<title>Validate by Referer</title>
<meta charset=UTF-8>
<link rel="stylesheet" href="/i/style.css">
<script type="text/javascript">
var ref = window.referrer || document.referrer;
if (ref)
{
    window.location = '/?url=' + escape(ref);
    document.write('<h1>Validating…</h1><div style="display:none">');
}
</script>
<h1>Validate by Referrer</h1>
<p class="invalid">Unable to validate — no referrer information received.
<p>Proceed to the <a href="/">hCard Validator</a> and enter the address manually.</p>
<?php
}