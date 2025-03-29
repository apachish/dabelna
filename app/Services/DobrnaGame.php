<?php

namespace App\Services;


use TCPDF;

class DobrnaGame {
    private $numbers;
    private $cards;

    public function __construct($players = 2) {
        $this->numbers = range(1, 90);
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
        $numbers = range(1, 90);
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
            if (empty(array_diff($card, array_slice($this->numbers, 0, count($this->numbers))))) {
                return $index + 1;
            }
        }
        return false;
    }

    public function getCards() {
        return $this->cards;
    }

    public function generatePDF() {
        $pdf = new TCPDF();
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Dobrna Game');
        $pdf->SetTitle('Dobrna Cards');
        $pdf->AddPage();

        $pdf->SetFont('helvetica', '', 12);
        foreach ($this->cards as $index => $card) {
            $pdf->Cell(0, 10, "کارت بازیکن " . ($index + 1), 0, 1, 'C');
            foreach (array_chunk($card, 5) as $row) {
                $pdf->Cell(0, 10, implode(' | ', $row), 0, 1, 'C');
            }
            $pdf->Ln();
        }

        $pdf->Output('dobrna_cards.pdf', 'D');
    }
}

$game = new DobrnaGame(3);
echo "کارت‌های بازیکنان ایجاد شد و در PDF ذخیره شد.\n";
$game->generatePDF();

