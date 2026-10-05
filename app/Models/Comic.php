<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comic extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'other_names',
        'author',
        'description',
        'cover_image',
        'banner_image',
        'status',
        'views',
        'monthly_views',
        'weekly_views',
        'daily_views',
        'rating_score',
        'rating_count',
        'is_featured',
        'is_recommended',
        'is_hidden',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_recommended' => 'boolean',
        'is_hidden' => 'boolean',
        'rating_score' => 'float',
        'views' => 'integer',
    ];

    public function scopeVisible($query)
    {
        return $query->where('is_hidden', false);
    }

    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'comic_genre');
    }

    public function chapters()
    {
        return $this->hasMany(Chapter::class)->orderBy('chapter_number', 'asc');
    }

    public function latestChapters($limit = 3)
    {
        return $this->hasMany(Chapter::class)->orderBy('chapter_number', 'desc')->limit($limit);
    }

    public function latestChapter()
    {
        return $this->hasOne(Chapter::class)->ofMany([
            'chapter_number' => 'max',
            'id' => 'max',
        ]);
    }

    public function firstChapter()
    {
        return $this->hasOne(Chapter::class)->ofMany([
            'chapter_number' => 'min',
            'id' => 'min',
        ]);
    }

    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class);
    }

    public function readingHistories()
    {
        return $this->hasMany(ReadingHistory::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)->whereNull('chapter_id')->orderBy('created_at', 'desc');
    }

    public function allComments()
    {
        return $this->hasMany(Comment::class)->orderBy('created_at', 'desc');
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function isBookmarkedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        return $this->bookmarks()->where('user_id', $user->id)->exists();
    }

    public function getCoverUrlAttribute(): string
    {
        if (!empty($this->cover_image)) {
            return $this->cover_image;
        }
        return asset('images/default.png');
    }

    public function getBannerUrlAttribute(): string
    {
        if (!empty($this->banner_image)) {
            return $this->banner_image;
        }
        return $this->cover_url;
    }

    public function getFormattedViewsAttribute(): string
    {
        if ($this->views >= 1000000) {
            return round($this->views / 1000000, 1) . 'M';
        }
        if ($this->views >= 1000) {
            return round($this->views / 1000, 1) . 'K';
        }
        return (string) $this->views;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'Hoàn thành',
            'dropped' => 'Tạm ngưng',
            default => 'Đang tiến hành',
        };
    }

    public function getUpdatedTimeFormattedAttribute(): string
    {
        $time = $this->updated_at ?? $this->created_at;
        if ($this->latestChapter) {
            $chapTime = $this->latestChapter->updated_at ?? $this->latestChapter->created_at;
            if ($chapTime && (!$time || $chapTime->gt($time))) {
                $time = $chapTime;
            }
        }

        if (!$time) {
            return 'Mới đây';
        }

        $now = now();
        $diffMinutes = (int) floor(abs($now->diffInMinutes($time)));

        // Nếu vừa đăng hoặc trong vòng dưới 30 phút
        if ($diffMinutes < 30) {
            return 'Mới đây';
        }

        $diffHours = (int) floor(abs($now->diffInHours($time)));
        if ($diffHours < 24) {
            return $diffHours . 'h';
        }

        $diffDays = (int) floor(abs($now->diffInDays($time)));
        if ($diffDays < 30) {
            return $diffDays . ' ngày';
        }

        return $time->format('d/m/Y');
    }
}
