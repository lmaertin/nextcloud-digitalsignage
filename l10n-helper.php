#!/usr/bin/env php
<?php
/**
 * Digital Signage - Translation Helper Script
 *
 * Helps with adding new languages and validating translations
 *
 * Usage:
 *   php l10n-helper.php add fr    # Add new language French
 *   php l10n-helper.php validate  # Check all translations
 *   php l10n-helper.php list      # Show available languages
 */

if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

$l10nDir = __DIR__ . '/l10n';
$baseFile = $l10nDir . '/en.json';

// Color codes for CLI output
class Colors {
    const RESET = "\033[0m";
    const GREEN = "\033[32m";
    const RED = "\033[31m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
}

function printSuccess($msg) {
    echo Colors::GREEN . "✓ " . $msg . Colors::RESET . "\n";
}

function printError($msg) {
    echo Colors::RED . "✗ " . $msg . Colors::RESET . "\n";
}

function printWarning($msg) {
    echo Colors::YELLOW . "⚠ " . $msg . Colors::RESET . "\n";
}

function printInfo($msg) {
    echo Colors::BLUE . "ℹ " . $msg . Colors::RESET . "\n";
}

function listLanguages() {
    global $l10nDir;
    
    echo "\n" . Colors::BLUE . "Available Languages:" . Colors::RESET . "\n";
    
    $files = glob($l10nDir . '/*.json');
    $languages = [
        'de' => 'Deutsch',
        'en' => 'English',
        'fr' => 'Français',
        'es' => 'Español',
        'it' => 'Italiano',
        'nl' => 'Nederlands',
        'pt' => 'Português',
    ];
    
    foreach ($files as $file) {
        $locale = basename($file, '.json');
        $langName = $languages[$locale] ?? 'Unknown';
        
        $data = json_decode(file_get_contents($file), true);
        $count = count($data);
        
        echo "  $locale ($langName) - $count keys\n";
    }
    echo "\n";
}

function validateTranslations() {
    global $l10nDir, $baseFile;
    
    echo "\n" . Colors::BLUE . "Validating translations..." . Colors::RESET . "\n";
    
    if (!file_exists($baseFile)) {
        printError("Base file not found: $baseFile");
        return false;
    }
    
    $baseData = json_decode(file_get_contents($baseFile), true);
    $baseKeys = array_keys($baseData);
    
    printInfo("Base file has " . count($baseKeys) . " keys");
    
    $files = glob($l10nDir . '/*.json');
    $allValid = true;
    
    foreach ($files as $file) {
        $locale = basename($file, '.json');
        
        if ($locale === 'en') continue;
        
        $data = json_decode(file_get_contents($file), true);
        $fileKeys = array_keys($data);
        
        // Check for missing keys
        $missing = array_diff($baseKeys, $fileKeys);
        
        // Check for extra keys
        $extra = array_diff($fileKeys, $baseKeys);
        
        if (!empty($missing) || !empty($extra)) {
            printWarning("Language '$locale' has inconsistencies:");
            $allValid = false;
            
            if (!empty($missing)) {
                echo "  Missing keys (" . count($missing) . "):\n";
                foreach (array_slice($missing, 0, 5) as $key) {
                    echo "    - $key\n";
                }
                if (count($missing) > 5) {
                    echo "    ... and " . (count($missing) - 5) . " more\n";
                }
            }
            
            if (!empty($extra)) {
                echo "  Extra keys (" . count($extra) . "):\n";
                foreach (array_slice($extra, 0, 5) as $key) {
                    echo "    - $key\n";
                }
                if (count($extra) > 5) {
                    echo "    ... and " . (count($extra) - 5) . " more\n";
                }
            }
        } else {
            printSuccess("Language '$locale' is complete (" . count($fileKeys) . " keys)");
        }
    }
    
    echo "\n";
    return $allValid;
}

function addLanguage($locale) {
    global $l10nDir, $baseFile;
    
    $targetFile = $l10nDir . '/' . $locale . '.json';
    
    if (file_exists($targetFile)) {
        printError("Language file already exists: $targetFile");
        return false;
    }
    
    if (!file_exists($baseFile)) {
        printError("Base file not found: $baseFile");
        return false;
    }
    
    $baseData = json_decode(file_get_contents($baseFile), true);
    
    // Create new file with same keys but empty values (for translation)
    $newData = [];
    foreach ($baseData as $key => $value) {
        $newData[$key] = ""; // Empty for translator to fill
    }
    
    // Save with pretty formatting
    $json = json_encode($newData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    if (file_put_contents($targetFile, $json) === false) {
        printError("Could not write to: $targetFile");
        return false;
    }
    
    printSuccess("Created language file: $targetFile");
    printInfo("Please translate all keys in the new file");
    
    return true;
}

// Main
if ($argc < 2) {
    echo "Digital Signage - Translation Helper\n";
    echo "Usage: php l10n-helper.php <command> [args]\n\n";
    echo "Commands:\n";
    echo "  list              - List available languages\n";
    echo "  validate          - Validate all translations\n";
    echo "  add <locale>      - Add new language (e.g., 'fr' for French)\n";
    echo "\n";
    exit(1);
}

$command = $argv[1];

switch ($command) {
    case 'list':
        listLanguages();
        break;
    case 'validate':
        validateTranslations();
        break;
    case 'add':
        if (!isset($argv[2])) {
            printError("Please specify locale code (e.g., 'fr', 'es', 'it')");
            exit(1);
        }
        addLanguage($argv[2]);
        break;
    default:
        printError("Unknown command: $command");
        exit(1);
}
