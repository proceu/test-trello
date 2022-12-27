<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'trello_id',
        'name',
        'desc',
        'start',
        'deadline',
        'closed',
    ];
    /**
     * @return HasMany
     */
    public function members(): HasMany
    {
        return $this->hasMany(TaskMember::class);
    }
}
