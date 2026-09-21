<?php

namespace App\Concerns;

use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

trait LogsActivityChanges
{
    use HasActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName(class_basename($this))
            ->setDescriptionForEvent(function (string $eventName) {
                $identifier = $this->getActivitylogIdentifier();
                return $identifier
                    ? "{$eventName} · {$identifier}"
                    : $eventName;
            });
    }

    /**
     * Override in models for a friendly identifier like order number or name.
     */
    protected function getActivitylogIdentifier(): ?string
    {
        return '#'.$this->getKey();
    }
}