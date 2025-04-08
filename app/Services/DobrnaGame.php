<?php

namespace App\Services;


use Apachish\Dabelna\App\Models\Card;
use Apachish\Dabelna\App\Models\Game;
use TCPDF;

class DobrnaGame {
    private $numbers;
    private $cards;

    public function __construct($players = 2) {
        $this->numbers = range(1, 99);
        shuffle($this->numbers);

        $this->generateCards($players);
    }

    private function generateCards($players) {
        $this->cards = [];
        for ($i = 0; $i < $players; $i++) {
            $this->cards[$i] = $this->generateCard();
        }
    }

    private function generateCard() {
        $card = [];
        $numbers = range(1, 99);
        shuffle($numbers);
        for ($i = 0; $i < 15; $i++) {
            $card[] = array_pop($numbers);
        }
        sort($card);
        return $card;
    }

    public function drawNumber() {
        return array_shift($this->numbers);
    }

    public function checkWinner() {
        foreach ($this->cards as $index => $card) {
            logger("qq",[empty(array_diff($card, array_slice($this->numbers, 0, count($this->numbers)))),
                array_slice($this->numbers, 0, count($this->numbers)),
                $card,
                empty(array_diff($card, array_slice($this->numbers, 0, count($this->numbers))))]
            );
            if (empty(array_diff($card, array_slice($this->numbers, 0, count($this->numbers))))) {
                return $index + 1;
            }
        }
        return false;
    }

    public function getCards() {
        return $this->cards;
    }

    public function generatePDF($path,$game_id,$type) {



        foreach ($this->cards as $index => $card) {
            $pdf = new TCPDF();
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('Dobrna Game');
            $pdf->SetTitle('Dobrna Cards');
            $pdf->SetMargins(10, 10, 10);
            $pdf->SetAutoPageBreak(TRUE, 10);
            $pdf->SetFont('iransans', '', 14);
            $pdf->setRTL(true); // فعال‌سازی نوشتار راست‌به‌چپ
            $pdf->setFontSubsetting(true);
            $pdf->SetFont('iransans', '', 14, '', true);
            $pdf->AddPage();

            $i = $index + 1;
            $marked =  $this->renderCard($i,$pdf, $card,Game::$types[$type]);
            $pdf->Output(storage_path($path."کارت_بازیکن_".$i.'.pdf'), 'F');

            Card::create([
               "numbers"=>json_encode($card),
                "marked"=>json_encode($marked),
                "game_id"=>$game_id,
                "file"=>storage_path($path."کارت_بازیکن_".$i.'.pdf')
            ]);

        }

    }

    private function renderCard($index, $pdf, array $numbers,$text=":-)")
    {
        $pdf->Cell(0, 10, ' کارت بازیکن '. $index, 0, 1, 'C');
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
        $marked = [];
        foreach ($grid as $i=>$row) {

            foreach ($row as $j=>$cell) {
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                if ($cell === '') {
                    // خانه خالی با رنگ مشکی
                    $pdf->SetFillColor(66, 133, 244);
                    $pdf->SetTextColor(255, 255, 255); // متن خالی و سیاه
                    $pdf->Cell($cellWidth, $cellHeight, $text, 1, 0, 'C', true);
                } else {
                    $pdf->SetFillColor(255, 255, 255);
                    $pdf->SetTextColor(0, 0, 0);
                    $pdf->Cell($cellWidth, $cellHeight, $cell, 1, 0, 'C', true);
                }
                $marked[$i][$j] = $cell;
            }
            $pdf->Ln();
        }
        return $marked;
    }

}



