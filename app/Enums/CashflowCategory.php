<?php

namespace App\Enums;

enum CashflowCategory: string
{
    case Ipl = 'ipl';
    case Security = 'security';
    case Cleanliness = 'cleanliness';
    case Utilities = 'utilities';
    case Maintenance = 'maintenance';
    case Administration = 'administration';
    case Activities = 'activities';
    case Donation = 'donation';
    case Other = 'other';
    case Uncategorized = 'uncategorized';

    public function label(): string
    {
        return match ($this) {
            self::Ipl => 'Pembayaran Tagihan',
            self::Security => 'Keamanan',
            self::Cleanliness => 'Kebersihan',
            self::Utilities => 'Listrik & Air',
            self::Maintenance => 'Perbaikan & Pemeliharaan',
            self::Administration => 'Administrasi',
            self::Activities => 'Kegiatan Warga',
            self::Donation => 'Donasi & Sumbangan',
            self::Other => 'Lainnya',
            self::Uncategorized => 'Belum Dikategorikan',
        };
    }

    public static function manualCases(): array
    {
        return array_values(array_filter(self::cases(), fn ($category) => $category !== self::Ipl));
    }
}
