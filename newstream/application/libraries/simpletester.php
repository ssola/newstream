<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

$libraryDir = APPPATH . 'libraries/simpletest';

if(!is_dir($libraryDir))
    exit("Simpletest must be located in \"$libraryDir\"");

require_once $libraryDir . '/unit_tester.php';
require_once $libraryDir . '/mock_objects.php';
require_once $libraryDir . '/collector.php';

class SimpleTester
{
    /**
    * What reporter should be used for display.
    * Could be either HtmlReporter, SmallReporter, MinimalReporter or ShowPasses.
    */
    public $Reporter = 'MinimalReporter';

    private $testDir;
    private $testTitle;
    private $fileExtension;

    public function __construct($params = false)
    {
        $ci =& get_instance();

        $ci->config->load('simpletester');
		echo $ci->config->item("autorun");
        if($params == false) {
            $params['runFromIPs'] = $ci->config->item('runFromIPs');
            $params['testDir'] = $ci->config->item('testDir');
            $params['fileExtension'] = $ci->config->item('fileExtension');
            $params['autorun'] = $ci->config->item('autorun');
            $params['reporter'] = $ci->config->item('reporter');
            $params['testTitle'] = $ci->config->item('testTitle');
        }

        if(isset($params['runFromIPs']) && strpos($params['runFromIPs'], $ci->input->server('SERVER_ADDR') === FALSE))
        {
            // Tests won't be run automatically from this IP.
            $params['autorun'] = FALSE;
        }

        // Check if call was an AJAX call. No point in running test
        // if not seen and may break the call.
        $header = 'CONTENT_TYPE';
        if(!empty($_SERVER[$header])) {
            // @todo Content types could be placed in config.
            $ajaxContentTypes = array('application/x-www-form-urlencoded', 'multipart/form-data');
            foreach ($ajaxContentTypes as $ajaxContentType) {
                if(false !== stripos($_SERVER[$header], $ajaxContentType))
                {
                    $params['autorun'] = FALSE;
                    break;
                }
            }
        }

        $this->testDir = $params['testDir'];
        $this->testTitle = $params['testTitle'];
        $this->fileExtension = $params['fileExtension'];

        if(isset($params['reporter']))
            $this->Reporter = $params['reporter'];

        if($params['autorun'] == TRUE)
            echo $this->Run();
    }

    /**
    * Run the tests, returning the reporter output.
    */
    public function Run()
    {
        // Save superglobals that might be tested.
        if(isset($_SESSION)) $oldsession = $_SESSION;
        $oldrequest = $_REQUEST;
        $oldpost = $_POST;
        $oldget = $_GET;
        $oldfiles = $_FILES;
        $oldcookie = $_COOKIE;

        $test_suite = new TestSuite($this->testTitle);

        // Add files in tests_dir
        if(is_dir($this->testDir))
        {
            if($dh = opendir($this->testDir))
            {
                while(($file = readdir($dh)) !== FALSE)
                {
                    // Test if file ends with php, then include it.
                    if(substr($file, -(strlen($this->fileExtension)+1)) == '.' . $this->fileExtension)
                    {
                        $test_suite->addFile($this->testDir . "/$file");
                    }
                }
                closedir($dh);
            }
        }

        // Start the tests
        ob_start();
        $test_suite->run(new $this->Reporter);
        $output_buffer = ob_get_clean();

        // Restore superglobals
        if(isset($oldsession)) $_SESSION = $oldsession;
        $_REQUEST = $oldrequest;
        $_POST = $oldpost;
        $_GET = $oldget;
        $_FILES = $oldfiles;
        $_COOKIE = $oldcookie;

        return $output_buffer;
    }
}

// Html output reporter classes //////////////////////////////////////

/**
* Display passes
*/
class ShowPasses extends HtmlReporter
{
    function ShowPasses()
    {
        $this->HtmlReporter();
    }

    function paintPass($message)
    {
        parent::paintPass($message);
        print "<span class=\"pass\">Pass</span>: ";
        $breadcrumb = $this->getTestList();
        array_shift($breadcrumb);
        print implode("-&gt;", $breadcrumb);
        print "-&gt;$message<br />\n";
    }

    function _getCss()
    {
        return parent::_getCss() . ' .pass {color:green;}';
    }
}

/**
* Displays a tiny div in upper right corner when ok
*/
class SmallReporter extends HtmlReporter
{
    var $test_name;

    function ShowPasses()
    {
        $this->HtmlReporter();
    }

    function paintHeader($test_name)
    {
        $this->test_name = $test_name;
    }

    function paintFooter($test_name)
    {
        if($this->getFailCount() + $this->getExceptionCount() == 0)
        {
            $text = $this->getPassCount() . " tests ok";
            print "<div style=\"background-color:#F5FFA8; text-align:center; right:10px; top:30px; border:2px solid green; z-index:10; position:absolute;\">$text</div>";
        }
        else
        {
            parent::paintFooter($test_name);
            print "</div>";
        }
    }

    function paintFail($message)
    {
        static $header = FALSE;
        if(!$header)
        {
            $this->newPaintHeader();
            $header = TRUE;
        }
        parent::paintFail($message);
    }

    function newPaintHeader()
    {
        $this->sendNoCacheHeaders();
        print "<style type=\"text/css\">\n";
        print $this->_getCss() . "\n";
        print "</style>\n";
        print "<h1 style=\"background-color:red; color:white;\">$this->test_name</h1>\n";
        print "<div style=\"background-color:#FBFBF0;\">";
        flush();
    }
}

/**
* Minimal only displays on error
*/
class MinimalReporter extends SmallReporter
{
    function paintFooter($test_name)
    {
        if($this->getFailCount() + $this->getExceptionCount() != 0)
        {
            parent::paintFooter($test_name);
            print "</div>";
        }
    }
}