<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Arr;

class AdminAccess
{
    public const ACCESS_ADMIN = 'can_access_admin';

    public static function permissions(): array
    {
        return [
            self::ACCESS_ADMIN,
            'view_admin_dashboard',
            'view_access_control',
            'view_admin_search',
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
        if (! $user || ! self::can($user, self::ACCESS_ADMIN)) {
            return [];
        }

        return collect(self::menu())
            ->map(function (array $section) use ($user): array {
                $section['items'] = collect($section['items'])
                    ->filter(fn (array $item): bool => self::can($user, $item['permissions']))
                    ->map(fn (array $item): array => Arr::except($item, ['permissions']))
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

    private static function menu(): array
    {
        return [
            [
                'label' => 'Overview',
                'items' => [
                    ['label' => 'Dashboard', 'href' => '/admin', 'icon' => 'pi-home', 'permissions' => ['view_admin_dashboard']],
                ],
            ],
            [
                'label' => 'User Management',
                'items' => [
                    ['label' => 'Users', 'href' => '/admin/users', 'icon' => 'pi-users', 'permissions' => ['view_any_user']],
                    ['label' => 'Data Contributor Invitations', 'href' => '/admin/data-contributor-invitations', 'icon' => 'pi-send', 'permissions' => ['manage_data_contributor_invitations']],
                    ['label' => 'Access Control', 'href' => '/admin/access-control', 'icon' => 'pi-key', 'permissions' => ['view_access_control']],
                ],
            ],
            [
                'label' => 'Data Operations',
                'items' => [
                    ['label' => 'Moderation Desk', 'href' => '/admin/moderation', 'icon' => 'pi-shield', 'permissions' => ['view_moderation']],
                    ['label' => 'Appraisal Data', 'href' => '/admin/pembanding', 'icon' => 'pi-database', 'permissions' => ['view_any_data::pembanding']],
                    ['label' => 'Master Data', 'href' => '/admin/master-data', 'icon' => 'pi-box', 'permissions' => ['view_master_data']],
                    ['label' => 'Geo Data', 'href' => '/admin/geo', 'icon' => 'pi-map', 'permissions' => ['view_geo_data']],
                ],
            ],
            [
                'label' => 'System',
                'items' => [
                    ['label' => 'Export Data', 'href' => '/admin/export', 'icon' => 'pi-download', 'permissions' => ['view_export']],
                    ['label' => 'System Backup', 'href' => '/admin/backup', 'icon' => 'pi-archive', 'permissions' => ['view_backup']],
                    ['label' => 'Settings', 'href' => '/admin/settings', 'icon' => 'pi-cog', 'permissions' => ['view_settings']],
                    ['label' => 'Activity Logs', 'href' => '/admin/activity-logs', 'icon' => 'pi-list', 'permissions' => ['view_activity_log']],
                    ['label' => 'Search', 'href' => '/admin/search', 'icon' => 'pi-search', 'permissions' => ['view_admin_search']],
                ],
            ],
        ];
    }
}
