<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'duration_days',
        'features',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'duration_days' => 'integer',
            'features' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ─── Scopes ────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    // ─── Relationships ─────────────────────────────

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class, 'plan_id');
    }

    // ─── Helpers ───────────────────────────────────

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 2) . ' EGP';
    }

    public function getDurationLabelAttribute(): string
    {
        $days = $this->duration_days;

        if ($days >= 365) {
            $years = floor($days / 365);
            return $years . ' ' . __('Year') . ($years > 1 ? 's' : '');
        }

        $months = floor($days / 30);
        if ($months > 0) {
            return $months . ' ' . __('Month') . ($months > 1 ? 's' : '');
        }

        return $days . ' ' . __('Day') . ($days > 1 ? 's' : '');
    }
}
