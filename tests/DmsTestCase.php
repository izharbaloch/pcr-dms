<?php

namespace Tests;

use App\Models\Child;
use App\Models\Donor;
use App\Models\User;
use App\Services\DonorService;
use Illuminate\Support\Facades\DB;

/**
 * Base for tests of the legacy donor tables. They run on the MySQL database `pcr_dms_test`,
 * which holds only the table structures, and are emptied before every test, so the real data is never touched.
 * (The tables are MyISAM, so a rolled-back transaction would not undo anything.)
 */
abstract class DmsTestCase extends TestCase
{
    private const TABLES = [
        'users', 'pakcr_donors', 'pakcr_transactions', 'subscriptions', 'manual_subscriptions', 'sponsorships',
        'sponsorship_transactions', 'sponosorship_changes', 'sponsorship_logs', 'osp_primary_infos', 'donation_products',
        'country_lists', 'country_states', 'osp_clusters', 'child_event_assignments', 'passout_requests',
        'dropout_requests', 'education_progress_reports',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'mysql', 'database.connections.mysql.database' => 'pcr_dms_test']);
        DB::purge('mysql');

        foreach (self::TABLES as $table) {
            DB::table($table)->truncate();
        }

        $this->seedReferenceData();
        $this->actingAs(User::factory()->create());
    }

    private function seedReferenceData(): void
    {
        DB::table('country_lists')->insert([
            ['id' => 231, 'sortname' => 'US', 'name' => 'United States', 'phonecode' => 1, 'currency' => 'USD'],
            ['id' => 166, 'sortname' => 'PK', 'name' => 'Pakistan', 'phonecode' => 92, 'currency' => 'PKR'],
        ]);
        DB::table('country_states')->insert([
            ['name' => 'Michigan', 'country_id' => 231],
            ['name' => 'Texas', 'country_id' => 231],
        ]);
        DB::table('osp_clusters')->insert([['id' => 1, 'cluster' => 'Lahore'], ['id' => 2, 'cluster' => 'Karachi']]);

        foreach (['Orphan Care Program', 'Orphan Sponsorship', 'Education Support Program', 'Where Most Needed'] as $program) {
            DB::table('donation_products')->insert(['stripe_name' => $program, 'website_name' => $program, 'status' => 1]);
        }
        DB::table('donation_products')->insert(['stripe_name' => 'Old', 'website_name' => 'Retired Program', 'status' => 0]);
    }

    protected function makeDonor(array $overrides = []): Donor
    {
        return app(DonorService::class)->create([
            'first_name' => 'Amina', 'last_name' => 'Khan', 'email' => 'amina'.uniqid().'@example.com', 'contact_no' => '555-0101',
            'address' => '1 Main St', 'city' => 'Canton', 'state' => 'Michigan', 'zip_code' => '48187', 'country' => 'United States',
            ...$overrides,
        ]);
    }

    protected function makeChild(array $overrides = []): Child
    {
        return Child::create([
            'pkcr_id' => 'PKCR-'.uniqid(), 'First_Name' => 'Ali', 'Last_Name' => 'Raza', 'Gender' => 'Male', 'Date_Of_Birth' => now()->subYears(9)->toDateString(),
            'Father_Name' => 'F', 'Mother_Name' => 'M', 'CNIC' => '1', 'District' => 'D', 'Province' => 'P', 'Country' => 'Pakistan',
            'School_Name' => 'S', 'School_Type' => 'T', 'Class' => '3', 'cluster_id' => 1, 'lock' => 'no', 'user_id' => 1,
            'Status' => 'Active', 'Sponsorship_status' => 'Unsponsored', 'isApproved' => 1, 'isSponsored' => 0,
            ...$overrides,
        ]);
    }
}
