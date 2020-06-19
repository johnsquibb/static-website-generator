# Static Markdown-to-HTML generator.
Generate static web pages from exported Markdown HTML.
This is a work-in-progress built with minimalism in mind, and is deliberately designed to be simple, lightweight, and free of external dependencies and modules.

## TODO
* Skip the export to HTML from editor by implementing a basic Markdown parser.
* Include sample directory structure.

## How it Works
Generator takes raw HTML content exported from a Markdown editor, and generates static HTML web pages with universal base template, header, and footer. Directory structure can be organized by specifying manifest directives in the JSON configuration.

## Usage

### Display Help
`php generator.php help`

### Initialize project configuration 
`php generator.php init`

This will generate a starting configuration file:

	{
	    "template": {
	        "base": "template\/layout\/base.html",
	        "header": "template\/layout\/header.html",
	        "footer": "template\/layout\/footer.html"
	    },
	    "manifest": {
	        "html\/index.html": "index.html",
	        "html\/about.html": "about.html",
	        "html\/error.html": "error.html",
	        "html\/error\/unofficial-error.html": "error\/unofficial-error.html",
	        "html\/resources-for-building-static-websites.html": "resources-for-building-static-websites.html"
	    }
	}

### Generate from source file
`php generator.php source-file destination-file`

### Generate all from configuration manifest.
`php generator.php generate`
