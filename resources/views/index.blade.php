<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بازی دبلنا - جشنواره تخفیف و جوایز</title>
    <!-- Tailwind CSS v4 CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js برای تعاملات -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- فونت وزیرمتن -->
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" type="text/css" />
    <style>
        body { font-family: 'Vazirmatn', sans-serif; }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex flex-col justify-between" x-data="{ selectedCard: null, modalOpen: false }">

<!-- هدر سایت و معرفی اسپانسر -->
<header class="bg-slate-800 border-b border-slate-700 py-4 px-6 shadow-md">
    <div class="max-w-6xl mx-auto flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="flex items-center gap-3">
            <span class="bg-amber-500 text-slate-950 font-bold px-3 py-1 rounded-xl text-lg">دبلنا پلاس</span>
            <span class="text-sm text-slate-400">هیجان بازی، لذت برنده شدن جایزه!</span>
        </div>
        <!-- بخش معرفی برند اسپانسر -->
        <div class="bg-slate-700/50 border border-slate-600 px-4 py-2 rounded-2xl flex items-center gap-3">
            <span class="text-xs text-amber-400">اسپانسر این دوره:</span>
            <span class="font-bold text-sm text-white">برند شوینده و آرایشی لوتوس</span>
            <span class="bg-emerald-500/20 text-emerald-400 text-xs px-2 py-0.5 rounded-full font-medium">۵۰٪ تخفیف خرید اول</span>
        </div>
    </div>
</header>

<!-- محتوای اصلی -->
<main class="max-w-6xl mx-auto px-4 py-8 w-full grid grid-cols-1 lg:grid-cols-3 gap-8">

    <!-- ستون سمت راست: اطلاعات و جوایز -->
    <div class="lg:col-span-1 space-y-6">
        <div class="bg-slate-800 border border-slate-700 rounded-3xl p-6 shadow-xl">
            <h2 class="text-xl font-bold text-amber-400 mb-3">🎯 جوایز این هفته چیست؟</h2>
            <p class="text-sm text-slate-300 leading-relaxed mb-4">
                با شرکت در این دور از بازی دبلنا، علاوه بر رقابت هیجان‌انگیز، از جوایز ویژه برندهای همکار بهره‌مند شوید:
            </p>
            <ul class="space-y-3 text-sm">
                <li class="flex items-center gap-2 bg-slate-700/40 p-3 rounded-xl">
                    <span class="text-amber-400">🎁</span>
                    <span>بن تخفیف ۵۰ درصدی خرید محصولات لوتوس</span>
                </li>
                <li class="flex items-center gap-2 bg-slate-700/40 p-3 rounded-xl">
                    <span class="text-amber-400">⭐</span>
                    <span>اشتراک ۳ ماهه رایگان سرویس‌های آنلاین</span>
                </li>
                <li class="flex items-center gap-2 bg-slate-700/40 p-3 rounded-xl">
                    <span class="text-amber-400">💰</span>
                    <span>وجه نقد و اعتبار خرید کیف پول</span>
                </li>
            </ul>
        </div>

        <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-3xl p-6 text-slate-950 shadow-xl">
            <h3 class="font-extrabold text-lg mb-2">چرا برندها عاشق این بازی هستند؟</h3>
            <p class="text-xs leading-relaxed font-medium">
                شرکت‌ها می‌توانند با معرفی محصول خود به عنوان جایزه، هزاران کاربر فعال و علاقه‌مند را جذب کرده و وفاداری مشتریان خود را افزایش دهند.
            </p>
        </div>
    </div>

    <!-- ستون وسط و چپ: انتخاب کارت و شروع بازی -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-slate-800 border border-slate-700 rounded-3xl p-6 shadow-xl">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold">انتخاب کارت بازی (دبلنا)</h2>
                <span class="text-xs bg-slate-700 px-3 py-1 rounded-full text-slate-300">هزینه هر کارت: ۱۰,۰۰۰ تومان</span>
            </div>

            <!-- گرید کارت‌های بازی قابل خرید -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                <!-- کارت شماره ۱ -->
                <div @click="selectedCard = 1" :class="selectedCard === 1 ? 'border-amber-500 bg-slate-700/80' : 'border-slate-700 bg-slate-700/30'" class="border-2 rounded-2xl p-4 cursor-pointer transition hover:border-amber-400">
                    <div class="flex justify-between items-center mb-3 text-xs text-slate-400">
                        <span>کارت شماره #۱۰۱</span>
                        <span class="text-amber-400 font-bold">۱۰,۰۰۰ تومان</span>
                    </div>
                    <!-- پیش‌نمایش مینیاتوری کارت دبلنا -->
                    <div class="grid grid-cols-5 gap-1.5 text-center text-xs font-mono bg-slate-900/60 p-3 rounded-xl">
                        <span class="bg-slate-800 py-1 rounded">4</span>
                        <span class="bg-slate-800 py-1 rounded">17</span>
                        <span class="bg-slate-800 py-1 rounded">-</span>
                        <span class="bg-slate-800 py-1 rounded">52</span>
                        <span class="bg-slate-800 py-1 rounded">78</span>
                        <span class="bg-slate-800 py-1 rounded">12</span>
                        <span class="bg-slate-800 py-1 rounded">-</span>
                        <span class="bg-slate-800 py-1 rounded">35</span>
                        <span class="bg-slate-800 py-1 rounded">61</span>
                        <span class="bg-slate-800 py-1 rounded">84</span>
                    </div>
                </div>

                <!-- کارت شماره ۲ -->
                <div @click="selectedCard = 2" :class="selectedCard === 2 ? 'border-amber-500 bg-slate-700/80' : 'border-slate-700 bg-slate-700/30'" class="border-2 rounded-2xl p-4 cursor-pointer transition hover:border-amber-400">
                    <div class="flex justify-between items-center mb-3 text-xs text-slate-400">
                        <span>کارت شماره #۱۰۲</span>
                        <span class="text-amber-400 font-bold">۱۰,۰۰۰ تومان</span>
                    </div>
                    <!-- پیش‌نمایش مینیاتوری کارت دبلنا -->
                    <div class="grid grid-cols-5 gap-1.5 text-center text-xs font-mono bg-slate-900/60 p-3 rounded-xl">
                        <span class="bg-slate-800 py-1 rounded">7</span>
                        <span class="bg-slate-800 py-1 rounded">-</span>
                        <span class="bg-slate-800 py-1 rounded">28</span>
                        <span class="bg-slate-800 py-1 rounded">44</span>
                        <span class="bg-slate-800 py-1 rounded">90</span>
                        <span class="bg-slate-800 py-1 rounded">-</span>
                        <span class="bg-slate-800 py-1 rounded">19</span>
                        <span class="bg-slate-800 py-1 rounded">33</span>
                        <span class="bg-slate-800 py-1 rounded">65</span>
                        <span class="bg-slate-800 py-1 rounded">81</span>
                    </div>
                </div>
            </div>

            <!-- دکمه پرداخت و ورود به بازی -->
            <button :disabled="!selectedCard" @click="modalOpen = true" :class="selectedCard ? 'bg-amber-500 text-slate-950 hover:bg-amber-400 cursor-pointer' : 'bg-slate-700 text-slate-500 cursor-not-allowed'" class="w-full font-bold py-3.5 rounded-2xl transition shadow-lg text-center block">
                پرداخت و دریافت کارت بازی
            </button>
        </div>
    </div>
</main>

<!-- مودال تایید پرداخت (نمایشی) -->
<div x-show="modalOpen" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 z-50" style="display: none;">
    <div class="bg-slate-800 border border-slate-700 rounded-3xl p-6 max-w-md w-full text-center space-y-4 shadow-2xl">
        <div class="w-16 h-16 bg-emerald-500/20 text-emerald-400 rounded-full flex items-center justify-center mx-3 text-2xl font-bold mx-auto">✓</div>
        <h3 class="text-xl font-bold text-white">کارت با موفقیت ثبت شد!</h3>
        <p class="text-sm text-slate-300">شما با موفقیت کارت شماره <span x-text="selectedCard" class="font-bold text-amber-400"></span> را خریداری کردید. آماده شروع بازی و برنده شدن جوایز اسپانسر باشید.</p>
        <button @click="modalOpen = false" class="w-full bg-slate-700 hover:bg-slate-600 text-white font-bold py-3 rounded-xl transition">
            ورود به اتاق بازی
        </button>
    </div>
</div>

<!-- فوتر -->
<footer class="bg-slate-800 border-t border-slate-700 py-4 text-center text-xs text-slate-400">
    تمامی حقوق محفوظ است | طراحی شده برای جذب کاربران و مشارکت برندها
</footer>

</body>
</html>
