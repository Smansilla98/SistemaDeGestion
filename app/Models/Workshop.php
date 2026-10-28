<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Taller o curso de una organización, con horarios semanales y cupo opcional.
 */
class Workshop extends Model
{
    use BelongsToRestaurant;

    public const STATUSES = ['ABIERTO' => 'Inscripción abierta', 'EN_CURSO' => 'En curso', 'CERRADO' => 'Cerrado', 'FINALIZADO' => 'Finalizado'];

    protected $fillable = ['restaurant_id', 'name', 'description', 'teacher_contact_id', 'room', 'capacity', 'fee_cents',
        'starts_on', 'ends_on', 'status', 'distribution_rule_id'];

    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date', 'capacity' => 'integer', 'fee_cents' => 'integer'];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'teacher_contact_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(WorkshopSchedule::class)->orderBy('weekday')->orderBy('start_time');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(WorkshopSession::class)->orderBy('date')->orderBy('start_time');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(WorkshopEnrollment::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(WorkshopEvaluation::class);
    }

    public function distributionRule(): BelongsTo
    {
        return $this->belongsTo(DistributionRule::class);
    }

    public function entries(): MorphMany
    {
        return $this->morphMany(ActivityEntry::class, 'entryable');
    }

    public function payouts(): MorphMany
    {
        return $this->morphMany(DistributionPayout::class, 'payoutable');
    }

    public function activeCount(): int
    {
        return $this->enrollments()->where('status', WorkshopEnrollment::ACTIVA)->count();
    }

    public function hasRoom(): bool
    {
        return $this->capacity === null || $this->activeCount() < $this->capacity;
    }

    /** ¿El usuario es el profesor del taller (por su contacto)? */
    public function isTaughtBy(User $user): bool
    {
        return $this->teacher_contact_id !== null
            && Contact::withoutGlobalScopes()->whereKey($this->teacher_contact_id)->where('user_id', $user->id)->exists();
    }
}
