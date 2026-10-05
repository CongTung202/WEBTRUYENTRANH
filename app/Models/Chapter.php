<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chapter extends Model
{
    use HasFactory;

    protected $fillable = [
        'comic_id',
        'chapter_number',
        'title',
        'slug',
        'views',
    ];

    protected $touches = ['comic'];

    protected $casts = [
        'chapter_number' => 'float',
        'views' => 'integer',
    ];

    public function comic()
    {
        return $this->belongsTo(Comic::class);
    }

    public function pages()
    {
        return $this->hasMany(ChapterPage::class)->orderBy('page_number', 'asc');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)->orderBy('created_at', 'desc');
    }

    public function getNextChapterAttribute()
    {
        return Chapter::where('comic_id', $this->comic_id)
            ->where('chapter_number', '>', $this->chapter_number)
            ->orderBy('chapter_number', 'asc')
            ->first();
    }

    public function getPrevChapterAttribute()
    {
        return Chapter::where('comic_id', $this->comic_id)
            ->where('chapter_number', '<', $this->chapter_number)
            ->orderBy('chapter_number', 'desc')
            ->first();
    }

    public function getFormattedNumberAttribute(): string
    {
        // If it's a whole number like 1.00 -> 1, if 1.50 -> 1.5
        return (string) (floor($this->chapter_number) == $this->chapter_number 
            ? (int) $this->chapter_number 
            : $this->chapter_number);
    }

    public function getDisplayNameAttribute(): string
    {
        $num = 'Chapter ' . $this->formatted_number;
        if (!empty($this->title)) {
            return $num . ': ' . $this->title;
        }
        return $num;
    }

    public function getUpdatedTimeFormattedAttribute(): string
    {
        $time = $this->updated_at ?? $this->created_at;
        if (!$time) {
            return 'Mới đây';
        }

        $now = now();
        $diffMinutes = (int) floor(abs($now->diffInMinutes($time)));

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
