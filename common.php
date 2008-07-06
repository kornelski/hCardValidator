<?php

$catalogfile = dirname(__FILE__).'/xmlcatalog.xml';
putenv('XML_CATALOG_FILES='.$catalogfile);
$_ENV['XML_CATALOG_FILES'] = $catalogfile;

date_default_timezone_set('UTC');

function __autoload($class)
{
  require_once "class/".strtolower($class).".php";
}


function myhtmlspecialchars($str)
{
    return htmlspecialchars(cleanstring($str));
}

function cleanstring($str, $charset = 'UTF-8')
{
    $str = @iconv($charset,'UTF-8//IGNORE',$str);        
    $str = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+|\xC2[\x80-\x9F]/','',$str);
    return $str;
}

require_once "phptal/PHPTAL.php";

if (!function_exists('bindtextdomain')) throw new Exception("Gettext not installed");

putenv("LC_ALL=en_US.UTF-8");
setlocale(LC_ALL, array('en','en_US.UTF-8','en_GB.UTF-8','pl','pl_PL'));
bindtextdomain("props", "./locale/");
bindtextdomain("errors", "./locale/");


function readablePropertyName($propname)
{
    return dgettext("props",$propname);
}


if (!function_exists('apc_store') && !function_exists('apc_fetch'))
{
    function apc_store($var,$val)
    {
        file_put_contents('/tmp/fakeapc'.md5($var),$val);
    }
    
    function apc_fetch($var)
    {
        return @file_get_contents('/tmp/fakeapc'.md5($var));
    }
}

if (!function_exists('sys_get_temp_dir'))
{
function sys_get_temp_dir()
{
return '/tmp/';
}
}
