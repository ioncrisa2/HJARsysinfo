<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pembanding;
use App\Models\JenisListing;
use App\Models\JenisObjek;
use App\Models\StatusPemberiInformasi;
use App\Models\BentukTanah;
use App\Models\DokumenTanah;
use App\Models\PosisiTanah;
use App\Models\KondisiTanah;
use App\Models\Topografi;
use App\Models\Peruntukan;
use Illuminate\Support\Facades\DB;

class BackfillPembandingMasterIds extends Command
{
    protected $signature = 'pembanding:backfill-master-ids
                            {--dry-run : Run without making changes}
                            {--report : Show detailed report of unmatched slugs}
                            {--create-missing : Create missing master data entries}
                            {--export-unmatched : Export unmatched data to CSV}';

    protected $description = 'Backfill pembandings master *_id fields from existing slug columns with detailed reporting';

    protected array $unmatchedData = [];
    protected array $stats = [];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $showReport = (bool) $this->option('report');
        $createMissing = (bool) $this->option('create-missing');
        $exportUnmatched = (bool) $this->option('export-unmatched');

        $this->info('🚀 Starting Pembanding Master IDs Backfill Process...');
        $this->newLine();

        if ($dryRun) {
            $this->warn('⚠️  DRY-RUN MODE: No changes will be made to the database');
            $this->newLine();
        }

        // Load master data maps
        $maps = $this->loadMasterDataMaps();

        // Initialize statistics
        $this->initializeStats();

        // Process pembandings
        $this->processPembandings($maps, $dryRun);

        // Display summary
        $this->displaySummary($dryRun);

        // Handle unmatched slugs
        if ($showReport || $exportUnmatched) {
            $this->handleUnmatchedSlugs($exportUnmatched);
        }

        // Create missing master data if requested
        if ($createMissing && !$dryRun && !empty($this->unmatchedData)) {
            $this->createMissingMasterData($maps);
        }

        return self::SUCCESS;
    }

    /**
     * Load all master data slug => id mappings
     */
    protected function loadMasterDataMaps(): array
    {
        $this->info('📚 Loading master data mappings...');

        $maps = [
            'jenis_listing'            => JenisListing::query()->pluck('id', 'slug')->all(),
            'jenis_objek'              => JenisObjek::query()->pluck('id', 'slug')->all(),
            'status_pemberi_informasi' => StatusPemberiInformasi::query()->pluck('id', 'slug')->all(),
            'bentuk_tanah'             => BentukTanah::query()->pluck('id', 'slug')->all(),
            'dokumen_tanah'            => DokumenTanah::query()->pluck('id', 'slug')->all(),
            'posisi_tanah'             => PosisiTanah::query()->pluck('id', 'slug')->all(),
            'kondisi_tanah'            => KondisiTanah::query()->pluck('id', 'slug')->all(),
            'topografi'                => Topografi::query()->pluck('id', 'slug')->all(),
            'peruntukan'               => Peruntukan::query()->pluck('id', 'slug')->all(),
        ];

        // Display master data stats
        foreach ($maps as $field => $data) {
            $count = count($data);
            $this->line("  • {$field}: {$count} entries");
        }

        $this->newLine();
        return $maps;
    }

    /**
     * Initialize statistics tracking
     */
    protected function initializeStats(): void
    {
        $this->stats = [
            'total_scanned' => 0,
            'already_has_id' => 0,
            'successfully_matched' => 0,
            'unmatched_slugs' => 0,
            'null_slugs' => 0,
            'updated_records' => 0,
        ];

        $this->unmatchedData = [
            'jenis_listing' => [],
            'jenis_objek' => [],
            'status_pemberi_informasi' => [],
            'bentuk_tanah' => [],
            'dokumen_tanah' => [],
            'posisi_tanah' => [],
            'kondisi_tanah' => [],
            'topografi' => [],
            'peruntukan' => [],
        ];
    }

    /**
     * Process all pembanding records
     */
    protected function processPembandings(array $maps, bool $dryRun): void
    {
        $this->info('🔄 Processing pembanding records...');

        $progressBar = $this->output->createProgressBar(
            Pembanding::withTrashed()->count()
        );

        Pembanding::query()
            ->select([
                'id',
                'jenis_listing', 'jenis_listing_id',
                'jenis_objek', 'jenis_objek_id',
                'status_pemberi_informasi', 'status_pemberi_informasi_id',
                'bentuk_tanah', 'bentuk_tanah_id',
                'dokumen_tanah', 'dokumen_tanah_id',
                'posisi_tanah', 'posisi_tanah_id',
                'kondisi_tanah', 'kondisi_tanah_id',
                'topografi', 'topografi_id',
                'peruntukan', 'peruntukan_id',
            ])
            ->withTrashed()
            ->chunkById(250, function ($rows) use ($maps, $dryRun, $progressBar) {
                foreach ($rows as $row) {
                    $this->stats['total_scanned']++;
                    $progressBar->advance();

                    $payload = $this->buildUpdatePayload($row, $maps);

                    if (!empty($payload)) {
                        $this->stats['updated_records']++;

                        if (!$dryRun) {
                            Pembanding::query()->whereKey($row->id)->update($payload);
                        }
                    }
                }
            });

        $progressBar->finish();
        $this->newLine(2);
    }

    /**
     * Build update payload for a single pembanding record
     */
    protected function buildUpdatePayload($row, array $maps): array
    {
        $payload = [];

        $fields = [
            'jenis_listing' => 'jenis_listing_id',
            'jenis_objek' => 'jenis_objek_id',
            'status_pemberi_informasi' => 'status_pemberi_informasi_id',
            'bentuk_tanah' => 'bentuk_tanah_id',
            'dokumen_tanah' => 'dokumen_tanah_id',
            'posisi_tanah' => 'posisi_tanah_id',
            'kondisi_tanah' => 'kondisi_tanah_id',
            'topografi' => 'topografi_id',
            'peruntukan' => 'peruntukan_id',
        ];

        foreach ($fields as $slugField => $idField) {
            $result = $this->matchSlugToId($row, $slugField, $idField, $maps[$slugField]);

            if ($result['matched'] && $result['id']) {
                $payload[$idField] = $result['id'];
            }
        }

        return $payload;
    }

    /**
     * Match a slug field to its corresponding ID
     */
    protected function matchSlugToId($row, string $slugField, string $idField, array $map): array
    {
        $slug = $row->{$slugField};
        $currentId = $row->{$idField};

        // Already has ID
        if ($currentId) {
            $this->stats['already_has_id']++;
            return ['matched' => false, 'id' => null];
        }

        // Slug is empty/null
        if (blank($slug)) {
            $this->stats['null_slugs']++;
            return ['matched' => false, 'id' => null];
        }

        // Try to find matching ID
        $id = $map[$slug] ?? null;

        if ($id) {
            $this->stats['successfully_matched']++;
            return ['matched' => true, 'id' => $id];
        }

        // No match found - track this
        $this->stats['unmatched_slugs']++;
        $this->trackUnmatchedSlug($row->id, $slugField, $slug);

        return ['matched' => false, 'id' => null];
    }

    /**
     * Track unmatched slug for reporting
     */
    protected function trackUnmatchedSlug(int $pembandingId, string $field, string $slug): void
    {
        if (!isset($this->unmatchedData[$field][$slug])) {
            $this->unmatchedData[$field][$slug] = [
                'slug' => $slug,
                'count' => 0,
                'pembanding_ids' => [],
            ];
        }

        $this->unmatchedData[$field][$slug]['count']++;
        $this->unmatchedData[$field][$slug]['pembanding_ids'][] = $pembandingId;
    }

    /**
     * Display summary statistics
     */
    protected function displaySummary(bool $dryRun): void
    {
        $this->info('📊 Summary Report:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Records Scanned', number_format($this->stats['total_scanned'])],
                ['Already Has ID', number_format($this->stats['already_has_id'])],
                ['Successfully Matched', number_format($this->stats['successfully_matched'])],
                ['Unmatched Slugs', number_format($this->stats['unmatched_slugs'])],
                ['Null/Empty Slugs', number_format($this->stats['null_slugs'])],
                ['Records ' . ($dryRun ? 'To Update' : 'Updated'), number_format($this->stats['updated_records'])],
            ]
        );
        $this->newLine();
    }

    /**
     * Handle unmatched slugs reporting
     */
    protected function handleUnmatchedSlugs(bool $exportToCsv): void
    {
        $hasUnmatched = false;

        foreach ($this->unmatchedData as $field => $slugs) {
            if (empty($slugs)) {
                continue;
            }

            $hasUnmatched = true;

            $this->warn("⚠️  Unmatched slugs for: {$field}");

            $tableData = [];
            foreach ($slugs as $data) {
                $sampleIds = array_slice($data['pembanding_ids'], 0, 5);
                $tableData[] = [
                    $data['slug'],
                    $data['count'],
                    implode(', ', $sampleIds) . (count($data['pembanding_ids']) > 5 ? '...' : ''),
                ];
            }

            $this->table(
                ['Slug', 'Occurrences', 'Sample Pembanding IDs'],
                $tableData
            );
            $this->newLine();
        }

        if (!$hasUnmatched) {
            $this->info('✅ No unmatched slugs found!');
            return;
        }

        if ($exportToCsv) {
            $this->exportUnmatchedToCsv();
        }

        $this->displaySuggestions();
    }

    /**
     * Export unmatched data to CSV
     */
    protected function exportUnmatchedToCsv(): void
    {
        $filename = storage_path('app/unmatched_pembanding_slugs_' . now()->format('Y-m-d_His') . '.csv');

        $handle = fopen($filename, 'w');
        fputcsv($handle, ['Master Table', 'Slug Value', 'Occurrences', 'Sample Pembanding IDs']);

        foreach ($this->unmatchedData as $field => $slugs) {
            foreach ($slugs as $data) {
                $sampleIds = implode('; ', array_slice($data['pembanding_ids'], 0, 10));
                fputcsv($handle, [
                    $field,
                    $data['slug'],
                    $data['count'],
                    $sampleIds,
                ]);
            }
        }

        fclose($handle);
        $this->info("📄 Unmatched data exported to: {$filename}");
        $this->newLine();
    }

    /**
     * Create missing master data entries
     */
    protected function createMissingMasterData(array $maps): void
    {
        if (!$this->confirm('Do you want to create missing master data entries?')) {
            return;
        }

        $this->info('🔨 Creating missing master data entries...');

        $modelMap = [
            'jenis_listing' => JenisListing::class,
            'jenis_objek' => JenisObjek::class,
            'status_pemberi_informasi' => StatusPemberiInformasi::class,
            'bentuk_tanah' => BentukTanah::class,
            'dokumen_tanah' => DokumenTanah::class,
            'posisi_tanah' => PosisiTanah::class,
            'kondisi_tanah' => KondisiTanah::class,
            'topografi' => Topografi::class,
            'peruntukan' => Peruntukan::class,
        ];

        $created = 0;

        foreach ($this->unmatchedData as $field => $slugs) {
            if (empty($slugs)) {
                continue;
            }

            $model = $modelMap[$field];
            $maxSortOrder = $model::max('sort_order') ?? 0;

            foreach ($slugs as $data) {
                $slug = $data['slug'];

                // Create with basic data
                $model::create([
                    'slug' => $slug,
                    'name' => $this->slugToName($slug),
                    'sort_order' => ++$maxSortOrder,
                    'is_active' => true,
                ]);

                $created++;
                $this->line("  ✓ Created {$field}: {$slug}");
            }
        }

        $this->info("✅ Created {$created} missing master data entries");
        $this->warn('⚠️  Please review and update the names/sort orders as needed');
        $this->info('💡 Run the command again to backfill the IDs');
        $this->newLine();
    }

    /**
     * Convert slug to human-readable name
     */
    protected function slugToName(string $slug): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $slug));
    }

    /**
     * Display helpful suggestions
     */
    protected function displaySuggestions(): void
    {
        $this->info('💡 Suggestions:');
        $this->line('  1. Review the unmatched slugs above');
        $this->line('  2. Fix typos in the master data tables or pembanding records');
        $this->line('  3. Use --create-missing to auto-create missing entries');
        $this->line('  4. Use --export-unmatched to export data to CSV for review');
        $this->newLine();

        $this->comment('Example SQL to investigate specific field:');
        $this->line('  SELECT id, dokumen_tanah, dokumen_tanah_id');
        $this->line('  FROM data_pembanding');
        $this->line('  WHERE dokumen_tanah IS NOT NULL');
        $this->line('  AND dokumen_tanah_id IS NULL');
        $this->line('  LIMIT 20;');
        $this->newLine();
    }
}
