<?php
/**
 * Triggers browser download for Excel content.
 * 
 * @param string $filename The desired name of the export file (without .xls extension)
 */
function ExcelGenerate(string $filename = 'report'): void
{
    // Sanitize filename to thoroughly prevent HTTP Header Injection / Response Splitting
    $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '', $filename);
    $filename = $filename ?: 'report';

    header("Content-Type: application/vnd.ms-excel");
    header("X-Content-Type-Options: nosniff");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
}
