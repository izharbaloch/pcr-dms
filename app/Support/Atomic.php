<?php

namespace App\Support;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Runs a write that touches several tables so that a failure leaves nothing behind.
 *
 * The legacy tables are MyISAM, where DB::transaction() cannot roll anything back. Every write
 * therefore goes through save(), which remembers how to reverse it, and a failure replays those
 * reversals. The transaction is kept as well, so the same code stays correct on InnoDB tables.
 */
class Atomic
{
    /** @var list<Closure> */
    private array $reversals = [];

    public static function run(Closure $work): mixed
    {
        $atomic = new self;

        try {
            return DB::transaction(fn () => $work($atomic));
        } catch (Throwable $exception) {
            $atomic->rollBack();

            throw $exception;
        }
    }

    /** Saves the model, and records how to delete it (new rows) or restore it (changed rows). */
    public function save(Model $model): Model
    {
        $isNew = ! $model->exists;
        $previous = Arr::only($model->getRawOriginal(), [...array_keys($model->getDirty()), $model->getUpdatedAtColumn()]);

        $model->save();

        $query = fn () => $model->newQueryWithoutScopes()->whereKey($model->getKey())->toBase();

        $this->reversals[] = $isNew
            ? fn () => $query()->delete()
            : fn () => $query()->update($previous);

        return $model;
    }

    private function rollBack(): void
    {
        foreach (array_reverse($this->reversals) as $reverse) {
            try {
                $reverse();
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        $this->reversals = [];
    }
}
