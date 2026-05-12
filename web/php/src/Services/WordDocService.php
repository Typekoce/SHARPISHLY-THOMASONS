<?php
declare(strict_types=1);

namespace App\Services;

use ZipArchive;
use RuntimeException;

class WordDocService extends BaseService
{
    /**
     * Swaps placeholders in a .docx template and saves a new report.
     * * @param string $templateName e.g., 'cladding_template.docx'
     * @param array $data e.g., ['ADDRESS' => '123 High St', 'YEAR' => '1975']
     * @return string The filename of the generated report
     */
    public function generateReport(string $templateName, array $data): string
    {
        $templatePath = $this->location->templates($templateName);
        $outputFileName = 'Report_' . time() . '.docx';
        $outputPath = $this->location->reports($outputFileName);

        // 1. Ensure the template exists
        if (!file_exists($templatePath)) {
            throw new RuntimeException("Surveyor template not found at: $templatePath");
        }

        // 2. Ensure the output directory (storage/reports) exists
        $outputDir = dirname($outputPath);
        if (!is_dir($outputDir)) {
            if (!mkdir($outputDir, 0777, true)) {
                throw new RuntimeException("Cannot create reports directory: $outputDir");
            }
        }

        // 3. Copy template to the reports folder
        if (!copy($templatePath, $outputPath)) {
            throw new RuntimeException("Failed to initialize report file at: $outputPath");
        }

        // 4. Open the copied ZIP (docx)
        $zip = new ZipArchive();
        if ($zip->open($outputPath) === true) {
            // Main text in a Word doc is stored in word/document.xml
            $xmlContent = $zip->getFromName('word/document.xml');

            if ($xmlContent === false) {
                $zip->close();
                throw new RuntimeException("Could not find document.xml within the template.");
            }

            // 5. Perform the semantic swap
            foreach ($data as $placeholder => $value) {
                // We use ${KEY} format to match standard template conventions
                $xmlContent = str_replace('${' . $placeholder . '}', (string)$value, $xmlContent);
            }

            // 6. Save the modified XML back into the ZIP
            $zip->addFromString('word/document.xml', $xmlContent);
            $zip->close();

            return $outputFileName;
        }

        throw new RuntimeException("Failed to open the .docx template as ZIP. Ensure ZipArchive is enabled.");
    }
}