<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Post extends Model
{
    protected $fillable = ['title', 'content', 'slug', 'status', 'user_id', 'image_thumbnail', 'created_at', 'updated_at'];

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

    public function scopeSearchByQuery($query, $q)
    {
        return $query->where(DB::raw('LOWER(title)'), 'LIKE', '%' . strtolower($q) . '%');
    }

    public function scopePopulerByCommnet($query)
    {
        return $query->withCount('comments')->orderBy('comments_count', 'desc');
    }

    public function scopePopulerByLike($query)
    {
        return $query->withCount('likes')->orderBy('likes_count', 'desc');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeNewPost($query)
    {
        return $query->orderBy('updated_at', 'desc');
    }

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

}
