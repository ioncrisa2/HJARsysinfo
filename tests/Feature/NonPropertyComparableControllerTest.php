<?php

use App\Models\NonPropertyComparable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('forbids non property index for user without permission', function () {
    $this->actingAs($this->user)
        ->get('/home/non-properti')
        ->assertForbidden();
});

it('can store non property vehicle comparable', function () {
    Permission::findOrCreate('create_data::non_property_comparable', 'web');
    Permission::findOrCreate('view_any_data::non_property_comparable', 'web');
    Permission::findOrCreate('update_data::non_property_comparable', 'web');

    $this->user->givePermissionTo([
        'create_data::non_property_comparable',
        'view_any_data::non_property_comparable',
        'update_data::non_property_comparable',
    ]);

    $payload = [
        'asset_category' => 'vehicle',
        'asset_subtype' => 'dump_truck',
        'brand' => 'Volvo',
        'model' => 'FMX',
        'variant' => '6x4R',
        'manufacture_year' => 2022,
        'serial_number' => 'SN-123',
        'registration_number' => 'B 1234 XYZ',
        'listing_type' => 'penawaran',
        'source_platform' => 'Autoline',
        'source_name' => 'Sumber Test',
        'source_phone' => '08123456789',
        'source_url' => 'https://example.com/truck',
        'location_country' => 'Indonesia',
        'location_city' => 'Jakarta',
        'location_address' => 'Jl. Testing No. 10',
        'currency' => 'idr',
        'price' => 1200000000,
        'assumed_discount_percent' => 8,
        'data_date' => now()->toDateString(),
        'asset_condition' => 'bekas',
        'operational_status' => 'operasional',
        'legal_document_status' => 'Surat Lengkap',
        'verification_status' => 'partial',
        'confidence_score' => 75,
        'notes' => 'Data pembanding dump truck.',
        'vehicle_type' => 'Dump Truck',
        'axle_configuration' => '6x4',
        'odometer_km' => 85000,
        'transmission' => 'manual',
        'fuel_type' => 'solar',
        'engine_cc' => 12000,
        'payload_kg' => 24000,
        'body_type' => 'Bak Dump',
        'drive_type' => '4x2',
    ];

    $response = $this->actingAs($this->user)
        ->post('/home/non-properti', $payload);

    $record = NonPropertyComparable::query()->first();

    $response->assertRedirect("/home/non-properti/{$record->id}");

    expect($record)->not->toBeNull()
        ->and($record->asset_category)->toBe('vehicle')
        ->and($record->brand)->toBe('Volvo')
        ->and($record->currency)->toBe('IDR')
        ->and((float) $record->asking_price)->toBe(1200000000.0)
        ->and($record->transaction_price)->toBeNull()
        ->and($record->vehicleSpec)->not->toBeNull()
        ->and($record->vehicleSpec->vehicle_type)->toBe('Dump Truck')
        ->and($record->vehicleSpec->axle_configuration)->toBe('6x4');
});

it('can store non property heavy equipment comparable', function () {
    Permission::findOrCreate('create_data::non_property_comparable', 'web');
    Permission::findOrCreate('view_any_data::non_property_comparable', 'web');
    Permission::findOrCreate('update_data::non_property_comparable', 'web');

    $this->user->givePermissionTo([
        'create_data::non_property_comparable',
        'view_any_data::non_property_comparable',
        'update_data::non_property_comparable',
    ]);

    $payload = [
        'asset_category' => 'heavy_equipment',
        'asset_subtype' => 'excavator',
        'brand' => 'Hyundai',
        'model' => 'R340D',
        'variant' => 'Crawler',
        'manufacture_year' => 2024,
        'listing_type' => 'penawaran',
        'location_city' => 'Surabaya',
        'currency' => 'idr',
        'price' => 2150000000,
        'data_date' => now()->toDateString(),
        'asset_condition' => 'bekas',
        'verification_status' => 'partial',
        'equipment_type' => 'Excavator',
        'hour_meter' => 4500,
        'operating_weight_kg' => 34000,
        'bucket_capacity_m3' => 1.8,
        'engine_power_hp' => 270,
    ];

    $response = $this->actingAs($this->user)
        ->post('/home/non-properti', $payload);

    $record = NonPropertyComparable::query()->first();

    $response->assertRedirect("/home/non-properti/{$record->id}");

    expect($record)->not->toBeNull()
        ->and($record->asset_category)->toBe('heavy_equipment')
        ->and($record->heavyEquipmentSpec)->not->toBeNull()
        ->and($record->heavyEquipmentSpec->equipment_type)->toBe('Excavator')
        ->and($record->heavyEquipmentSpec->hour_meter)->toBe(4500);
});

it('can store non property barge comparable', function () {
    Permission::findOrCreate('create_data::non_property_comparable', 'web');
    Permission::findOrCreate('view_any_data::non_property_comparable', 'web');
    Permission::findOrCreate('update_data::non_property_comparable', 'web');

    $this->user->givePermissionTo([
        'create_data::non_property_comparable',
        'view_any_data::non_property_comparable',
        'update_data::non_property_comparable',
    ]);

    $payload = [
        'asset_category' => 'barge',
        'asset_subtype' => 'deck_barge',
        'brand' => 'PT Shipyard A',
        'model' => 'Barge 300FT',
        'manufacture_year' => 2018,
        'listing_type' => 'penawaran',
        'location_city' => 'Balikpapan',
        'currency' => 'idr',
        'price' => 12500000000,
        'data_date' => now()->toDateString(),
        'asset_condition' => 'bekas',
        'verification_status' => 'unverified',
        'barge_type' => 'Deck Barge',
        'capacity_dwt' => 10000,
        'loa_m' => 91.44,
        'beam_m' => 24.38,
        'draft_m' => 5.2,
        'gross_tonnage' => 5200,
        'built_year' => 2018,
    ];

    $response = $this->actingAs($this->user)
        ->post('/home/non-properti', $payload);

    $record = NonPropertyComparable::query()->first();

    $response->assertRedirect("/home/non-properti/{$record->id}");

    expect($record)->not->toBeNull()
        ->and($record->asset_category)->toBe('barge')
        ->and($record->bargeSpec)->not->toBeNull()
        ->and($record->bargeSpec->barge_type)->toBe('Deck Barge')
        ->and($record->bargeSpec->capacity_dwt)->toBe(10000);
});

it('can store non property comparable with media files and links', function () {
    Storage::fake('public');

    Permission::findOrCreate('create_data::non_property_comparable', 'web');
    Permission::findOrCreate('view_any_data::non_property_comparable', 'web');
    Permission::findOrCreate('update_data::non_property_comparable', 'web');

    $this->user->givePermissionTo([
        'create_data::non_property_comparable',
        'view_any_data::non_property_comparable',
        'update_data::non_property_comparable',
    ]);

    $payload = [
        'asset_category' => 'vehicle',
        'asset_subtype' => 'dump_truck',
        'brand' => 'Volvo',
        'model' => 'FMX',
        'manufacture_year' => 2022,
        'listing_type' => 'penawaran',
        'location_city' => 'Jakarta',
        'currency' => 'idr',
        'price' => 1200000000,
        'data_date' => now()->toDateString(),
        'asset_condition' => 'bekas',
        'verification_status' => 'partial',
        'vehicle_type' => 'Dump Truck',
        'media_files' => [
            UploadedFile::fake()->image('foto-unit.jpg'),
            UploadedFile::fake()->create('brosur.pdf', 200, 'application/pdf'),
        ],
        'media_links' => [
            [
                'external_url' => 'https://example.com/dump-truck',
                'caption' => 'Sumber listing',
            ],
        ],
    ];

    $response = $this->actingAs($this->user)
        ->post('/home/non-properti', $payload);

    $record = NonPropertyComparable::query()
        ->with('media')
        ->first();

    $response->assertRedirect("/home/non-properti/{$record->id}");

    expect($record)->not->toBeNull()
        ->and($record->media)->toHaveCount(3);

    $fileMedia = $record->media->whereNotNull('file_path')->values();
    expect($fileMedia)->toHaveCount(2);

    foreach ($fileMedia as $media) {
        Storage::disk('public')->assertExists($media->file_path);
    }

    expect($record->media->where('media_type', 'link')->count())->toBe(1);
});

it('can soft delete non property comparable with reason', function () {
    Permission::findOrCreate('view_any_data::non_property_comparable', 'web');
    Permission::findOrCreate('delete_data::non_property_comparable', 'web');

    $this->user->givePermissionTo([
        'view_any_data::non_property_comparable',
        'delete_data::non_property_comparable',
    ]);

    $record = NonPropertyComparable::query()->create([
        'comparable_code' => 'NPV-TEST-0001',
        'asset_category' => 'vehicle',
        'asset_subtype' => 'dump_truck',
        'brand' => 'Volvo',
        'model' => 'FMX',
        'listing_type' => 'penawaran',
        'location_city' => 'Jakarta',
        'currency' => 'IDR',
        'asking_price' => 1000000000,
        'data_date' => now()->toDateString(),
        'asset_condition' => 'bekas',
        'verification_status' => 'unverified',
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)
        ->delete("/home/non-properti/{$record->id}", [
            'reason' => 'Data duplikat.',
        ]);

    $response->assertRedirect('/home/non-properti');

    $this->assertSoftDeleted('np_comparables', [
        'id' => $record->id,
    ]);

    $this->assertDatabaseHas('np_comparables', [
        'id' => $record->id,
        'deleted_by_id' => $this->user->id,
        'deleted_reason' => 'Data duplikat.',
    ]);
});
