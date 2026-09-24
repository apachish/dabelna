<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بازی دبلنا - جشنواره جوایز و مشارکت برندها</title>
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
<body class="bg-slate-900 text-slate-100 min-h-screen flex flex-col justify-between" x-data="{ selectedCard: null, modalOpen: false, sponsorModal: false, sponsorSuccess: false }">

<!-- هدر سایت و معرفی اسپانسر فعلی -->
<header class="bg-slate-800 border-b border-slate-700 py-4 px-6 shadow-md sticky top-0 z-40">
    <div class="max-w-6xl mx-auto flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="flex items-center gap-3">
            <span class="bg-amber-500 text-slate-950 font-bold px-3 py-1 rounded-xl text-lg">دبلنا پلاس</span>
            <span class="text-sm text-slate-400">هیجان بازی، لذت برنده شدن جایزه!</span>
        </div>

        <div class="flex items-center gap-4">
            <!-- بخش معرفی برند اسپانسر -->
            <div class="bg-slate-700/50 border border-slate-600 px-4 py-1.5 rounded-2xl flex items-center gap-3">
                <span class="text-xs text-amber-400">اسپانسر ویژه:</span>
                <span class="font-bold text-sm text-white">برند لوتوس</span>
            </div>
            <!-- دکمه ورود شرکت‌ها به عنوان اسپانسر -->
            <button @click="sponsorModal = true" class="bg-slate-700 hover:bg-slate-600 text-amber-400 border border-amber-500/30 text-xs font-bold px-3.5 py-2 rounded-xl transition">
                ثبت‌نام شرکت‌ها (اسپانسر شوید)
            </button>
        </div>
    </div>
</header>

<!-- محتوای اصلی -->
<main class="max-w-6xl mx-auto px-4 py-8 w-full grid grid-cols-1 lg:grid-cols-3 gap-8">

    <!-- ستون سمت راست: جوایز و برندگان اخیر -->
    <div class="lg:col-span-1 space-y-6">
        <!-- جوایز دوره -->
        <div class="bg-slate-800 border border-slate-700 rounded-3xl p-6 shadow-xl">
            <h2 class="text-xl font-bold text-amber-400 mb-3">🎯 جوایز این هفته چیست؟</h2>
            <ul class="space-y-3 text-sm">
                <li class="flex items-center gap-2 bg-slate-700/40 p-3 rounded-xl">
                    <span class="text-amber-400">🎁</span>
                    <span>بن تخفیف ۵۰٪ خرید محصولات لوتوس</span>
                </li>
                <li class="flex items-center gap-2 bg-slate-700/40 p-3 rounded-xl">
                    <span class="text-amber-400">⭐</span>
                    <span>اشتراک ۳ ماهه رایگان پلتفرم‌ها</span>
                </li>
            </ul>
        </div>

        <!-- برندگان اخیر بازی (اعتمادسازی و ایجاد هیجان) -->
        <div class="bg-slate-800 border border-slate-700 rounded-3xl p-6 shadow-xl">
            <h3 class="text-lg font-bold text-emerald-400 mb-3">🏆 آخرین برندگان بازی</h3>
            <div class="space-y-3 text-xs">
                <div class="bg-slate-700/30 p-3 rounded-2xl flex justify-between items-center border border-slate-700">
                    <div>
                        <p class="font-bold text-white">علی رضایی</p>
                        <p class="text-slate-400 text-[10px] mt-0.5">کارت #۱۰۲</p>
                    </div>
                    <span class="bg-emerald-500/20 text-emerald-400 px-2.5 py-1 rounded-lg font-medium">تخفیف ۵۰٪ لوتوس</span>
                </div>
                <div class="bg-slate-700/30 p-3 rounded-2xl flex justify-between items-center border border-slate-700">
                    <div>
                        <p class="font-bold text-white">سارا احمدی</p>
                        <p class="text-slate-400 text-[10px] mt-0.5">کارت #۸۸</p>
                    </div>
                    <span class="bg-emerald-500/20 text-emerald-400 px-2.5 py-1 rounded-lg font-medium">اشتراک ۳ ماهه</span>
                </div>
                <div class="bg-slate-700/30 p-3 rounded-2xl flex justify-between items-center border border-slate-700">
                    <div>
                        <p class="font-bold text-white">محمد کریمی</p>
                        <p class="text-slate-400 text-[10px] mt-0.5">کارت #۴۵</p>
                    </div>
                    <span class="bg-emerald-500/20 text-emerald-400 px-2.5 py-1 rounded-lg font-medium">تخفیف ۳۰٪ خرید</span>
                </div>
            </div>
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

<!-- مودال ثبت‌نام خرید کارت -->
<div x-show="modalOpen" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 z-50" style="display: none;">
    <div class="bg-slate-800 border border-slate-700 rounded-3xl p-6 max-w-md w-full text-center space-y-4 shadow-2xl">
        <div class="w-16 h-16 bg-emerald-500/20 text-emerald-400 rounded-full flex items-center justify-center mx-auto text-2xl font-bold">✓</div>
        <h3 class="text-xl font-bold text-white">کارت با موفقیت ثبت شد!</h3>
        <p class="text-sm text-slate-300">شما کارت شماره <span x-text="selectedCard" class="font-bold text-amber-400"></span> را خریداری کردید. آماده شروع بازی و برنده شدن جوایز باشید.</p>
        <button @click="modalOpen = false" class="w-full bg-slate-700 hover:bg-slate-600 text-white font-bold py-3 rounded-xl transition">
            ورود به اتاق بازی
        </button>
    </div>
</div>

<!-- مودال فرم درخواست اسپانسر (مخصوص شرکت‌ها و برندها) -->
<div x-show="sponsorModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 z-50" style="display: none;">
    <div class="bg-slate-800 border border-slate-700 rounded-3xl p-6 max-w-lg w-full space-y-4 shadow-2xl text-right">
        <div class="flex justify-between items-center border-b border-slate-700 pb-3">
            <h3 class="text-lg font-bold text-amber-400">فرم درخواست مشارکت به عنوان اسپانسر</h3>
            <button @click="sponsorModal = false; sponsorSuccess = false;" class="text-slate-400 hover:text-white text-lg font-bold">✕</button>
        </div>

        <!-- حالت موفقیت ارسال فرم -->
        <template x-if="sponsorSuccess">
            <div class="py-8 text-center space-y-3">
                <div class="w-14 h-14 bg-emerald-500/20 text-emerald-400 rounded-full flex items-center justify-center mx-auto text-xl font-bold">✓</div>
                <h4 class="font-bold text-white text-lg">درخواست شما ثبت شد!</h4>
                <p class="text-xs text-slate-300">کارشناسان ما جهت هماهنگیِ جوایز و قرار گرفتن برند شما در پلتفرم به زودی تماس خواهند گرفت.</p>
                <button @click="sponsorModal = false; sponsorSuccess = false;" class="w-full bg-slate-700 hover:bg-slate-600 text-white font-bold py-2.5 rounded-xl text-sm transition mt-4">بازگشت</button>
            </div>
        </template>

        <!-- حالت فرم اصلی -->
        <template x-if="!sponsorSuccess">
            <form @submit.prevent="sponsorSuccess = true" class="space-y-3 text-xs">
                <p class="text-slate-300 mb-2">با ارائه تخفیف یا اشتراک محصولات خود به عنوان جایزه، هزاران کاربر فعال را جذب کنید.</p>

                <div>
                    <label class="block text-slate-400 mb-1">نام شرکت / برند</label>
                    <input type="text" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white focus:outline-none focus:border-amber-500" placeholder="مثلا: فروشگاه اینترنتی لوتوس">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div>
                        <label class="block text-slate-400 mb-1">نام شخص رابط</label>
                        <input type="text" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white focus:outline-none focus:border-amber-500" placeholder="نام و نام خانوادگی">
                    </div>
                    <div>
                        <label class="block text-slate-400 mb-1">شماره تماس (موبایل)</label>
                        <input type="tel" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white focus:outline-none focus:border-amber-500" placeholder="0912xxxxxxx">
                    </div>
                </div>

                <div>
                    <label class="block text-slate-400 mb-1">نوع جایزه‌ای که پیشنهاد می‌دهید</label>
                    <select class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white focus:outline-none focus:border-amber-500">
                        <option>درصد تخفیف خرید کالا / خدمات</option>
                        <option>اشتراک رایگان سرویس (ماهانه/فصلی)</option>
                        <option>کارت هدیه نقدی / اعتبار کیف پول</option>
                    </select>
                </div>

                <div>
                    <label class="block text-slate-400 mb-1">توضیحات و جزئیات جایزه</label>
                    <textarea rows="2" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white focus:outline-none focus:border-amber-500" placeholder="مثلا: ۵۰ عدد کد تخفیف ۵۰ درصدی..."></textarea>
                </div>

                <button type="submit" class="w-full bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold py-3 rounded-xl transition mt-3">
                    ارسال درخواست همکاری
                </button>
            </form>
        </template>
    </div>
</div>

<!-- فوتر -->
<footer class="bg-slate-800 border-t border-slate-700 py-4 text-center text-xs text-slate-400 mt-8">
    تمامی حقوق محفوظ است | پلتفرم بازی و رقابت دبلنا پلاس
</footer>

</body>
</html>
