<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\TemplateProcessor;

use function Illuminate\Log\log;

class WordExportService
{
    /**
     * Format number in German format: thousands separator (.) and decimal comma (,)
     * Examples: 1000.50 -> "1.000,50", 1234567.89 -> "1.234.567,89"
     */
    private function formatGermanNumber($value): string
    {
        if ($value === null || $value === '' || $value === '-') {
            return '-';
        }

        // Check if value is numeric
        if (!is_numeric($value)) {
            return $value;
        }

        // Convert to float for formatting
        $floatValue = (float) $value;

        // Format with 2 decimal places, German thousands separator and decimal comma
        return number_format($floatValue, 2, ',', '.');
    }

    /**
     * Format all numeric values in placeholders array to German format
     */
    private function formatPlaceholders(array $placeholders): array
    {
        $formatted = [];

        foreach ($placeholders as $key => $value) {
            // Skip special placeholders like TODAY()
            if ($key === 'TODAY()') {
                $formatted[$key] = $value;
                continue;
            }

            // Format numeric values
            $formatted[$key] = $this->formatGermanNumber($value);
        }

        return $formatted;
    }

    public function exportOfferWithTemplate(
        array $placeholders,
        string $outputFilename,
        ?string $templateFilename = null
    ) {
        $templatePath = $templateFilename
            ? storage_path('app/templates/' . $templateFilename)
            : storage_path(config('offer_word_export.template_path'));

        $template = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);

        // Format all numeric values to German format
        $formattedPlaceholders = $this->formatPlaceholders($placeholders);

        // Set standard placeholders
        foreach ($formattedPlaceholders as $key => $value) {
            $template->setValue($key, $value ?? '-');
        }

        // Set today's date
        $template->setValue('TODAY()', now()->format('d.m.Y'));

        // Optional: append template name
        if ($templateFilename) {
            $baseName = pathinfo($templateFilename, PATHINFO_FILENAME);
            $outputFilename .= '_' . $baseName;
        }

        $outputPath = storage_path("app/public/{$outputFilename}.docx");
        $template->saveAs($outputPath);

        return response()->download($outputPath, "{$outputFilename}.docx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="' . "{$outputFilename}.docx" . '"',
        ])->deleteFileAfterSend();
    }
}
