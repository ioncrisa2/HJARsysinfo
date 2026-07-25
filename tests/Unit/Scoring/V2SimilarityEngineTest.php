<?php

use App\Models\Pembanding;
use App\Services\Scoring\EligibilityService;
use App\Services\Scoring\V2SimilarityEngine;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    config()->set('pembanding_scoring', require base_path('config/pembanding_scoring.php'));

    $this->makeScoringItem = function (array $overrides = []): Pembanding {
        $item = new Pembanding;
        $attributes = array_merge([
            'latitude' => -6.2,
            'longitude' => 106.8,
            'distance' => 0,
            'market_basis' => 'sale',
            'evidence_type' => 'transaction',
            'jenis_objek' => 'rumah_tinggal',
            'peruntukan' => 'rumah_tinggal',
            'luas_tanah' => 100,
            'luas_bangunan' => 80,
            'dokumen_tanah' => 'sertifikat_hak_milik',
            'lebar_jalan' => 6,
            'posisi_tanah' => 'interior_lot',
            'kondisi_tanah' => 'matang',
            'harga' => 1000000000,
        ], $overrides);

        foreach ($attributes as $key => $value) {
            $item->setAttribute($key, $value);
        }

        foreach (['jenisListing', 'jenisObjek', 'peruntukanRef', 'dokumenTanah', 'posisiTanah', 'kondisiTanah'] as $relation) {
            $item->setRelation($relation, null);
        }

        return $item;
    };
});

it('scores exact classifications above broader substitutions', function () {
    $reference = ($this->makeScoringItem)([
        'jenis_objek' => 'apartement',
        'peruntukan' => 'unit_apartemen',
        'luas_tanah' => 0,
    ]);
    $exact = ($this->makeScoringItem)([
        'jenis_objek' => 'apartement',
        'peruntukan' => 'unit_apartemen',
        'luas_tanah' => 0,
    ]);
    $villa = ($this->makeScoringItem)([
        'jenis_objek' => 'rumah_tinggal',
        'peruntukan' => 'villa',
        'luas_tanah' => 0,
    ]);

    $engine = app(V2SimilarityEngine::class);
    $exactResult = $engine->evaluate($reference, $exact);
    $villaResult = $engine->evaluate($reference, $villa);

    expect($exactResult->score)->toBe(100.0)
        ->and($villaResult->score)->toBeLessThan($exactResult->score)
        ->and($villaResult->components['peruntukan']->similarity)->toBe(0.5)
        ->and($villaResult->components['jenis_objek']->similarity)->toBe(0.0);
});

it('uses a continuous road width similarity without a cliff', function () {
    $reference = ($this->makeScoringItem)(['lebar_jalan' => 6]);
    $seven = ($this->makeScoringItem)(['lebar_jalan' => 7]);
    $sevenPointZeroOne = ($this->makeScoringItem)(['lebar_jalan' => 7.01]);
    $engine = app(V2SimilarityEngine::class);

    $first = $engine->evaluate($reference, $seven)->components['lebar_jalan']->similarity;
    $second = $engine->evaluate($reference, $sevenPointZeroOne)->components['lebar_jalan']->similarity;

    expect($first)->toBeGreaterThan($second)
        ->and($first - $second)->toBeLessThan(0.01);
});

it('does not use price as a similarity component', function () {
    $reference = ($this->makeScoringItem)(['harga' => 100000000]);
    $cheap = ($this->makeScoringItem)(['harga' => 100000000]);
    $expensive = ($this->makeScoringItem)(['harga' => 9000000000]);
    $engine = app(V2SimilarityEngine::class);

    $cheapResult = $engine->evaluate($reference, $cheap);
    $expensiveResult = $engine->evaluate($reference, $expensive);

    expect($cheapResult->score)->toBe($expensiveResult->score)
        ->and($cheapResult->components)->not->toHaveKey('harga');
});

it('reports reference and candidate coverage separately', function () {
    $minimal = ($this->makeScoringItem)([
        'jenis_objek' => null,
        'luas_tanah' => null,
        'luas_bangunan' => null,
        'dokumen_tanah' => null,
        'lebar_jalan' => null,
        'posisi_tanah' => null,
        'kondisi_tanah' => null,
    ]);
    $complete = ($this->makeScoringItem)();
    $missingCandidate = ($this->makeScoringItem)([
        'dokumen_tanah' => null,
        'lebar_jalan' => null,
    ]);
    $engine = app(V2SimilarityEngine::class);

    $minimalResult = $engine->evaluate($minimal, $complete);
    $missingResult = $engine->evaluate($complete, $missingCandidate);

    expect($minimalResult->referenceCoverage)->toBeLessThan(60)
        ->and($minimalResult->status)->toBe('insufficient_input')
        ->and($missingResult->referenceCoverage)->toBe(100.0)
        ->and($missingResult->scoreCoverage)->toBeLessThan(100)
        ->and($missingResult->warnings)->not->toBeEmpty();
});

it('rejects candidates with a different sale or rent basis', function () {
    $reference = ($this->makeScoringItem)(['market_basis' => 'sale']);
    $candidate = ($this->makeScoringItem)(['market_basis' => 'rent']);

    $result = app(EligibilityService::class)->evaluate($reference, $candidate);

    expect($result->eligible)->toBeFalse()
        ->and($result->tier)->toBe('excluded');
});

it('marks a deceptively high score as not reliable when reference coverage is insufficient', function () {
    $reference = ($this->makeScoringItem)([
        'jenis_objek' => null,
        'luas_tanah' => null,
        'luas_bangunan' => null,
        'dokumen_tanah' => null,
        'lebar_jalan' => null,
        'posisi_tanah' => null,
        'kondisi_tanah' => null,
    ]);
    $candidate = ($this->makeScoringItem)([
        'jenis_objek' => null,
        'luas_tanah' => null,
        'luas_bangunan' => null,
        'dokumen_tanah' => null,
        'lebar_jalan' => null,
        'posisi_tanah' => null,
        'kondisi_tanah' => null,
    ]);

    $result = app(V2SimilarityEngine::class)->evaluate($reference, $candidate);

    expect($result->score)->toBe(100.0)
        ->and($result->status)->toBe('insufficient_input')
        ->and($result->warnings)->not->toBeEmpty()
        ->and(implode(' ', $result->warnings))->toContain('tidak layak');
});

it('excludes evidence dated after the historical reference date', function () {
    $reference = ($this->makeScoringItem)(['reference_date' => '2025-01-01']);
    $candidate = ($this->makeScoringItem)(['tanggal_data' => '2025-01-02']);

    $result = app(EligibilityService::class)->evaluate($reference, $candidate);

    expect($result->eligible)->toBeFalse()
        ->and($result->reasons[0])->toContain('setelah tanggal acuan');
});
