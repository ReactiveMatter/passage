<?php
require 'vendor/autoload.php'; // Include the Composer autoloader for Parsedown

$base = dirname(__DIR__);
define('DS', DIRECTORY_SEPARATOR );

use Symfony\Component\Yaml\Yaml;

mb_internal_encoding('UTF-8');

$parsedown = new Parsedown();

# Parse config.yml and store it in $site
$site = Yaml::parseFile($base.DS."config.yml");

if(isset($site['base']))
{
    $site['base'] = trim($site['base'], "/");
    if(strlen($site['base']) > 0 )
    { $site['base'] = "/".$site['base']; }
}
else
{
    $site['base'] = '';
}


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

// Function to parse one file md (or supported file). Outputs $page variable
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

        $page['title']= trim($page['title']);

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
        /*Remove lines that contains only tags */
        $content = preg_replace('/^(?:\s*#[a-zA-Z0-9_-]+\s*?)*$/m', '', $content);
        /* Add trailing slashes to all internal links for consistence */
        $content = preg_replace_callback('/\[(.*?)\]\((.*?)\)/', function($matches) {
                $link = $matches[2];
                $path_info = pathinfo($link);

                // Check if the link has no extension
                if (!isset($path_info['extension'])) 
                {
                    $link = rtrim($link, '/') . '/';
                }

        return '[' . $matches[1] . '](' . $link . ')';
        }, $content);

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
        /*Adding a trailing slash to be consistent in URL scheme */
        $slug = rtrim($slug,"/")."/";
        $page['slug'] = $slug;

        /*Note on slug 
        Slug is a relative url with trailing slash but not preceded by a slash
        */

        $fileContent = "";
        
        /* Making page as default layout */
        if(isset($site['default-layout']))
        {   
            $layout = $site['default-layout'];
        }
        else
        {
            $layout = "page";
        }
        

        if(isset($page['layout']))
        {   
            $file = $base.DS."_template".DS.$page['layout'].".php";
            if (file_exists($file) && is_readable($file)) 
            {
                $layout = $page['layout'];
            }
             
        }
        else
        {   
            /* If page layout is not set, the folder name will be the layout */
            $slug_parts = explode('/', trim($slug, "/"));
            if(sizeof($slug_parts)>1)
            {
                $folder_name = trim($slug_parts[sizeof($slug_parts) - 2]);
                $file = $base.DS."_template".DS.$folder_name.".php";
                if (file_exists($file) && is_readable($file)) 
                {
                    $layout = $folder_name;
                }
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

/* Create HTML file from one $page variable */
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

    echo "Built ".$page['slug']."index.html"."\n";
    ob_start();
    include($base.DS."_template/".$page['layout'].".php");
    $fileContent = ob_get_clean();
    $file = fopen($destination, "w");
    fwrite($file, $fileContent);
    fclose($file);    
}

/* Scan for all files in the project directory. Parses each file and pushes in $pages variable */
function scan($dir)
{  global $base, $site, $pages, $counter;
    
    $entries = scandir($dir);
    foreach ($entries as $entry) {
        if ($entry !== '.' && $entry !== '..' && !str_starts_with($entry, "_")) 
        {   
            $path = $dir.DS.$entry;
            if (is_file($path)) {
                //Parse file and push to $pages
                $page = parse($path);
                if($page)
                {   
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


/* Recursively generate HTML files from $pages variable */
function generateHTMLFiles($pages)
{
    for($i=0; $i < sizeof($pages); $i++) {
        // echo "Building no. ".$i." - ".$pages[$i]['slug']."\n";
        createHTMLFile($pages[$i]);
    }

}

function sortByDate($pages)
{
     usort($pages, function($a, $b){
        if(!isset($a['date']))
        {
            $a['date'] = -1;
        }

        if(!isset($b['date']))
        {
            $b['date'] = -1;
        }

        return $b['date'] - $a['date'];
    });

     return $pages;
}

   
/* Generate feed from a feed.php file in _template (if it exists) */
function generateFeed()
{
    global $base, $pages, $site;

    $file = $base.DS."_template".DS."feed".".php";
    if (file_exists($file) && is_readable($file)) 
    {
        include($base.DS."_template".DS."feed".".php");
    }
}

/* Copy all css, js files from _template to _site/assets */
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
generateFeed();
copyAssets($base.DS."_template");
