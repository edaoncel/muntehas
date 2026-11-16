<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'xml_url',
        'default_profit_margin',
        'is_active',
        'last_synced_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'default_profit_margin' => 'decimal:2',
        'last_synced_at' => 'datetime',
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'supplier_id');
    }
}