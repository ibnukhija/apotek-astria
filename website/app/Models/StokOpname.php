<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StokOpname extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_FINAL = 'final';

    protected $table = 'stok_opname';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tanggal'      => 'date:Y-m-d',
            'finalized_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(StokOpnameDetail::class, 'stok_opname_id');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }
}