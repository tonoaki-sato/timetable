<?php

namespace App\Presenters;

use App\Models\Equipment;

class EquipmentPresenter
{
    public function __construct(
        protected Equipment $equipment
    ) {}

    /**
     * 区分ラベルを取得
     */
    public function typeLabel(): string
    {
        return $this->equipment->type_enum->label();
    }

    /**
     * 持出ルールラベルを取得
     */
    public function checkoutRuleLabel(): string
    {
        return $this->equipment->checkout_rule_enum->label();
    }

    /**
     * フォーマット済み数量表示を取得（例: "約 60 本" または "5 台"）
     */
    public function formattedQuantity(): string
    {
        $prefix = $this->equipment->is_approximate_quantity ? '約 ' : '';

        return $prefix.number_format((int) $this->equipment->quantity).' '.$this->equipment->unit;
    }

    /**
     * 定常保管場所の拠点名を取得
     */
    public function storageLocationName(): ?string
    {
        return $this->equipment->storageLocation?->name;
    }

    /**
     * 画面表示用の解決済み保管・調達ラベルを取得
     */
    public function resolvedStorageLocationLabel(): string
    {
        if ($this->equipment->isRental()) {
            return 'イベント会社手配';
        }

        if ($this->equipment->storageLocation !== null) {
            return $this->equipment->storageLocation->name;
        }

        if ($this->equipment->isPersonal()) {
            return '個人保管（持ち込み）';
        }

        return '未指定';
    }

    /**
     * モーダル表示・編集用のJSONペイロード配列を取得
     *
     * @return array<string, mixed>
     */
    public function toModalPayload(): array
    {
        return [
            'id' => $this->equipment->id,
            'name' => $this->equipment->name,
            'type' => $this->equipment->type,
            'type_label' => $this->typeLabel(),
            'type_short_label' => $this->equipment->type_enum->shortLabel(),
            'type_icon' => $this->equipment->type_enum->icon(),
            'type_badge_classes' => $this->equipment->type_enum->badgeClasses(),
            'checkout_rule' => $this->equipment->checkout_rule,
            'checkout_rule_label' => $this->checkoutRuleLabel(),
            'checkout_rule_short_label' => $this->equipment->checkout_rule_enum->shortLabel(),
            'checkout_rule_icon' => $this->equipment->checkout_rule_enum->icon() ?? '',
            'checkout_rule_badge_classes' => $this->equipment->checkout_rule_enum->badgeClasses(),
            'quantity' => (int) $this->equipment->quantity,
            'is_approximate_quantity' => (bool) $this->equipment->is_approximate_quantity,
            'unit' => $this->equipment->unit,
            'formatted_quantity' => $this->formattedQuantity(),
            'storage_location_id' => $this->equipment->storage_location_id,
            'storage_location_name' => $this->storageLocationName() ?? '',
            'storage_location_is_active' => $this->equipment->storageLocation ? (bool) $this->equipment->storageLocation->is_active : true,
            'resolved_storage_location_label' => $this->resolvedStorageLocationLabel(),
            'description' => $this->equipment->description ?? '',
            'update_url' => route('equipments.update', $this->equipment),
        ];
    }
}
