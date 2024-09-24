<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'volume_id'];


    public function volume(): BelongsTo
    {
        return $this->belongsTo(Volume::class, 'volume_id', 'id');
    }

    public function ingestRule(): HasMany
    {
        return $this->hasMany(IngestRule::class, 'project_id');
    }
}
