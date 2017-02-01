<?php

/**
 * Runs validator on test-case files in tests/ and examples/
 */

require_once "class/loader.php";
require_once "class/validationresult.php";
require_once "class/vcard.php";
require_once "class/hcardvalidator.php";
require_once "class/controller.php";
require_once "PHPTAL.php";
require_once "PHPTAL/GetTextTranslator.php";

class AutoTest
{
    function __construct()
    {
        $this->validator = new hCardValidator();
    }

    private $unexpected_errors = 0;

    function testFile($file, array $expected = [])
    {
        if (!is_file($file)) return;

        echo '<div><h2 style="display:inline;font-size:0.8em;margin:0.5em 0;color:#bbb;font-weight:normal">'.$file.'</h2> ';
        $result = $this->validator->validateFile($file);

        $this->addExpectedErrorsFromFile($file, $expected);

        $this->checkResult($result,$expected);

        foreach($expected as $notfound => $x)
        {
            if (!$x) continue;

            echo "<p>Expected error <strong>$notfound not found".($x > 1 ? " ×$x":"")."</strong></p>";
            foreach($result->vcards as $vcard)
            {
                echo '<pre style="font-size:11px;line-height:9px">'.htmlspecialchars(print_r($vcard->data,true)).'</pre>';
                $this->unexpected_errors++;
            }
        }
        echo '</div>';

        if ($this->unexpected_errors > 15) throw new Exception("Enough errors");
    }

    private function addExpectedErrorsFromFile($file, array &$expected)
    {
        if (preg_match_all('/@expect:?\s*([^-\n;]+)/',file_get_contents($file),$m))
        {
            foreach($m[1] as $line)
            {
                foreach(preg_split('/[ ,]+/',$line,NULL,PREG_SPLIT_NO_EMPTY) as $errclass)
                {
                    if(isset($expected[$errclass])) $expected[$errclass]++; else $expected[$errclass] = 1;
                }
            }
        }
    }

    /**
     * Compares errors in $result with counters in $expected and displays result
     *
     * @param $expected passed by reference to decrement counters in recursively checked hcards
     */
    private function checkResult($result, array &$expected)
    {
        $hadUnexpectedErrors = false;

        foreach($result->errors as $k => $e)
        {
            if (isset($expected[ $e['class'] ]))
            {
                if ($expected[ $e['class'] ])
                {
                    $result->errors[$k]['expected'] = true;
                    echo '<span style="color:#ccc;font-size:0.6em">'.$e['class'].' #'.$expected[ $e['class'] ].'</span> ';
                     $expected[ $e['class'] ]--;
                }
                else if (isset($expected[ $e['class'] ]))
                {
                    echo '<span>'.$e['class'].' - occured too many times</span> ';
                }
            }
        }

        foreach($result->errors as $e)
        {
            if (empty($e['expected']))
            {
                $hadUnexpectedErrors = true;

                $args = $e['args'];
                array_unshift($args,str_replace('%s','<var style="color:#060">%s</var>',$e['message']));

                echo '<p><strong>'.ucwords($e['type']).' ['.$e['class'].']</strong>: '.call_user_func_array('sprintf',$args)." ".$e['location']."</p>";
            }
        }

        foreach($result->vcards as $vcard)
        {
            if ($this->checkResult($vcard->result, $expected))
            {
                $hadUnexpectedErrors = true;
                echo '<pre style="font-size:11px;line-height:9px">'.htmlspecialchars(print_r($vcard->data,true)).'</pre>';
                $this->unexpected_errors++;
            }
        }

        return $hadUnexpectedErrors;
    }

    function testAll()
    {
        header("Content-Type:text/html;charset=UTF-8");
        ?><h1>Selftest <?php echo time() ?></h1><?php

        $start = microtime(true);
        $filesnum = 0;
        try
        {
            $files = glob("tests/uf/*");
            foreach($files as $file)
            {
                $this->testFile($file);
                $filesnum++;
            }

            $files = glob("tests/*");
            foreach($files as $file)
            {
                // tests use filename same as error ID,
                // so I don't need to put @expected comment in every one
                $expected_error_name = basename($file, '.html');
                $this->testFile($file, [$expected_error_name =>1]);
                $filesnum++;
            }

            $files = glob("examples/*");
            foreach($files as $file)
            {
                $this->testFile($file);
                $filesnum++;
            }
        }
        catch(Exception $e)
        {
            echo('<h1>'.htmlspecialchars($e->getMessage()).'</h1><pre>'.$e.'</pre>');
        }
        $total = max(0.001,microtime(true) - $start);

        echo '<p>Done '.$filesnum.' files at '.round($filesnum / $total).' files/s</p>';
    }
}

$c = new Controller();
$c->init(true);

$t = new AutoTest();
$t->testAll();
