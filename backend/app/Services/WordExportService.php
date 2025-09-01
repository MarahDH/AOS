<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\TemplateProcessor;

use function Illuminate\Log\log;

class WordExportService
{
    public function exportOfferWithTemplate(
        array $placeholders,
        string $outputFilename,
        ?string $templateFilename = null
    ) {
        $templatePath = $templateFilename
            ? storage_path('app/templates/' . $templateFilename)
            : storage_path(config('offer_word_export.template_path'));

        $template = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);

        // Set standard placeholders
        foreach ($placeholders as $key => $value) {
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
