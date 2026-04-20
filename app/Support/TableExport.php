<?php

namespace App\Support;

use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TableExport
{
    /**
     * @param  array<int, string>  $headings
     * @param  iterable<array<int, mixed>>  $rows
     * @param  array<string, mixed>  $meta
     */
    public static function download(
        string $format,
        string $filename,
        string $title,
        array $headings,
        iterable $rows,
        array $meta = [],
    ): StreamedResponse {
        return self::isCsv($format)
            ? self::csv($filename, $headings, $rows)
            : self::excel($filename, $title, $headings, $rows, $meta);
    }

    protected static function isCsv(string $format): bool
    {
        return strtolower($format) === 'csv';
    }

    /**
     * @param  array<int, string>  $headings
     * @param  iterable<array<int, mixed>>  $rows
     */
    protected static function csv(string $filename, array $headings, iterable $rows): StreamedResponse
    {
        return Response::streamDownload(function () use ($headings, $rows): void {
            echo "\xEF\xBB\xBF";

            $handle = fopen('php://output', 'wb');
            fputcsv($handle, $headings);

            foreach ($rows as $row) {
                fputcsv($handle, array_map([self::class, 'stringValue'], $row));
            }

            fclose($handle);
        }, self::filename($filename, 'csv'), [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  array<int, string>  $headings
     * @param  iterable<array<int, mixed>>  $rows
     * @param  array<string, mixed>  $meta
     */
    protected static function excel(string $filename, string $title, array $headings, iterable $rows, array $meta): StreamedResponse
    {
        return Response::streamDownload(function () use ($title, $headings, $rows, $meta): void {
            echo "\xEF\xBB\xBF";
            echo '<!doctype html><html><head><meta charset="UTF-8">';
            echo '<style>
                body{font-family:Arial,sans-serif;color:#111827;}
                h1{font-size:20px;margin:0 0 12px 0;}
                .meta{margin:0 0 16px 0;border-collapse:collapse;}
                .meta td{border:1px solid #cbd5e1;padding:6px 10px;background:#f8fafc;}
                table.data{border-collapse:collapse;width:100%;}
                table.data th{border:1px solid #94a3b8;background:#243746;color:#ffffff;font-weight:700;padding:8px;text-align:left;}
                table.data td{border:1px solid #cbd5e1;padding:7px;mso-number-format:"\@";vertical-align:top;}
                table.data tr:nth-child(even) td{background:#f8fafc;}
            </style>';
            echo '</head><body>';
            echo '<h1>'.self::escape($title).'</h1>';

            if ($meta !== []) {
                echo '<table class="meta">';

                foreach ($meta as $label => $value) {
                    echo '<tr><td><strong>'.self::escape((string) $label).'</strong></td><td>'.self::escape(self::stringValue($value)).'</td></tr>';
                }

                echo '</table>';
            }

            echo '<table class="data"><thead><tr>';

            foreach ($headings as $heading) {
                echo '<th>'.self::escape($heading).'</th>';
            }

            echo '</tr></thead><tbody>';

            foreach ($rows as $row) {
                echo '<tr>';

                foreach ($row as $value) {
                    echo '<td>'.self::escape(self::stringValue($value)).'</td>';
                }

                echo '</tr>';
            }

            echo '</tbody></table></body></html>';
        }, self::filename($filename, 'xls'), [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    protected static function stringValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('d.m.Y H:i');
        }

        return (string) $value;
    }

    protected static function filename(string $filename, string $extension): string
    {
        $safeName = trim(preg_replace('/[^A-Za-z0-9\-_]+/', '-', $filename), '-');

        return ($safeName !== '' ? $safeName : 'export').'.'.$extension;
    }

    protected static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
