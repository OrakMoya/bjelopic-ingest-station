<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class File extends Model
{
    use HasFactory;

    protected $fillable = ['filename', 'path', 'volume_id', 'exif', 'mimetype'];


    public function volume():BelongsTo
    {
        return $this->belongsTo(Volume::class, 'volume_id');
    }
}
