<?php
declare(strict_types=1);
namespace App\Services;

final class ReminderService
{
    /**
     * Single source of truth for reminder subject/body.
     * Manual NIU reminders and automatic reminders must use exactly the same text.
     */
    public static function content(array $document): array
    {
        $number = (string)($document['doc_number'] ?? '');
        $days = (int)floor((strtotime(date('Y-m-d')) - strtotime((string)($document['due_date'] ?? date('Y-m-d')))) / 86400);
        $amount = number_format((float)($document['total_with_vat'] ?? 0), 2, ',', ' ').' Kč';

        if ($days < 0) {
            return [
                'subject' => 'Blíží se splatnost faktury '.$number,
                'body' => 'Faktura '.$number.' ve výši '.$amount.' Kč je před splatností.',
                'days' => $days,
            ];
        }

        return [
            'subject' => 'Upomínka k faktuře '.$number,
            'body' => 'Faktura '.$number.' ve výši '.$amount.' Kč je po splatnosti '.$days.' dní.',
            'days' => $days,
        ];
    }
}
