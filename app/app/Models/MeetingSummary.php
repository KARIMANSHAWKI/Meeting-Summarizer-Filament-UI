<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'title',
        'summary',
        'decisions',
        'action_items',
        'source',
        'azure_raw',
        'input_type',
        'input_text',
        'input_media_path',
        'processing_status',
        'error_message',
    ];

    protected $casts = [
        'decisions' => 'array',
        'action_items' => 'array',
        'azure_raw' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
