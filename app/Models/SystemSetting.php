<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = [
        'system_name',
        'logo_path',
        'qr_base_url',
        'internal_code_format',
        'label_template',
        'default_company_id',
    ];

    public static function current(): self
    {
        return self::query()->firstOrCreate(['id' => 1]);
    }
}
