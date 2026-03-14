<div class="w-4/5 mx-auto py-10">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <style>
        .swiper-slide {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: center !important;
        }
    </style>

    <div class="swiper rounded-lg overflow-hidden shadow-lg">
        <div class="swiper-wrapper">

            <!-- Slide 1 -->
            <div class="swiper-slide flex items-center px-40 bg-gray-300 p-10">

                <!-- Texto -->
                <div class="w-1/2">
                    <h2 class="text-5xl font-bold mb-4">Encuentra lo que necesitas</h2>
                    <p class="text-3xl text-gray-700 mb-6">
                        Todo lo que necesites aqui en tu ciudad, con la mejor calidad y al mejor precio.
                    </p>
                    <a href="#" class="bg-green-600 text-white px-8 py-4 rounded hover:bg-green-700">
                        Explorar Tienda
                    </a>
                </div>

                <!-- Imagen -->
                <div class="w-1/2 pt-20 flex justify-end">
                    <img class="h-78 object-contain" src="../public/pngwing.com.png" alt="">
                </div>

            </div>

            <!-- Slide 2 -->
            <div class="swiper-slide px-40 flex items-center bg-gray-300 p-10">

                <div class="w-1/2">
                    <h2 class="text-5xl font-bold mb-4">¡Familias emprendedoras!</h2>
                    <p class="text-3xl text-gray-700 mb-6">
                        Descubre una amplia variedad de productos locales y apoya a los pequeños negocios.
                    </p>
                    <a href="#" class="bg-green-600 text-white px-8 py-4 rounded hover:bg-green-700">
                        Explorar Tienda
                    </a>
                </div>

                <div class="w-1/2 pt-20 flex justify-end">
                    <img class="h-78 object-contain" src="../public/slideruno.png" alt="">
                </div>

            </div>

            <!-- Slide 3 -->
            <div class="swiper-slide px-40 flex items-center bg-gray-300 p-10">

                <div class="w-1/2">
                    <h2 class="text-5xl font-bold mb-4">Productos locales</h2>
                    <p class="text-3xl text-gray-700 mb-6">
                        Descubre una amplia variedad de productos locales y apoya a los pequeños negocios.
                    </p>
                    <a href="#" class="bg-green-600 text-white px-8 py-4 rounded hover:bg-green-700">
                        Explorar Tienda
                    </a>
                </div>

                <div class="w-1/2 pt-20 flex justify-end">
                    <img class="h-78 object-contain" src="../public/primo.png" alt="">
                </div>

            </div>

        </div>


        <div class="swiper-pagination"></div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
</div>

<script>
    const swiper = new Swiper('.swiper', {
        loop: true,
        speed: 1000,
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        autoplay: {
            delay: 3000,
            disableOnInteraction: false,
        },
    });
</script>