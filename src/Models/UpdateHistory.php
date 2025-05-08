<?php

namespace LaravelUpdraft\Models;

use Illuminate\Database\Eloquent\Model;

class UpdateHistory extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'update_history';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'version',
        'name',
        'description',
        'applied_by',
        'metadata',
        'applied_at',
        'successful',
        'backup_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'metadata' => 'array',
        'applied_at' => 'datetime',
        'successful' => 'boolean',
    ];

    /**
     * Get the latest applied update.
     *
     * @return self|null
     */
    public static function getLatestUpdate()
    {
        return static::where('successful', true)
            ->latest('applied_at')
            ->first();
    }

    /**
     * Check if a specific version has been applied.
     *
     * @param string $version
     * @return bool
     */
    public static function hasVersion($version)
    {
        return static::where('version', $version)
            ->where('successful', true)
            ->exists();
    }
}
