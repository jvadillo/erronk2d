<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChallengeEvidence extends Model
{
    protected $table = 'challenge_evidences';

    /**
     * Valoraciones admitidas para una anotación, de la más favorable a la menos.
     *
     * @var list<string>
     */
    public const SENTIMENTS = ['positive', 'neutral', 'negative'];

    protected $fillable = ['challenge_id', 'student_id', 'author_id', 'note', 'sentiment'];

    protected $attributes = ['sentiment' => 'neutral'];

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
