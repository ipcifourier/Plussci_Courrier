<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Meeting extends Model
{
    use HasFactory;

    // ── Committee types ───────────────────────────────────────────────────────
    public const COMMITTEE_TYPES = [
        'comite_veille'          => 'Comité de Veille',
        'comite_technique'       => 'Comité Technique',
        'secretariat_technique'  => 'Secrétariat Technique Multisectoriel',
        'gtt'                    => 'Groupe Technique de Travail (GTT)',
        'other'                  => 'Autre',
    ];

    // ── JEE status (colours) ──────────────────────────────────────────────────
    public const JEE_STATUSES = [
        'not_done'    => 'Pas fait',
        'launched'    => 'Lancé (TDR OK / Validé)',
        'in_progress' => 'Démarré / En cours',
        'completed'   => 'Réalisé',
    ];

    // Tailwind colour classes for each JEE status
    public const JEE_COLORS = [
        'not_done'    => 'bg-red-500',
        'launched'    => 'bg-orange-500',
        'in_progress' => 'bg-yellow-400',
        'completed'   => 'bg-green-500',
    ];

    public const JEE_TEXT_COLORS = [
        'not_done'    => 'text-white',
        'launched'    => 'text-white',
        'in_progress' => 'text-gray-800',
        'completed'   => 'text-white',
    ];

    protected $fillable = [
        'title',
        'description',
        'starts_at',
        'ends_at',
        'location',
        'status',
        'facilitator_id',
        'committee_type',
        'jee_status',
        'planning_year',
        'planning_period',
        'gtt_id',
        'tdr_path',
        'rapport_path',
        'planned_date',
    ];

    protected $casts = [
        'starts_at'      => 'datetime',
        'ends_at'        => 'datetime',
        'planning_year'  => 'integer',
        'planned_date'   => 'date',
    ];

    public function facilitator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'facilitator_id');
    }

    public function gtt(): BelongsTo
    {
        return $this->belongsTo(Gtt::class, 'gtt_id');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'meeting_participants')
            ->withPivot(['role', 'attendance_status'])
            ->withTimestamps();
    }

    public function participantLinks(): HasMany
    {
        return $this->hasMany(MeetingParticipant::class);
    }

    public function agendaItems(): HasMany
    {
        return $this->hasMany(MeetingAgendaItem::class)->orderBy('position');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(MeetingTask::class);
    }
}
