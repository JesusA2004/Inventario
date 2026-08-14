<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetCodeCounter extends Model
{
    protected $fillable = [
        'company_id',
        'asset_type_id',
        'last_number',
    ];
}
