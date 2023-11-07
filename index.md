---
title: Passage
---
This is a PHP based static site generator. It is highly customizable and easy. It uses PHP for creating templates, hence no need to learn a new templating language (as PHP itself is a templating language).

It uses Parsedown and Symphony YAML component to generate static html files.

# Usage
1. Write your content in Markdown (.md) files with the directory structure as you desire. The folder path and name of the file is used as url.
2. Create template in `_template` folder. The `layout` metadata
3. Add metadata as YAML frontmatter in your files as per requirement.
4. Run `_engine/build.php` to build site. The files will be created in `_site` folder.

## Config

The sitewide config is stored in `config.yml` file. The following config options are available.
```
base: # The base for urls
title: # Site title (Alternatively if list line of the content is a heading it is used as title.)
support: [md, txt, html, htm] # File extensions which will be parsed
buildall: true # Whether to build pages without frontmatter
output-dir: _site # Output directory
default-category: General 
footer:  # Footer text, Implemented by template
date-format: 'd F, Y' # Date display format, Implemented by template
```

## YAML metadata

The information about a page is to be stored in yaml frontmatter

```
title: # Title of the page
layout: # Layout to be displayed _template/<value>.php is called for parsing
date:2023-10-30 # Date, optional
category: # category of the page
tags: # Tags array, optional
- tag1
- tag2
```

## Tags

Apart from tags metadata, tags are also detected from words stating with '#' for e.g. `#tag1`;

A line containing only tags is removed while rendering.

## Drafts

A file tagged with `draft` will not be rendered.

# Template

When rendering the `_template/<template_name>.php` file is called. The `_template` folder is placed in the same folder in which `_engine` is placed.

`$site` variable contains all metadata contained in `config.yml` which is at the root folder in which `_engine` is placed. The variables are key of this array.

`$page` variable contains all metadata contained in frontmatter of a page. The variables are key of this array.