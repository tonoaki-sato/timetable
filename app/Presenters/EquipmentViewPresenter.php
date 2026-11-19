<?php

namespace App\Presenters;

use App\Models\Equipment;
use App\Models\Timetable;

class EquipmentViewPresenter
{
    /**
     * スコープ有無に応じた資機材画面URL・タイトル・説明文を構築
     *
     * @return array{
     *     scopedTimetable: ?Timetable,
     *     indexUrl: string,
     *     createUrl: string,
     *     storeUrl: string,
     *     reorderUrl: string,
     *     showUrl: string,
     *     editUrl: string,
     *     updateUrl: string,
     *     destroyUrl: string,
     *     indexTitle: string,
     *     indexDesc: string,
     *     createTitle: string,
     *     createDesc: string,
     *     editTitle: string,
     *     editDesc: string
     * }
     */
    public function buildViewMeta(?Timetable $timetable, bool $isTimetableScoped, ?Equipment $equipment = null): array
    {
        $scopedTimetable = $isTimetableScoped ? $timetable : null;

        return [
            'scopedTimetable' => $scopedTimetable,
            'indexUrl' => $scopedTimetable ? route('timetables.equipments.index', $scopedTimetable) : route('equipments.index'),
            'createUrl' => $scopedTimetable ? route('timetables.equipments.create', $scopedTimetable) : route('equipments.create'),
            'storeUrl' => $scopedTimetable ? route('timetables.equipments.store', $scopedTimetable) : route('equipments.store'),
            'reorderUrl' => $scopedTimetable ? route('timetables.equipments.reorder', $scopedTimetable) : route('equipments.reorder'),
            'showUrl' => $equipment ? $equipment->showUrl($scopedTimetable) : '',
            'editUrl' => $equipment ? $equipment->editUrl($scopedTimetable) : '',
            'updateUrl' => $equipment
                ? ($scopedTimetable ? route('timetables.equipments.update', [$scopedTimetable, $equipment]) : route('equipments.update', $equipment))
                : '',
            'destroyUrl' => $equipment ? $equipment->destroyUrl($scopedTimetable) : '',
            'indexTitle' => $scopedTimetable ? '資機材管理 ('.$scopedTimetable->name.')' : '機材・備品一覧',
            'indexDesc' => $scopedTimetable
                ? '「'.$scopedTimetable->name.'」（'.$scopedTimetable->target_date?->format('Y年m月d日').'）運行に使用する備品の台帳および配備状況を管理します。'
                : 'まつり運行全般で使用する自前保有備品、外部レンタル機材、個人持ち込み私物の台帳です。ドラッグ＆ドロップで表示順を変更できます。',
            'createTitle' => $scopedTimetable ? '資機材の新規登録 ('.$scopedTimetable->name.')' : '機材・備品の新規登録',
            'createDesc' => $scopedTimetable
                ? '運行計画「'.$scopedTimetable->name.'」で使用する機材・備品を新規登録します。'
                : '新規機材・備品を台帳に登録します。',
            'editTitle' => $equipment
                ? '資機材編集: '.$equipment->name.($scopedTimetable ? ' ('.$scopedTimetable->name.')' : '')
                : '',
            'editDesc' => $equipment
                ? '備品「'.$equipment->name.'」の基本情報や管理情報を編集します。'
                : '',
        ];
    }
}
