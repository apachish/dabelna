<?php

namespace App\Services;

use TCPDF;

class DobrnaCardGenerator
{
    public function generateCardPdf(array $cards)
    {
        $pdf = new TCPDF();
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Dobrna Game');
        $pdf->SetTitle('Dobrna Cards');
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(TRUE, 10);
        $pdf->SetFont('iransans', '', 12);
        $pdf->setRTL(true); // فعال‌سازی نوشتار راست‌به‌چپ
        $pdf->setFontSubsetting(true);
        $pdf->SetFont('iransans', '', 14, '', true);

        foreach ($cards as $card) {
            $pdf->AddPage();
            $this->renderCard($pdf, $card);
        }

        return $pdf->Output(public_path('dobrna_cards.pdf'), 'F'); // 'I' = نمایش در مرورگر
    }

    private function renderCard($pdf, array $numbers)
    {
        $pdf->Cell(0, 10, ' کارت بازیکن '. 1, 0, 1, 'C');
        $pdf->Ln(5);

        // ساخت جدول 3x9
        $rows = 3;
        $cols = 9;
        $grid = array_fill(0, $rows, array_fill(0, $cols, ''));

        shuffle($numbers);
        $chunks = array_chunk($numbers, 5);

        for ($i = 0; $i < 3; $i++) {
            $colsToFill = array_rand(array_fill(0, $cols, 1), 5);
            foreach (array_values($colsToFill) as $j => $col) {
                $grid[$i][$col] = $chunks[$i][$j];
            }
        }

        $cellWidth = 20;
        $cellHeight = 15;

        foreach ($grid as $row) {
            foreach ($row as $cell) {
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                if ($cell === '') {
                    // خانه خالی با رنگ مشکی
                    $pdf->SetFillColor(66, 133, 244);
                    $pdf->SetTextColor(0, 131, 143); // متن خالی و سیاه
                    $pdf->Cell($cellWidth, $cellHeight, ':-)', 1, 0, 'C', true);
                } else {
                    $pdf->SetFillColor(255, 255, 255);
                    $pdf->SetTextColor(0, 0, 0);
                    $pdf->Cell($cellWidth, $cellHeight, $cell, 1, 0, 'C', true);
                }
            }
            $pdf->Ln();
        }
    }
}
