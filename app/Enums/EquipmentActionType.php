<?php

namespace App\Enums;

enum EquipmentActionType: string
{
    case StorageOut = 'storage_out';
    case VendorReceive = 'vendor_receive';
    case StagingMove = 'staging_move';
    case Setup = 'setup';
    case NightStorage = 'night_storage';
    case ReturnStorage = 'return_storage';
    case CollectionStaging = 'collection_staging';
    case VendorSetup = 'vendor_setup';
    case VendorPickup = 'vendor_pickup';
    case Other = 'other';

    /**
     * 日本語ラベルを取得
     */
    public function label(): string
    {
        return match ($this) {
            self::StorageOut => '倉庫出し・移動',
            self::VendorReceive => '業者受取・立会',
            self::StagingMove => '中継・待機移動',
            self::Setup => '会場設置',
            self::NightStorage => '夜間保管移動',
            self::ReturnStorage => '倉庫返還・格納',
            self::CollectionStaging => '業者回収待ち集約',
            self::VendorSetup => 'イベント会社搬入・設営',
            self::VendorPickup => 'イベント会社解体・搬出回収',
            self::Other => 'その他作業',
        };
    }

    /**
     * アイコン文字を取得
     */
    public function icon(): string
    {
        return match ($this) {
            self::StorageOut => '📦',
            self::VendorReceive => '🤝',
            self::StagingMove => '🚚',
            self::Setup => '🛠️',
            self::NightStorage => '🌙',
            self::ReturnStorage => '🔒',
            self::CollectionStaging => '📦',
            self::VendorSetup => '🏢',
            self::VendorPickup => '🚛',
            self::Other => '📝',
        };
    }

    /**
     * セレクトボックス等の選択肢一覧を取得
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}

