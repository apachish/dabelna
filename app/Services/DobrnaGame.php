<?php

namespace App\Services;


use Apachish\Dabelna\App\Models\Card;
use Apachish\Dabelna\App\Models\Game;
use TCPDF;

class DobrnaGame {
    private $numbers;
    private $cards;

    public function __construct($players = 2) {
        $this->numbers = range(1, 90);
        shuffle($this->numbers);

        $this->generateCards($players);
    }
    function generateCard() {
        $card = array_fill(0, 3, array_fill(0, 9, null)); // 3 rows × 9 columns

        // مرحله 1: تولید عددهای هر ستون با توجه به بازه مربوطه
        $columns = [];

        for ($i = 0; $i < 9; $i++) {
            $min = $i === 0 ? 1 : $i * 10;
            $max = $i === 8 ? 90 : ($i * 10 + 9);

            // حداکثر 3 عدد از هر ستون، برای توزیع متعادل‌تر بعداً فقط 1 یا 2 عدد استفاده می‌کنیم
            $count = 3;
            $columns[$i] = [];

            $range = range($min, $max);
            shuffle($range);
            $columns[$i] = array_slice($range, 0, $count);
            sort($columns[$i]); // مرتب برای قرارگیری در کارت
        }

        // مرحله 2: پر کردن کارت
        // در هر ستون حداکثر 3 جایگاه داریم (چون 3 ردیفه)
        foreach ($columns as $colIndex => $numbers) {
            $rows = [0, 1, 2];
            shuffle($rows); // ترتیب رندوم برای توزیع اعداد در ردیف‌ها

            for ($i = 0; $i < count($numbers); $i++) {
                $row = $rows[$i];
                $card[$row][$colIndex] = $numbers[$i];
            }
        }

        // مرحله 3: اطمینان از اینکه هر ردیف دقیقاً ۵ عدد داره
        foreach ($card as $rowIndex => &$row) {
            $filled = array_filter($row, fn($v) => $v !== null);
            $count = count($filled);

            if ($count > 5) {
                // حذف اضافه‌ها به‌صورت تصادفی
                $nonEmptyCols = array_keys(array_filter($row, fn($v) => $v !== null));
                shuffle($nonEmptyCols);
                $toRemove = $count - 5;

                for ($i = 0; $i < $toRemove; $i++) {
                    $row[$nonEmptyCols[$i]] = null;
                }

            } elseif ($count < 5) {
                // در موارد نادر ممکنه کمتر از ۵ باشه → می‌شه الگوریتم را پیچیده‌تر کرد، فعلاً ساده نگه می‌داریم
                // یا کارت جدید بسازیم
                return generateDabirnaCard(); // بازسازی کارت
            }
        }

        return $card;
    }
    private function generateCards($players) {
        $this->cards = [];
        for ($i = 0; $i < $players; $i++) {
            $this->cards[$i] = $this->generateCard();
        }
    }

//    private function generateCard() {
//        $card = [];
//        $numbers = range(1, 90);
//        shuffle($numbers);
//        for ($i = 0; $i < 15; $i++) {
//            $card[] = array_pop($numbers);
//        }
//        sort($card);
//        return $card;
//    }

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

    public function generatePDF($path,$game) {



        foreach ($this->cards as $index => $card) {
            $pdf = new MYPDF('L', 'mm', 'A5', true, 'UTF-8', false);
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('Dobrna Game');
            $pdf->SetTitle('Dobrna Cards');
            $pdf->SetMargins(13, 30, 10);
            $pdf->SetAutoPageBreak(TRUE, 10);
            $pdf->SetFont('iransans', '', 14);
//            $pdf->setRTL(true); // فعال‌سازی نوشتار راست‌به‌چپ
            $pdf->setFontSubsetting(true);
            $pdf->SetFont('iransans', '', 14, '', true);
            $pdf->AddPage();
            $img_file = public_path('back.jpg');
            $pdf->AddBackgroundImage($img_file);

            $i = $index + 1;
            $marked =  $this->renderCard($i,$pdf, $card,data_get($game,'title'),data_get($game,'id'));
            $pdf->Output(storage_path($path."کارت_بازیکن_".$i.'.pdf'), 'F');

            Card::create([
                "title"=> "کارت بازی".$i,
               "numbers"=>json_encode($card),
                "marked"=>json_encode($marked),
                "game_id"=>data_get($game,'id'),
                "file"=>$path."کارت_بازیکن_".$i.'.pdf'
            ]);

        }

    }

    private function renderCard($index, $pdf, array $numbers,$text=":-)",$game_id)
    {
        $pdf->Cell(0, 10, ' بازی '. $game_id, 0, 1, 'C');
        $pdf->Cell(0, 10, ' کارت بازی '. $index, 0, 1, 'C');
        $pdf->Ln(5);

        // ساخت جدول 3x9
        $rows = 3;
        $cols = 9;
        $grid = array_fill(0, $rows, array_fill(0, $cols, ''));

//        shuffle($numbers);
//        $chunks = array_chunk($numbers, 5);


//        for ($i = 0; $i < 3; $i++) {
//            $colsToFill = array_rand(array_fill(0, $cols, 1), 5);
//            foreach (array_values($colsToFill) as $j => $col) {
//                $grid[$i][$col] = $chunks[$i][$j];
//            }
//        }

        $cellWidth = 20;
        $cellHeight = 15;
        $marked = [];
logger("numbers",[$numbers]);

        foreach ($numbers as $i=>$row) {

            foreach (array_reverse($row) as $j=>$cell) {

                $x = $pdf->GetX();
                $y = $pdf->GetY();
                if (!$cell) {
                    // خانه خالی با رنگ مشکی
                    $pdf->SetFillColor(160, 160, 160);
                    $pdf->SetTextColor(255, 255, 255); // متن خالی و سیاه
                    $pdf->Cell($cellWidth, $cellHeight, $text, 1, 0, 'C', true);
                } else {
                    $pdf->SetDrawColor(153, 0, 0); // قاب قرمز
                    $pdf->SetFillColor(255, 255, 255); // زمینه سفید
                    $pdf->SetTextColor(0, 0, 0); // متن مشکی

                    $pdf->Cell($cellWidth, $cellHeight, $cell, 1, 0, 'C', true);
                }
                $marked[$i][$j] = $cell;
            }
            $pdf->Ln();
        }
        return $marked;
    }


}



