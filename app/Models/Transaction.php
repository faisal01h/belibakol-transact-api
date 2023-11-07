<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'user_identifier',
        'destination',
        'created_by',
        'invoice',
        'ref_id',
        'payment_method',
        'source',
        'base_price',
        'payment_method_fee',
        'selling_price',
        'status',
        'remarks',
        'coupon_id',
        'raw_json'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
    ];

    public function product() {
        return $this->belongsTo(Product::class);
    }
}
