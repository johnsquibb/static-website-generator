<?php declare(strict_types=1);
/**
 * Generator takes raw HTML content exported from a Markdown editor, and generates static HTML web pages with universal
 * base template, header, and footer. Directory structure can be organized by specifying manifest directives in the
 * JSON configuration.
 *
 * This is a work-in-progress built with minimalism in mind. It is shared here in the hopes that it may be useful to
 * others (and mostly my future self). The goal is to keep the generator a lightweight, single-script application, free
 * of external dependencies and superficial infrastructure.
 *
 * TODO: We can skip the export to HTML from editor by implementing our own basic Markdown parser.
 * TODO: Implement custom styles (currently using default CSS from MarkdownPad 2)
 * TODO: Include sample directory structure in github and include README.md to facilitate rapid setup.
 */
// The root is the directory parent of the directory that contains this generator.
$projectRootPath = dirname(__DIR__);

// JSON configuration defines templates, manifest for generation.
// It can be generated using one of the commands to this script (see help() function).
$projectConfigPath = $projectRootPath . DIRECTORY_SEPARATOR . 'config.json';

// Parse CLI args, run basic commands.
$args = array_slice($argv, 1);
switch (count($args)) {
    case 0:
        help();
        break;
    case 1:
        switch ($args[0]) {
            case 'init':
                if (!file_exists($projectConfigPath)) {
                    initProject();
                } else {
                    die('Configuration already exists!');
                }
                break;
            case 'generate':
                generateFromManifest();
                break;
            default:
                help();
        }
        break;
    case 2:
        buildTemplate($args);
        break;
}

// Display help.
function help(): void
{
    echo "\n", 'Usage:', "\n\n";
    echo 'Initialize project config: `php generator.php init`', "\n";
    echo 'Generate from source file: `php generator.php source-file destination-file`', "\n";
    echo 'Generate all from config.manifest list: `php generator.php generate`', "\n\n";
    exit;
}

// Initialize project configuration.
function initProject()
{
    global $projectConfigPath;

    // Basic configuration to build container with universal header and footer.
    // index and error pages defined in manifest satisfy basic requirements for serving content from AWS S3 bucket.
    $config = [
        'template' => [
            'base' => 'template/layout/base.html',
            'header' => 'template/layout/header.html',
            'footer' => 'template/layout/footer.html',
        ],
        'manifest' => [
            // Keys are source files relative to project root.
            // Values are destination files stored in 'public' directory.
            'html/index.html' => 'index.html',
            'html/error.html' => 'error.html'
        ],
    ];

    // Put the JSON configuration in the root project directory.
    file_put_contents($projectConfigPath, json_encode($config, JSON_PRETTY_PRINT));
}

// Render source file into base template with optional universal header/footer.
function buildTemplate(array $args): void
{
    global $projectRootPath;
    $config = loadConfig();

    // Load the base template.
    $baseTemplateFilePath = $projectRootPath . DIRECTORY_SEPARATOR . $config['template']['base'];
    if (!file_exists($baseTemplateFilePath)) {
        die("Base template is missing in template directory!");
    }
    $baseTemplate = file_get_contents($baseTemplateFilePath);

    // Load the header template.
    $headerTemplateFilePath = $projectRootPath . DIRECTORY_SEPARATOR . $config['template']['header'];
    $headerTemplate = '';
    if (file_exists($headerTemplateFilePath)) {
        $headerTemplate = file_get_contents($headerTemplateFilePath);
    }

    // Load the footer template.
    $footerTemplateFilePath = $projectRootPath . DIRECTORY_SEPARATOR . $config['template']['footer'];
    $footerTemplate = '';
    if (file_exists($footerTemplateFilePath)) {
        $footerTemplate = file_get_contents($footerTemplateFilePath);
    }

    // Load the specified source html.
    $contentFilePath = $projectRootPath . DIRECTORY_SEPARATOR . $args[0];
    if (!file_exists($contentFilePath)) {
        die("File {$contentFilePath} does not exist.");
    }
    $content = file_get_contents($contentFilePath);
    $body = parseBodyFromContent($content);

    // Add header, footer, html to base template.
    $generated = str_replace('{{ header }}', $headerTemplate, $baseTemplate);
    $generated = str_replace('{{ footer }}', $footerTemplate, $generated);
    $generated = str_replace('{{ body }}', $body, $generated);

    // Save the generated template to the public directory.
    $publicDirPath = $projectRootPath . DIRECTORY_SEPARATOR . 'public';
    if (!file_exists($publicDirPath)) {
        die("Public directory is missing in project root!");
    }

    $parts = explode('/', $args[1]);
    $fileName = array_pop($parts);
    $fileDirectory = $publicDirPath . DIRECTORY_SEPARATOR . implode('/', $parts);
    $generatedFilePath = $fileDirectory . DIRECTORY_SEPARATOR . $fileName;

    // Create directory structure if it does not exist.
    if (!file_exists($fileDirectory)) {
        mkdir($fileDirectory, 0700, true);
    }

    file_put_contents($generatedFilePath, $generated);
}

// Extract just the inner HTML from <body> tag in source HTML document.
// This allows extraction of content for inclusion in base template with universal header/footer.
// Most markdown editors (e.g. MarkdownPad 2) will export the complete HTML document.
function parseBodyFromContent(string $content): string
{
    $d = new \DOMDocument;
    $mock = new \DOMDocument;
    $d->loadHTML($content);
    $body = $d->getElementsByTagName('body')->item(0);

    foreach ($body->childNodes as $child) {
        $mock->appendChild($mock->importNode($child, true));
    }

    return $mock->saveHTML();
}

// Load the JSON configuration and unmarshal it.
function loadConfig(): array
{
    global $projectConfigPath;

    if (!file_exists($projectConfigPath)) {
        die('Missing project config.json. Run `php generator.php init` to create a basic configuration.');
    }

    return json_decode(file_get_contents($projectConfigPath), true);
}

// Build templates from manifest list defined in configuration.
function generateFromManifest(): void
{
    $config = loadConfig();

    foreach ($config['manifest'] as $source => $target) {
        buildTemplate([$source, $target]);
    }
}
