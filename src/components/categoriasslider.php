<?php
include("../config/conexion.php");
$sql = "SELECT * FROM categories LIMIT 5";
$result = mysqli_query($conn, $sql);
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

<div class="relative w-4/5 mx-auto py-10">
    <div class="swiper mySwiper">
        <h3 class="text-2xl font-bold mb-3">Todas las categorías</h3>
        <div class="swiper-wrapper">

            <!-- Vehículos -->
            <div class="swiper-slide !flex !items-center !justify-center">
                <a href="#" class="flex flex-col gap-3 p-4 w-full border border-gray-200 rounded-xl bg-white hover:border-gray-400 transition-colors no-underline text-inherit">
                    <div class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center text-lg text-gray-600">
                        <i class="bi bi-truck"></i>
                    </div>
                    <div class="flex justify-between items-center text-sm font-medium text-gray-800">
                        <span>Vehículos</span>
                        <i class="bi bi-arrow-right text-gray-400"></i>
                    </div>
                </a>
            </div>

            <!-- Propiedades -->
            <div class="swiper-slide !flex !items-center !justify-center">
                <a href="#" class="flex flex-col gap-3 p-4 w-full border border-gray-200 rounded-xl bg-white hover:border-gray-400 transition-colors no-underline text-inherit">
                    <div class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center text-lg text-gray-600">
                        <i class="bi bi-house"></i>
                    </div>
                    <div class="flex justify-between items-center text-sm font-medium text-gray-800">
                        <span>Propiedades</span>
                        <i class="bi bi-arrow-right text-gray-400"></i>
                    </div>
                </a>
            </div>

            <!-- Alquileres -->
            <div class="swiper-slide !flex !items-center !justify-center">
                <a href="#" class="flex flex-col gap-3 p-4 w-full border border-gray-200 rounded-xl bg-white hover:border-gray-400 transition-colors no-underline text-inherit">
                    <div class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center text-lg text-gray-600">
                        <i class="bi bi-key"></i>
                    </div>
                    <div class="flex justify-between items-center text-sm font-medium text-gray-800">
                        <span>Alquileres</span>
                        <i class="bi bi-arrow-right text-gray-400"></i>
                    </div>
                </a>
            </div>

            <!-- Electrónica -->
            <div class="swiper-slide !flex !items-center !justify-center">
                <a href="#" class="flex flex-col gap-3 p-4 w-full border border-gray-200 rounded-xl bg-white hover:border-gray-400 transition-colors no-underline text-inherit">
                    <div class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center text-lg text-gray-600">
                        <i class="bi bi-laptop"></i>
                    </div>
                    <div class="flex justify-between items-center text-sm font-medium text-gray-800">
                        <span>Electrónica</span>
                        <i class="bi bi-arrow-right text-gray-400"></i>
                    </div>
                </a>
            </div>

            <!-- Celulares -->
            <div class="swiper-slide !flex !items-center !justify-center">
                <a href="#" class="flex flex-col gap-3 p-4 w-full border border-gray-200 rounded-xl bg-white hover:border-gray-400 transition-colors no-underline text-inherit">
                    <div class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center text-lg text-gray-600">
                        <i class="bi bi-phone"></i>
                    </div>
                    <div class="flex justify-between items-center text-sm font-medium text-gray-800">
                        <span>Celulares</span>
                        <i class="bi bi-arrow-right text-gray-400"></i>
                    </div>
                </a>
            </div>
            <div class="swiper-slide !flex !items-center !justify-center">
                <a href="#" class="flex flex-col gap-3 p-4 w-full border border-gray-200 rounded-xl bg-white hover:border-gray-400 transition-colors no-underline text-inherit">
                    <div class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center text-lg text-gray-600">
                        <i class="bi bi-phone"></i>
                    </div>
                    <div class="flex justify-between items-center text-sm font-medium text-gray-800">
                        <span>Celulares</span>
                        <i class="bi bi-arrow-right text-gray-400"></i>
                    </div>
                </a>
            </div>
            <div class="swiper-slide !flex !items-center !justify-center">
                <a href="#" class="flex flex-col gap-3 p-4 w-full border border-gray-200 rounded-xl bg-white hover:border-gray-400 transition-colors no-underline text-inherit">
                    <div class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center text-lg text-gray-600">
                        <i class="bi bi-phone"></i>
                    </div>
                    <div class="flex justify-between items-center text-sm font-medium text-gray-800">
                        <span>Celulares</span>
                        <i class="bi bi-arrow-right text-gray-400"></i>
                    </div>
                </a>
            </div>
            <div class="swiper-slide !flex !items-center !justify-center">
                <a href="#" class="flex flex-col gap-3 p-4 w-full border border-gray-200 rounded-xl bg-white hover:border-gray-400 transition-colors no-underline text-inherit">
                    <div class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center text-lg text-gray-600">
                        <i class="bi bi-phone"></i>
                    </div>
                    <div class="flex justify-between items-center text-sm font-medium text-gray-800">
                        <span>Celulares</span>
                        <i class="bi bi-arrow-right text-gray-400"></i>
                    </div>
                </a>
            </div>
            <div class="swiper-slide !flex !items-center !justify-center">
                <a href="#" class="flex flex-col gap-3 p-4 w-full border border-gray-200 rounded-xl bg-white hover:border-gray-400 transition-colors no-underline text-inherit">
                    <div class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center text-lg text-gray-600">
                        <i class="bi bi-phone"></i>
                    </div>
                    <div class="flex justify-between items-center text-sm font-medium text-gray-800">
                        <span>Celulares</span>
                        <i class="bi bi-arrow-right text-gray-400"></i>
                    </div>
                </a>
            </div>
            <div class="swiper-slide !flex !items-center !justify-center">
                <a href="#" class="flex flex-col gap-3 p-4 w-full border border-gray-200 rounded-xl bg-white hover:border-gray-400 transition-colors no-underline text-inherit">
                    <div class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center text-lg text-gray-600">
                        <i class="bi bi-phone"></i>
                    </div>
                    <div class="flex justify-between items-center text-sm font-medium text-gray-800">
                        <span>Celulares</span>
                        <i class="bi bi-arrow-right text-gray-400"></i>
                    </div>
                </a>
            </div>
            <div class="swiper-slide !flex !items-center !justify-center">
                <a href="#" class="flex flex-col gap-3 p-4 w-full border border-gray-200 rounded-xl bg-white hover:border-gray-400 transition-colors no-underline text-inherit">
                    <div class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center text-lg text-gray-600">
                        <i class="bi bi-phone"></i>
                    </div>
                    <div class="flex justify-between items-center text-sm font-medium text-gray-800">
                        <span>Celulares</span>
                        <i class="bi bi-arrow-right text-gray-400"></i>
                    </div>
                </a>
            </div>
            <div class="swiper-slide !flex !items-center !justify-center">
                <a href="#" class="flex flex-col gap-3 p-4 w-full border border-gray-200 rounded-xl bg-white hover:border-gray-400 transition-colors no-underline text-inherit">
                    <div class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center text-lg text-gray-600">
                        <i class="bi bi-phone"></i>
                    </div>
                    <div class="flex justify-between items-center text-sm font-medium text-gray-800">
                        <span>Celulares</span>
                        <i class="bi bi-arrow-right text-gray-400"></i>
                    </div>
                </a>
            </div>
            <div class="swiper-slide !flex !items-center !justify-center">
                <a href="#" class="flex flex-col gap-3 p-4 w-full border border-gray-200 rounded-xl bg-white hover:border-gray-400 transition-colors no-underline text-inherit">
                    <div class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center text-lg text-gray-600">
                        <i class="bi bi-phone"></i>
                    </div>
                    <div class="flex justify-between items-center text-sm font-medium text-gray-800">
                        <span>Celulares</span>
                        <i class="bi bi-arrow-right text-gray-400"></i>
                    </div>
                </a>
            </div>
            <div class="swiper-slide !flex !items-center !justify-center">
                <a href="#" class="flex flex-col gap-3 p-4 w-full border border-gray-200 rounded-xl bg-white hover:border-gray-400 transition-colors no-underline text-inherit">
                    <div class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center text-lg text-gray-600">
                        <i class="bi bi-phone"></i>
                    </div>
                    <div class="flex justify-between items-center text-sm font-medium text-gray-800">
                        <span>Celulares</span>
                        <i class="bi bi-arrow-right text-gray-400"></i>
                    </div>
                </a>
            </div>
            <div class="swiper-slide !flex !items-center !justify-center">
                <a href="#" class="flex flex-col gap-3 p-4 w-full border border-gray-200 rounded-xl bg-white hover:border-gray-400 transition-colors no-underline text-inherit">
                    <div class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center text-lg text-gray-600">
                        <i class="bi bi-phone"></i>
                    </div>
                    <div class="flex justify-between items-center text-sm font-medium text-gray-800">
                        <span>Celulares</span>
                        <i class="bi bi-arrow-right text-gray-400"></i>
                    </div>
                </a>
            </div>
            <div class="swiper-slide !flex !items-center !justify-center">
                <a href="#" class="flex flex-col gap-3 p-4 w-full border border-gray-200 rounded-xl bg-white hover:border-gray-400 transition-colors no-underline text-inherit">
                    <div class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center text-lg text-gray-600">
                        <i class="bi bi-phone"></i>
                    </div>
                    <div class="flex justify-between items-center text-sm font-medium text-gray-800">
                        <span>Celulares</span>
                        <i class="bi bi-arrow-right text-gray-400"></i>
                    </div>
                </a>
            </div>
            <div class="swiper-slide !flex !items-center !justify-center">
                <a href="#" class="flex flex-col gap-3 p-4 w-full border border-gray-200 rounded-xl bg-white hover:border-gray-400 transition-colors no-underline text-inherit">
                    <div class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center text-lg text-gray-600">
                        <i class="bi bi-phone"></i>
                    </div>
                    <div class="flex justify-between items-center text-sm font-medium text-gray-800">
                        <span>Celulares</span>
                        <i class="bi bi-arrow-right text-gray-400"></i>
                    </div>
                </a>
            </div>
            <div class="swiper-slide !flex !items-center !justify-center">
                <a href="#" class="flex flex-col gap-3 p-4 w-full border border-gray-200 rounded-xl bg-white hover:border-gray-400 transition-colors no-underline text-inherit">
                    <div class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center text-lg text-gray-600">
                        <i class="bi bi-phone"></i>
                    </div>
                    <div class="flex justify-between items-center text-sm font-medium text-gray-800">
                        <span>Celulares</span>
                        <i class="bi bi-arrow-right text-gray-400"></i>
                    </div>
                </a>
            </div>

        </div>

    
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        new Swiper(".mySwiper", {
            slidesPerView: 3,
            spaceBetween: 12,
            loop: true,
            navigation: {
                nextEl: ".swiper-button-next",
                prevEl: ".swiper-button-prev",
            },
            autoplay: {
                delay: 3000,
                disableOnInteraction: false,
            },
            breakpoints: {
                640: {
                    slidesPerView: 4,
                    spaceBetween: 16
                },
                1024: {
                    slidesPerView: 10,
                    spaceBetween: 20
                },
            }
        });
    });
</script>