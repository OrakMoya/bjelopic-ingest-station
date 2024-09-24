<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IngestRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'rules',
        'project_id'
    ];


    public function project():BelongsTo{
        return $this->belongsTo(Project::class, 'project_id');
    }
}
