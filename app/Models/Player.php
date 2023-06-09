<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Player extends Model
{
    use HasFactory;

    public const PROFILE_IMAGE_PATH = 'profile_images';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = ['team_id', 'first_name', 'last_name', 'profile_image_path'];

    /**
     * Player belongs to a team.
     *
     * @return BelongsTo
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the profile image url of this player.
     *
     * @return string
     */
    public function profileImageUrl(): string
    {
        return Storage::url($this->profile_image_path);
    }
}
