<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Todo extends Model
{
    use HasFactory, LogsActivity;

    /**
     * 自動ログ対象
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'description', 'completed'])
            ->useLogName('todo')
            ->logOnlyDirty();
    }

    /**
     * マスアサインメント可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'completed',
        'user_id',
    ];

    /**
     * 型キャストする属性
     *
     * @var array<string, string>
     */
    protected $casts = [
        'completed' => 'boolean', // 'completed' カラムをブール値として扱う
    ];
}
