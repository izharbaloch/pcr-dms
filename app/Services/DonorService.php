<?php

namespace App\Services;

use App\Models\Country;
use App\Models\Donor;
use Illuminate\Support\Facades\DB;

class DonorService
{
    /**
     * Creates a donor. The donor id is the next `auto` number, and the donor number reads
     * "2026/PK/11693" (year, country code, id), as for every donor before.
     *
     * @param  array{first_name: string, last_name: string, email: string, contact_no: string, address: string, address2?: ?string, city: string, state?: ?string, zip_code: string, country: string}  $data
     */
    public function create(array $data): Donor
    {
        $number = (int) Donor::max('auto') + 1;
        $countryCode = Country::where('name', $data['country'])->value('sortname');

        return Donor::create([
            ...$data,
            'id' => (string) $number,
            'donor_no' => sprintf('%d/%s/%04d', now()->year, $countryCode, $number),
        ]);
    }

    /** An address for donors who have none: "donor-12@pakcr.org", one past the highest used. */
    public function nextPlaceholderEmail(): string
    {
        $highest = DB::table('pakcr_donors')
            ->where('email', 'like', 'donor-%@pakcr.org')
            ->selectRaw("MAX(CAST(SUBSTRING_INDEX(email, '-', -1) AS UNSIGNED)) AS highest")
            ->value('highest');

        return 'donor-'.((int) $highest + 1).'@pakcr.org';
    }
}
