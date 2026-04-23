<?php
/**
 * extract.php - Bridge between PHP constants and Makefile
 */
require_once __DIR__ . '/env.php';

// Get the key from the CLI argument
$key = $argv[1] ?? '';

// Check if the constant is defined and echo its value
if (defined($key)) {
    echo constant($key);
} else {
    exit(1);
}