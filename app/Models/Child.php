<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * A child in the orphan programs (`osp_primary_infos`).
 */
class Child extends Model
{
    protected $table = 'osp_primary_infos';

    protected $guarded = [];

    /** Clusters whose children are not offered to donors for sponsorship. */
    public const EXCLUDED_CLUSTERS = ['Karachi', 'Sukkur', 'Nushki', 'Quetta', 'Gwadar'];

    /** Classes (grades) of children about to leave the program, who are not offered for sponsorship. */
    public const EXCLUDED_CLASSES = [10];

    protected function name(): Attribute
    {
        return Attribute::get(fn () => trim("{$this->First_Name} {$this->Last_Name}"));
    }

    /** "(PKCR-12) Ali Khan - 9 years, Lahore". Needs the `age` and `cluster` columns from scopeForPicker(). */
    protected function pickerLabel(): Attribute
    {
        return Attribute::get(fn () => "({$this->pkcr_id}) {$this->name} - {$this->age} years, {$this->cluster}");
    }

    /**
     * Children a donor can be offered: approved and active, unsponsored (or in the pool), not in an excluded
     * cluster or class, not assigned to an event, passed out, dropped out or failed their latest report,
     * and with no active sponsorship.
     */
    public function scopeAvailableForSponsorship(Builder $query): Builder
    {
        return $query
            ->whereIn('osp_primary_infos.Sponsorship_status', ['Unsponsored', 'InPool'])
            ->where('osp_primary_infos.Status', 'Active')
            ->where('osp_primary_infos.isApproved', 1)
            ->whereNotIn('osp_primary_infos.Class', self::EXCLUDED_CLASSES)
            ->whereNotIn('osp_primary_infos.cluster_id', DB::table('osp_clusters')->select('id')->whereIn('cluster', self::EXCLUDED_CLUSTERS))
            ->whereNotIn('osp_primary_infos.id', DB::table('child_event_assignments')->select('child_id')->whereNotNull('child_id'))
            ->whereNotIn('osp_primary_infos.id', DB::table('passout_requests')->select('child_id')->whereNotNull('child_id'))
            ->whereNotIn('osp_primary_infos.id', DB::table('dropout_requests')->select('childId')->whereNotNull('childId'))
            ->whereNotIn('osp_primary_infos.id', DB::table('education_progress_reports')
                ->select('childId')
                ->whereNotNull('childId')
                ->where('isPromotted', 0)
                ->whereRaw('id IN (SELECT MAX(id) FROM education_progress_reports GROUP BY childId)'))
            ->whereNotIn('osp_primary_infos.id', DB::table('sponsorships')->select('child_id')->where('status', Sponsorship::ACTIVE)->where('isActive', 1));
    }

    /** Search by name or PKCR id. */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        return $query->when($term !== '', fn (Builder $q) => $q->where(fn (Builder $q) => $q
            ->whereRaw("CONCAT_WS(' ', osp_primary_infos.First_Name, osp_primary_infos.Last_Name) LIKE ?", ["%{$term}%"])
            ->orWhere('osp_primary_infos.pkcr_id', 'like', "%{$term}%")));
    }

    /** Columns and joins needed to show a child in a picker. */
    public function scopeForPicker(Builder $query): Builder
    {
        return $query
            ->leftJoin('osp_clusters', 'osp_clusters.id', '=', 'osp_primary_infos.cluster_id')
            ->select([
                'osp_primary_infos.id',
                'osp_primary_infos.pkcr_id',
                'osp_primary_infos.First_Name',
                'osp_primary_infos.Last_Name',
                DB::raw('TIMESTAMPDIFF(YEAR, osp_primary_infos.Date_Of_Birth, CURDATE()) AS age'),
                DB::raw("COALESCE(osp_clusters.cluster, 'Unknown') AS cluster"),
            ])
            ->orderBy('osp_primary_infos.First_Name');
    }

    /** Whether every given child can be offered for sponsorship right now. */
    public static function allAvailable(array $ids): bool
    {
        $ids = array_unique($ids);

        return static::query()->availableForSponsorship()->whereKey($ids)->count() === count($ids);
    }
}
