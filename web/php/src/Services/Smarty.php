<?php
// src/Smarty.php

declare(strict_types=1);

namespace App;

class Smarty
{
    /**
     * Renders a single template string with {{{var}}} placeholders
     *
     * @param string $template  Template string (e.g. "<b>{{{title}}}</b>")
     * @param array  $vars      Variables to replace
     * @return string
     */
    public function render(string $template, array $vars = []): string
    {
        return preg_replace_callback(
            '/\{\{\{([^{}]+)\}\}\}/',
            function (array $matches) use ($vars): string {
                $key = trim($matches[1]);

                // Simple dot notation support (user.name)
                if (str_contains($key, '.')) {
                    $parts = explode('.', $key);
                    $value = $vars;
                    foreach ($parts as $part) {
                        if (is_array($value) && array_key_exists($part, $value)) {
                            $value = $value[$part];
                        } else {
                            return '';
                        }
                    }
                    return (string) $value;
                }

                return array_key_exists($key, $vars)
                    ? (string) $vars[$key]
                    : '';
            },
            $template
        );
    }

    /**
     * Renders a partial template for each item in the collection
     * and concatenates the results.
     *
     * @param string $partialTemplate   The template string for one item
     * @param array  $items             Array of associative arrays (data rows)
     * @return string                   Concatenated rendered partials
     */
    public function partial(string $partialTemplate, array $items): string
    {
        $result = '';

        foreach ($items as $item) {
            // Each item should be an array (or object with array-like access)
            if (!is_array($item)) {
                continue; // or throw exception - your choice
            }

            $result .= $this->render($partialTemplate, $item);
        }

        return $result;
    }

    /**
     * Render and output directly
     */
    public function display(string $template, array $vars = []): void
    {
        echo $this->render($template, $vars);
    }
}