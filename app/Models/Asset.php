<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'brand',
        'serial_number',
        'status',
        'description',
        'buy_date',
        'price',
        'warranty_date',
        'sell_date',
        'sell_price',
        'buyer_id',
        'seller_id',
        'asset_category_id',
        'account_id',
        'brand_link',
        'start_date',
        'brand_id',
        'brand_name'
    ];

    public function buyer()
    {
        return $this->belongsTo(Account::class, 'buyer_id')->select('id', 'full_name', 'email', 'phone', 'address');
    }

    public function seller()
    {
        return $this->belongsTo(Account::class, 'seller_id')->select('id', 'full_name', 'email', 'phone', 'address');
    }

    public function assetCategory()
    {
        return $this->belongsTo(AssetCategory::class)->select('id', 'name');
    }

    public function account()
    {
        return $this->belongsTo(Account::class)->select('id', 'full_name', 'email', 'phone', 'address');
    }

    public function historyAssets()
    {
        return $this->hasMany(HistoryAsset::class);
    }

    public function brand()
    {
        return $this->belongsTo(AssetBrand::class);
    }
}
