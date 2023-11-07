<?php
require 'vendor/autoload.php'; // Include the Composer autoloader for Parsedown
require 'ParsedownExtra.php';

$base = dirname(__DIR__);
define('DS', DIRECTORY_SEPARATOR );

use Symfony\Component\Yaml\Yaml;

mb_internal_encoding('UTF-8');

$parsedown = new Parsedown();

$site = Yaml::parseFile($base.DS."config.yml");

$site['base'] = rtrim($site['base'], "/");
$supported_extensions = $site['support'];
$site['default-title'] = 'Untitled';

if(!isset($site['output-dir']))
{
    $site['output-dir'] = "_site";
}

if(!isset($site['date-format']))
{
    $site['date-format'] = "Y-m-d";
}

define("ASSETS", $site['base']."/assets");

echo "Building at ".$site['base']."\n";
echo "Assests are at ".ASSETS."\n";



$pages = [];

// Function to serve a Markdown file as HTML
function parse($file) {

    global $parsedown, $site;
    global $base, $supported_extensions, $front,  $http_base;

    $ext = pathinfo($file, PATHINFO_EXTENSION);
    $filename = pathinfo($file, PATHINFO_FILENAME);


    if (file_exists($file) && is_readable($file)) 
    {
            if(!in_array($ext, $supported_extensions))
            {
                return false;
            }
        $content = file_get_contents($file);

        $frontMatter = [];
        if (preg_match('/^---\s*\n(.*?\n)---\s*\n/sm', $content, $matches)) {
            $frontMatter = Yaml::parse($matches[1]);
            $content = substr($content, strlen($matches[0]));
        }

        $page = $frontMatter;

        if(!isset($page['title']))
        {
            if (preg_match('/^# (.+?)$/m', $content, $matches)) {
                $page['title'] = $matches[1];
                $content = substr($content, strlen($matches[0]));
            }
            else
            {
                $page['title'] = $site['default-title'];
            }
            
        }


        /* Extract tags */

        preg_match_all('/(?<!\\\\)\s#\w+/', $content, $tagmatches);
    
        // Get tags from the markdown
        $tags = array_map(function($tag) {
            $tag = trim($tag);
            $tag = ltrim($tag, '#');
            return $tag;
        }, $tagmatches[0]);
    
        if(!isset($page['tags']))
        {
            $page['tags'] = [];
        }
        else if (!is_array($page['tags'])) {
            $page['tags'] = (array)$page['tags'];
        }
        
        $page['tags'] = array_merge($page['tags'], $tags);
        $page['tags'] = array_map('strtolower', $page['tags']);
        $page['tags'] = array_unique($page['tags']);
        $content = preg_replace('/^(?:\s*#\w+\s*?)*$/m', '', $content);
        $content = $parsedown->text($content);
        $page['content']= trim($content, " \n\r\t");

         if(!$site['buildall'])
        {   
             /* Only parse if file has front matter */
            if(sizeof($page) == 0) {
            return;}
        }

         $slug = str_replace($base,"", $file);

        if($filename=="index")
        {       
            $slug = str_replace($filename.".".$ext, "", $slug);   
        }
        else
        {
            $slug = str_replace(".".$ext, "", $slug);
        }

        $slug = trim($slug, DS);
        $slug = str_replace(DS, "/",$slug);
        $page['slug'] = $slug;

    

        $fileContent = "";
        $layout = "page";

        if(isset($page['layout']))
        {   
            $file = $base.DS."_template".DS.$page['layout'].".php";
            if (file_exists($file) && is_readable($file)) 
            {
                $layout = $page['layout'];
            }
             
        }

        $page['layout'] = $layout;

        if(!isset($page['default-category']))
        {
            $page['default-category'] = "General";
        }

        if(!isset($page['category']))
        {
            $page['category'] = $page['default-category'];
        }

        return $page;
        

    } 

    return false;
}

function createHTMLFile($page)
{   
    global $base, $site, $pages;

    if(in_array('draft', $page['tags']))
    {
        return;
    }

    $destination = str_replace("/",DS, $page['slug']);
    $destination = trim($destination, DS);


    if (!is_dir($base.DS.$site['output-dir'].DS.$destination)) 
    {
        mkdir($base.DS.$site['output-dir'].DS.$destination, 0777, true); // true for recursive create
    }

    $destination = $base.DS.$site['output-dir'].DS.$destination.DS."index.html";

    echo "Built ".$page['slug']."\n";
    ob_start();
    include("../_template/".$page['layout'].".php");
    $fileContent = ob_get_clean();
    $file = fopen($destination, "w");
    fwrite($file, $fileContent);
    fclose($file);    
}

function scan($dir)
{  global $base, $site, $pages, $counter;
    
    $entries = scandir($dir);
    foreach ($entries as $entry) {
        if ($entry !== '.' && $entry !== '..' && !str_starts_with($entry, "_")) 
        {   
            $path = $dir.DS.$entry;
            if (is_file($path)) {
                $page = parse($path);
                if($page)
                {   
                    // echo "Pushing ".$page['slug']."\n";
                    // echo "Total pages pushed: ".sizeof($pages)."\n";
                    array_push($pages, $page);
                }
            } elseif (is_dir($path)) {
                 if(!str_contains($dir, "_engine") and !str_contains($dir, "_site")) 
                { 
                    scan($path);
                }
           }
        }
    } 
}


function generateHTMLFiles($pages)
{
    for($i=0; $i < sizeof($pages); $i++) {
        // echo "Building no. ".$i." - ".$pages[$i]['slug']."\n";
        createHTMLFile($pages[$i]);
    }

}

function copyAssets($dir)
{   global $base, $site;
    $entries = scandir($dir);
    foreach ($entries as $entry) {
        if ($entry !== '.' && $entry !== '..') 
        {  
            $path = $dir.DS.$entry;
            if (is_file($path)) 
            {
                if(pathinfo($path, PATHINFO_EXTENSION) == "js" || pathinfo($path, PATHINFO_EXTENSION) == "css")
                {
                    $ext = pathinfo($path, PATHINFO_EXTENSION);
                    $filename = pathinfo($path, PATHINFO_FILENAME);
             
                    if (!is_dir($base.DS.$site['output-dir'].DS."assets")) 
                    {
                        mkdir($base.DS.$site['output-dir'].DS."assets", 0777, true); // true for recursive create
                    }

                    copy($path, $base.DS.$site['output-dir'].DS."assets".DS.$filename.".".$ext);
                }
            } 
            else
            {
                copyAssets($path);
            }
        }
    }
}


/* Main functions */

scan($base);
generateHTMLFiles($pages);
copyAssets($base.DS."_template");
