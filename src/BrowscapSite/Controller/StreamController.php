<?php

namespace BrowscapSite\Controller;

use BrowscapSite\Tool\RateLimiter;

class StreamController
{
    /**
     * @var \BrowscapSite\Tool\RateLimiter
     */
    protected $rateLimiter;

    public function __construct(RateLimiter $rateLimiter)
    {
        $this->rateLimiter = $rateLimiter;
    }

    protected function failed($status, $message)
    {
        header("HTTP/1.0 {$status}");
        echo $message;
        die();
    }

    public function indexAction()
    {
        // @todo - this is horrendous
        if (!isset($_GET['q'])) {
            $this->failed('400 Bad Request', 'The version requested could not be found');
        }

        $browscapVersion = strtolower($_GET['q']);

        switch ($browscapVersion)
        {
            case 'browscapini':
                $file = "browscap.ini";
                break;
            case 'full_browscapini':
                $file = "full_asp_browscap.ini";
                break;
            case 'lite_browscapini':
                $file = "lite_asp_browscap.ini";
                break;
            case 'php_browscapini':
                $file = "php_browscap.ini";
                break;
            case 'full_php_browscapini':
                $file = "full_php_browscap.ini";
                break;
            case 'lite_php_browscapini':
                $file = "lite_php_browscap.ini";
                break;
            case 'browscapxml':
                $file = "browscap.xml";
                break;
            case 'browscapcsv':
                $file = "browscap.csv";
                break;
            case 'browscapzip':
                $file = "browscap.zip";
                break;
            default:
                $this->failed('404 Not Found', 'The version requested could not be found');
        }

        $buildDirectory = __DIR__ . '/../../../build/';

        $fullpath = $buildDirectory . $file;

        if (!file_exists($fullpath)) {
            $this->failed('500 Internal Server Error', 'The original file for the version requested could not be found');
        }

        if (!$this->rateLimiter->checkLimit($_SERVER['REMOTE_ADDR']))
        {
            $this->failed('429 Too Many Requests', 'Rate limit exceeded. Please try again later.');
        }

        $this->rateLimiter->logDownload($_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $browscapVersion);

        header("HTTP/1.0 200 OK");
        header("Cache-Control: public");
        header("Content-Type: application/zip");
        header("Content-Transfer-Encoding: Binary");
        header("Content-Length:" . filesize($fullpath));
        header("Content-Disposition: attachment; filename=" . $file);
        readfile($fullpath);
        die();
    }
}
