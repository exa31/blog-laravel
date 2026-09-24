<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'post_id',
        'content',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function replies()
    {
        return $this->hasMany(Reply::class);
    }

    public function scopeForPost($query, int $postId)
    {
        return $query->where('post_id', $postId)->orderBy('created_at', 'desc');
    }

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
