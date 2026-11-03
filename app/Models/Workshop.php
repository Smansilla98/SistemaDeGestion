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

    public const STATUSES = ['PROPUESTA' => 'Propuesta (a evaluar)', 'ABIERTO' => 'Inscripción abierta', 'EN_CURSO' => 'En curso', 'CERRADO' => 'Cerrado', 'FINALIZADO' => 'Finalizado'];

    protected $fillable = ['restaurant_id', 'name', 'description', 'teacher_contact_id', 'room', 'capacity', 'fee_cents',
        'starts_on', 'ends_on', 'status', 'distribution_rule_id', 'activity_category_id'];

    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date', 'capacity' => 'integer', 'fee_cents' => 'integer'];

    /** @return BelongsTo<Contact, $this> */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'teacher_contact_id');
    }

    /** @return HasMany<WorkshopSchedule, $this> */
    public function schedules(): HasMany
    {
        return $this->hasMany(WorkshopSchedule::class)->orderBy('weekday')->orderBy('start_time');
    }

    /** @return HasMany<WorkshopSession, $this> */
    public function sessions(): HasMany
    {
        return $this->hasMany(WorkshopSession::class)->orderBy('date')->orderBy('start_time');
    }

    /** @return HasMany<WorkshopEnrollment, $this> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(WorkshopEnrollment::class);
    }

    /** @return HasMany<WorkshopEvaluation, $this> */
    public function evaluations(): HasMany
    {
        return $this->hasMany(WorkshopEvaluation::class);
    }

    /** @return BelongsTo<DistributionRule, $this> */
    public function distributionRule(): BelongsTo
    {
        return $this->belongsTo(DistributionRule::class);
    }

    /** @return MorphMany<ActivityEntry, $this> */
    public function entries(): MorphMany
    {
        return $this->morphMany(ActivityEntry::class, 'entryable');
    }

    /** @return MorphMany<DistributionPayout, $this> */
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

    /** @return BelongsTo<ActivityCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ActivityCategory::class, 'activity_category_id');
    }
}
