<?php

namespace App\Http\Controllers;

use App\Actions\Staff\DownloadStaffCardsPdfAction;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class StaffPrintController extends Controller
{
    public function __construct(
        protected DownloadStaffCardsPdfAction $downloadStaffCardsPdfAction
    ) {}

    /**
     * スタッフ名札カードの一括PDFダウンロード（またはプレビュー表示）
     */
    public function printCards(Request $request): Response|View
    {
        $this->authorize('viewAny', Staff::class);

        // プレビュー指定時はHTMLプレビュー画面を表示
        if ($request->boolean('preview')) {
            $cards = $this->downloadStaffCardsPdfAction->buildCardsForRequest($request);

            return view('staff.print_cards', [
                'cards' => $cards,
                'title' => 'スタッフ名札カード一括印刷 ('.$cards->count().'名)',
            ]);
        }

        return $this->downloadStaffCardsPdfAction->execute($request);
    }

    /**
     * 単一スタッフの名札カードPDFダウンロード（またはプレビュー表示）
     */
    public function printSingleCard(Staff $staff, Request $request): Response|View
    {
        $this->authorize('view', $staff);

        // プレビュー指定時はHTMLプレビュー画面を表示
        if ($request->boolean('preview')) {
            $cards = $this->downloadStaffCardsPdfAction->buildCardsData(collect([$staff]), 120);

            return view('staff.print_cards', [
                'cards' => $cards,
                'title' => "{$staff->name} 名札カード印刷",
            ]);
        }

        return $this->downloadStaffCardsPdfAction->executeForSingle($staff);
    }
}
