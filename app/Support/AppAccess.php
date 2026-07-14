<?php

namespace App\Support;

use App\Models\User;
use App\Supports\DictionaryTypeMap;
use Illuminate\Support\Arr;

class AppAccess
{
    public static function permissions(): array
    {
        return [
            'view_access_control',
            'view_search',
            'view_activity_log',

            'view_any_user',
            'view_user',
            'create_user',
            'update_user',
            'delete_user',
            'delete_any_user',
            'force_delete_user',
            'force_delete_any_user',
            'restore_user',
            'restore_any_user',
            'replicate_user',
            'reorder_user',

            'view_any_role',
            'view_role',
            'create_role',
            'update_role',
            'delete_role',
            'delete_any_role',
            'force_delete_role',
            'force_delete_any_role',
            'restore_role',
            'restore_any_role',
            'replicate_role',
            'reorder_role',

            'view_any_permission',
            'view_permission',
            'create_permission',
            'update_permission',
            'delete_permission',
            'delete_any_permission',

            'manage_data_contributor_invitations',

            'view_moderation',
            'approve_delete_request',
            'reject_delete_request',

            'view_master_data',
            'create_master_data',
            'update_master_data',
            'update_master_data_status',
            'delete_master_data',
            'delete_any_master_data',
            'reorder_master_data',

            'view_geo_data',
            'create_geo_data',
            'update_geo_data',
            'delete_geo_data',

            'view_export',
            'view_export_audit',
            'view_backup',
            'create_database_backup',
            'create_uploads_backup',
            'view_settings',
            'update_settings',
            'clear_cache',

            ...self::widgetPermissions(),
        ];
    }

    public static function widgetPermissions(): array
    {
        return array_values(self::widgetPermissionMap());
    }

    public static function widgetPermissionMap(): array
    {
        return [
            'customLeafletMap' => 'widget_CustomLeafletMap',
            'dataEntryTrendChart' => 'widget_DataEntryTrendChart',
            'latestPembandingTable' => 'widget_LatestPembandingTable',
            'listingCompositionChart' => 'widget_ListingCompositionChart',
            'listingTableWidget' => 'widget_ListingTableWidget',
            'map' => 'widget_Map',
            'statsOverview' => 'widget_StatsOverview',
            'topContributorTable' => 'widget_TopContributorTable',
            'dataFreshnessWidget' => 'widget_DataFreshnessWidget',
            'topAreaActivityTable' => 'widget_TopAreaActivityTable',
            'objectTypeCountTable' => 'widget_ObjectTypeCountTable',
        ];
    }

    public static function menuFor(?User $user): array
    {
        if (! $user) {
            return [];
        }

        return collect(self::menu())
            ->map(function (array $section) use ($user): array {
                $section['items'] = collect($section['items'])
                    ->map(function (array $item) use ($user): ?array {
                        if (isset($item['children'])) {
                            $item['children'] = collect($item['children'])
                                ->filter(fn (array $child): bool => self::can($user, $child['permissions'] ?? []))
                                ->map(fn (array $child): array => Arr::except($child, ['permissions']))
                                ->values()
                                ->all();

                            return $item['children'] === [] ? null : Arr::except($item, ['permissions']);
                        }

                        return ! isset($item['permissions']) || self::can($user, $item['permissions'])
                            ? Arr::except($item, ['permissions'])
                            : null;
                    })
                    ->filter()
                    ->values()
                    ->all();

                return $section;
            })
            ->filter(fn (array $section): bool => $section['items'] !== [])
            ->values()
            ->all();
    }

    public static function can(?User $user, string|array $permissions): bool
    {
        if (! $user) {
            return false;
        }

        foreach (Arr::wrap($permissions) as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    public static function capabilityMap(?User $user, array $permissionsByKey): array
    {
        return collect($permissionsByKey)
            ->mapWithKeys(fn (string|array $permissions, string $key): array => [
                $key => self::can($user, $permissions),
            ])
            ->all();
    }

    public static function roleLabel(string $role): string
    {
        return match ($role) {
            'super_admin' => 'Super Admin',
            'data_contributor' => 'Data Contributor',
            'bulk_import' => 'Bulk Import',
            'pimpinan' => 'Pimpinan',
            'surveyor' => 'Surveyor',
            default => str($role)->replace(['_', '-'], ' ')->title()->toString(),
        };
    }

    private static function menu(): array
    {
        return [
            [
                'label' => 'Ringkasan',
                'items' => [
                    ['label' => 'Dashboard', 'href' => '/app', 'icon' => 'pi-home'],
                ],
            ],
            [
                'label' => 'Operasional Data',
                'items' => [
                    [
                        'label' => 'Bank Data',
                        'icon' => 'pi-folder',
                        'children' => [
                            ['label' => 'Daftar Data', 'href' => '/app/pembanding', 'icon' => 'pi-list', 'permissions' => ['view_any_data::pembanding']],
                            ['label' => 'Bulk Import', 'href' => '/app/pembanding-imports', 'icon' => 'pi-file-import', 'permissions' => ['bulk_import_data::pembanding']],
                            ['label' => 'Moderasi Data', 'href' => '/app/moderation', 'icon' => 'pi-shield', 'permissions' => ['view_moderation']],
                            ['label' => 'Export Data', 'href' => '/app/export', 'icon' => 'pi-download', 'permissions' => ['view_export']],
                        ],
                    ],
                ],
            ],
            [
                'label' => 'Referensi Data',
                'items' => [
                    [
                        'label' => 'Master Data',
                        'href' => '/app/master-data',
                        'icon' => 'pi-box',
                        'permissions' => ['view_master_data'],
                        'children' => [
                            ['label' => 'Ringkasan', 'href' => '/app/master-data', 'icon' => 'pi-th-large', 'exact' => true, 'permissions' => ['view_master_data']],
                            ...collect(DictionaryTypeMap::publicDefinitions())
                                ->map(fn (array $definition): array => [
                                    'label' => $definition['label'],
                                    'href' => "/app/master-data/{$definition['type']}",
                                    'icon' => $definition['icon'],
                                    'permissions' => ['view_master_data'],
                                ])
                                ->all(),
                        ],
                    ],
                    ['label' => 'Geo Location', 'href' => '/app/geo', 'icon' => 'pi-map', 'permissions' => ['view_geo_data']],
                ],
            ],
            [
                'label' => 'User & Akses',
                'items' => [
                    ['label' => 'Users', 'href' => '/app/users', 'icon' => 'pi-users', 'permissions' => ['view_any_user']],
                    ['label' => 'Undangan Kontributor', 'href' => '/app/data-contributor-invitations', 'icon' => 'pi-send', 'permissions' => ['manage_data_contributor_invitations']],
                    ['label' => 'Access Control', 'href' => '/app/access-control', 'icon' => 'pi-key', 'permissions' => ['view_access_control']],
                ],
            ],
            [
                'label' => 'Sistem',
                'items' => [
                    ['label' => 'Backup Sistem', 'href' => '/app/backup', 'icon' => 'pi-database', 'permissions' => ['view_backup']],
                    ['label' => 'Pengaturan', 'href' => '/app/settings', 'icon' => 'pi-cog', 'permissions' => ['view_settings']],
                    ['label' => 'Activity Logs', 'href' => '/app/activity-logs', 'icon' => 'pi-list', 'permissions' => ['view_activity_log']],
                ],
            ],
        ];
    }
}
