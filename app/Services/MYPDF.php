<?php

namespace App\Services;

class MYPDF extends \TCPDF
{
    public function Header() {
        // غیرفعال‌سازی Header پیش‌فرض
    }

    public function Footer() {
        // غیرفعال‌سازی Footer پیش‌فرض
    }

    // اضافه کردن بک‌گراند در هر صفحه
    public function AddBackgroundImage($img_path) {
        // ذخیره موقعیت فعلی
        $this->startTransaction();
        $this->setPageMark();

        // غیر فعال کردن AutoPageBreak برای پوشاندن کل صفحه
        $auto_page_break = $this->getAutoPageBreak();
        $bMargin = $this->getBreakMargin();
        $this->SetAutoPageBreak(false, 0);

        // اندازه کامل صفحه
        $this->Image($img_path, 0, 0, $this->getPageWidth(), $this->getPageHeight(), '', '', '', false, 300, '', false, false, 0);

        // بازیابی تنظیمات قبلی
        $this->SetAutoPageBreak($auto_page_break, $bMargin);
    }
}
