<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'slug',
        'status',
        'user_id',
        'image_thumbnail',
        'like',
        'created_at',
        'updated_at',
    ];

    protected $appends = ['reading_time', 'image_url'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function savedPosts()
    {
        return $this->hasMany(SavePost::class);
    }

    public function scopeSearchByQuery($query, ?string $q)
    {
        if (empty($q)) {
            return $query;
        }

        return $query->where(DB::raw('LOWER(title)'), 'LIKE', '%'.strtolower($q).'%');
    }

    public function scopePopularByComment($query)
    {
        return $query->withCount('comments')->orderBy('comments_count', 'desc');
    }

    public function scopePopularByLike($query)
    {
        return $query->withCount('likes')->orderBy('likes_count', 'desc');
    }

    // Backward compatibility aliases for existing scopes
    public function scopePopulerByCommnet($query)
    {
        return $this->scopePopularByComment($query);
    }

    public function scopePopulerByLike($query)
    {
        return $this->scopePopularByLike($query);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeNewPost($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Calculate approximate reading time in minutes.
     */
    public function getReadingTimeAttribute(): int
    {
        $words = str_word_count(strip_tags($this->content ?? ''));

        return max(1, (int) ceil($words / 200));
    }

    /**
     * Get the resolved public URL for the post image thumbnail.
     */
    public function getImageUrlAttribute(): string
    {
        if (empty($this->image_thumbnail)) {
            return 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?auto=format&fit=crop&w=800&q=80';
        }

        if (str_starts_with($this->image_thumbnail, 'http://') || str_starts_with($this->image_thumbnail, 'https://')) {
            return $this->image_thumbnail;
        }

        $disk = config('filesystems.default', 'public');

        try {
            return Storage::disk($disk)->url($this->image_thumbnail);
        } catch (\Throwable $e) {
            return '/storage/'.ltrim($this->image_thumbnail, '/');
        }
    }

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'like' => 'integer',
    ];
}
