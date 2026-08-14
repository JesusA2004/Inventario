<?php

namespace App\Services;

use App\Models\AssetCodeCounter;
use App\Models\AssetType;
use App\Models\Company;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AssetCodeService
{
    /**
     * Generate the next available internal code for a company + asset type,
     * formatted as EMPRESA-TIPO-CONSECUTIVO (e.g. CML-LAP-001).
     *
     * Uses a dedicated counters table with row locking inside a transaction
     * to stay safe under concurrent requests (e.g. two phones registering
     * assets for the same branch at the same time).
     */
    public function generate(Company $company, AssetType $assetType): string
    {
        $number = $this->nextNumber($company->id, $assetType->id);

        return sprintf(
            '%s-%s-%03d',
            Str::upper($company->code),
            Str::upper($assetType->code),
            $number,
        );
    }

    private function nextNumber(int $companyId, int $assetTypeId): int
    {
        return DB::transaction(function () use ($companyId, $assetTypeId): int {
            $counter = AssetCodeCounter::query()
                ->where('company_id', $companyId)
                ->where('asset_type_id', $assetTypeId)
                ->lockForUpdate()
                ->first();

            if (! $counter) {
                try {
                    $counter = AssetCodeCounter::create([
                        'company_id' => $companyId,
                        'asset_type_id' => $assetTypeId,
                        'last_number' => 0,
                    ]);
                } catch (QueryException) {
                    // Another concurrent request created it first; re-fetch with lock.
                    $counter = AssetCodeCounter::query()
                        ->where('company_id', $companyId)
                        ->where('asset_type_id', $assetTypeId)
                        ->lockForUpdate()
                        ->firstOrFail();
                }
            }

            $counter->increment('last_number');

            return $counter->last_number;
        });
    }
}
