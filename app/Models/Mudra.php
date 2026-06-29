<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MudraFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mudra extends Model
{
    /** @use HasFactory<MudraFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'benefits',
        'ai_class_label',
        'reference_image_path',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Route-model binding by slug rather than id (no exposed numeric ids).
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @param  Builder<Mudra>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
