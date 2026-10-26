<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    use HasFactory;

    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    public static function get(string $key, $default = null)
    {
        try {
            $setting = static::find($key);
            return $setting ? $setting->value : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    public static function set(string $key, $value): void
    {
        if ($value === null) {
            static::remove($key);
            return;
        }
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function remove(string $key): void
    {
        static::where('key', $key)->delete();
    }

    public static function getAppName(): string
    {
        return (string) static::get('app_name', 'Sistem Peminjaman BMN');
    }

    public static function getAppShortName(): string
    {
        return (string) static::get('app_short_name', 'BBLSDM Komdigi Makassar');
    }

    public static function getSatkerName(): string
    {
        return (string) static::get('satker_name', 'Balai Besar Pelatihan Sumber Daya Manusia Komunikasi dan Digital Makassar');
    }

    public static function getSatkerCode(): string
    {
        return (string) static::get('satker_code', 'BBLSDM.73');
    }

    public static function getSatkerAddress(): string
    {
        return (string) static::get('satker_address', 'Jl. Prof. Abdurrahman Basalamah II No 25 Makassar 90231');
    }

    public static function getFooterText(): string
    {
        return (string) static::get('app_footer_text', '© 2026 BBLSDM Komunikasi dan Digital Makassar. Hak Cipta Dilindungi.');
    }

    public static function getLogoUrl(): ?string
    {
        $path = static::get('app_logo');
        if ($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            return asset('storage/' . $path);
        }
        return null;
    }

    public static function getFaviconUrl(): string
    {
        $path = static::get('app_favicon');
        if ($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            return asset('storage/' . $path);
        }
        return "data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='8' fill='%230B63B5'/><text x='16' y='22' font-family='sans-serif' font-size='18' font-weight='900' fill='white' text-anchor='middle'>B</text></svg>";
    }
}
