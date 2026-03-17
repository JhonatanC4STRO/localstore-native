<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ComercioLocal – Compra y vende en tu ciudad</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <style>
    :root {
      --green-900: #0d3a1e;
      --green-800: #145228;
      --green-700: #1a6b33;
      --green-600: #1f8340;
      --green-500: #25a050;
      --green-400: #36c465;
      --green-300: #5dd988;
      --green-100: #d0f5df;
      --green-50: #edfaf3;

      --yellow-600: #c47a00;
      --yellow-500: #e8930a;
      --yellow-400: #f7ab1e;
      --yellow-300: #fcc945;
      --yellow-200: #fde08a;
      --yellow-100: #fef3cc;
      --yellow-50: #fffae8;

      --text-dark: #0f1f15;
      --text-mid: #344a3b;
      --text-soft: #6b8070;
      --bg-page: #f5faf7;
      --bg-card: #ffffff;
      --radius-card: 18px;
      --shadow-card: 0 4px 20px rgba(13, 58, 30, .10), 0 1px 4px rgba(13, 58, 30, .06);
      --shadow-hover: 0 10px 36px rgba(13, 58, 30, .16), 0 2px 8px rgba(13, 58, 30, .08);
    }

    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html {
      scroll-behavior: smooth;
    }

    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--bg-page);
      color: var(--text-dark);
      overflow-x: hidden;
    }

    h1,
    h2,
    h3,
    h4,
    h5 {
      font-family: 'Syne', sans-serif;
    }

    a {
      text-decoration: none;
      color: inherit;
    }

    /* ========= NAVBAR ========= */
    .navbar {
      position: sticky;
      top: 0;
      z-index: 100;
      background: var(--green-900);
      border-bottom: 3px solid var(--yellow-400);
      padding: 0 28px;
      display: flex;
      align-items: center;
      gap: 20px;
      height: 68px;
      box-shadow: 0 4px 24px rgba(0, 0, 0, .35);
    }

    .nav-logo {
      display: flex;
      align-items: center;
      gap: 10px;
      font-family: 'Syne', sans-serif;
      font-weight: 800;
      font-size: 1.3rem;
      color: #fff;
      white-space: nowrap;
      flex-shrink: 0;
    }

    .nav-logo .logo-icon {
      width: 38px;
      height: 38px;
      background: var(--yellow-400);
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.1rem;
      color: var(--green-900);
    }

    .nav-logo span em {
      color: var(--yellow-400);
      font-style: normal;
    }

    .nav-search {
      flex: 1;
      display: flex;
      align-items: center;
      background: rgba(255, 255, 255, .1);
      border: 1.5px solid rgba(255, 255, 255, .2);
      border-radius: 50px;
      overflow: hidden;
      max-width: 480px;
    }

    .nav-search input {
      flex: 1;
      background: transparent;
      border: none;
      outline: none;
      color: #fff;
      font-family: 'DM Sans', sans-serif;
      font-size: .92rem;
      padding: 10px 16px;
    }

    .nav-search input::placeholder {
      color: rgba(255, 255, 255, .5);
    }

    .nav-search button {
      background: var(--yellow-400);
      border: none;
      cursor: pointer;
      padding: 10px 18px;
      color: var(--green-900);
      font-size: 1rem;
      transition: background .2s;
    }

    .nav-search button:hover {
      background: var(--yellow-300);
    }

    .nav-city {
      display: flex;
      align-items: center;
      gap: 6px;
      color: rgba(255, 255, 255, .75);
      font-size: .85rem;
      cursor: pointer;
      white-space: nowrap;
      transition: color .2s;
    }

    .nav-city:hover {
      color: var(--yellow-300);
    }

    .nav-spacer {
      flex: 1;
    }

    .nav-links {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .btn-ghost-nav {
      background: transparent;
      border: 1.5px solid rgba(255, 255, 255, .35);
      color: #fff;
      border-radius: 50px;
      padding: 7px 18px;
      font-family: 'DM Sans', sans-serif;
      font-size: .87rem;
      cursor: pointer;
      transition: all .2s;
    }

    .btn-ghost-nav:hover {
      border-color: var(--yellow-400);
      color: var(--yellow-400);
    }

    .btn-publish {
      background: var(--yellow-400);
      border: none;
      color: var(--green-900);
      border-radius: 50px;
      padding: 8px 20px;
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: .87rem;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 7px;
      transition: all .2s;
      white-space: nowrap;
      box-shadow: 0 3px 12px rgba(248, 171, 30, .4);
    }

    .btn-publish:hover {
      background: var(--yellow-300);
      box-shadow: 0 5px 18px rgba(248, 171, 30, .55);
      transform: translateY(-1px);
    }

    /* ========= HERO ========= */
    .hero {
      position: relative;
      background: var(--green-900);
      overflow: hidden;
      padding: 80px 40px 60px;
      display: flex;
      align-items: center;
      gap: 60px;
      min-height: 480px;
    }

    .hero::before {
      content: '';
      position: absolute;
      inset: 0;
      background:
        radial-gradient(ellipse 55% 80% at 70% 50%, rgba(37, 160, 80, .18) 0%, transparent 70%),
        radial-gradient(ellipse 40% 60% at 10% 20%, rgba(248, 171, 30, .12) 0%, transparent 60%);
    }

    .hero-dots {
      position: absolute;
      inset: 0;
      background-image: radial-gradient(rgba(255, 255, 255, .07) 1px, transparent 1px);
      background-size: 30px 30px;
    }

    .hero-content {
      position: relative;
      z-index: 2;
      flex: 1;
    }

    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      background: rgba(248, 171, 30, .15);
      border: 1.5px solid rgba(248, 171, 30, .35);
      color: var(--yellow-300);
      border-radius: 50px;
      padding: 5px 14px;
      font-size: .8rem;
      font-weight: 600;
      margin-bottom: 20px;
    }

    .hero h1 {
      font-size: clamp(2rem, 4vw, 3.2rem);
      font-weight: 800;
      color: #fff;
      line-height: 1.15;
      margin-bottom: 16px;
    }

    .hero h1 em {
      color: var(--yellow-400);
      font-style: normal;
    }

    .hero p {
      color: rgba(255, 255, 255, .65);
      font-size: 1.05rem;
      line-height: 1.6;
      max-width: 520px;
      margin-bottom: 32px;
    }

    .hero-search {
      display: flex;
      align-items: center;
      background: #fff;
      border-radius: 60px;
      overflow: hidden;
      max-width: 560px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, .3);
    }

    .hero-search input {
      flex: 1;
      border: none;
      outline: none;
      padding: 15px 22px;
      font-family: 'DM Sans', sans-serif;
      font-size: .97rem;
      color: var(--text-dark);
    }

    .hero-search input::placeholder {
      color: #aab8b0;
    }

    .hero-search .sep {
      width: 1px;
      height: 28px;
      background: #ddd;
    }

    .hero-search select {
      border: none;
      outline: none;
      padding: 15px 16px;
      font-family: 'DM Sans', sans-serif;
      font-size: .87rem;
      color: var(--text-mid);
      background: transparent;
      cursor: pointer;
    }

    .hero-search .btn-hero-search {
      background: var(--green-500);
      border: none;
      padding: 15px 28px;
      color: #fff;
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: .95rem;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: background .2s;
    }

    .hero-search .btn-hero-search:hover {
      background: var(--green-600);
    }

    .hero-stats {
      display: flex;
      gap: 30px;
      margin-top: 28px;
    }

    .hero-stat {
      text-align: left;
    }

    .hero-stat .num {
      font-family: 'Syne', sans-serif;
      font-weight: 800;
      font-size: 1.6rem;
      color: var(--yellow-300);
    }

    .hero-stat .lbl {
      font-size: .78rem;
      color: rgba(255, 255, 255, .5);
    }

    .hero-illustration {
      position: relative;
      z-index: 2;
      flex-shrink: 0;
      width: 380px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
      transform: rotate(-2deg);
    }

    .hero-mini-card {
      background: rgba(255, 255, 255, .09);
      border: 1.5px solid rgba(255, 255, 255, .15);
      border-radius: 14px;
      overflow: hidden;
      backdrop-filter: blur(6px);
      transition: transform .3s;
    }

    .hero-mini-card:hover {
      transform: scale(1.04);
    }

    .hero-mini-card img {
      width: 100%;
      height: 110px;
      object-fit: cover;
      display: block;
    }

    .hero-mini-card .hmc-info {
      padding: 8px 10px;
    }

    .hero-mini-card .hmc-price {
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: .95rem;
      color: var(--yellow-300);
    }

    .hero-mini-card .hmc-title {
      font-size: .73rem;
      color: rgba(255, 255, 255, .65);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .hero-mini-card.featured {
      grid-column: span 2;
    }

    .hero-mini-card.featured img {
      height: 120px;
    }

    /* ========= CATEGORIES ========= */
    .categories-section {
      padding: 50px 40px 30px;
      background: #fff;
    }

    .section-header {
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      margin-bottom: 28px;
    }

    .section-title {
      font-size: 1.5rem;
      font-weight: 800;
      color: var(--text-dark);
    }

    .section-title span {
      color: var(--green-500);
    }

    .section-link {
      font-size: .85rem;
      color: var(--green-600);
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 4px;
      transition: gap .2s;
    }

    .section-link:hover {
      gap: 8px;
    }

    .categories-grid {
      display: flex;
      gap: 14px;
      overflow-x: auto;
      padding-bottom: 8px;
      scrollbar-width: none;
    }

    .categories-grid::-webkit-scrollbar {
      display: none;
    }

    .cat-card {
      flex-shrink: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 12px;
      background: var(--bg-page);
      border: 2px solid transparent;
      border-radius: var(--radius-card);
      padding: 22px 28px;
      cursor: pointer;
      transition: all .25s;
      width: 120px;
    }

    .cat-card:hover {
      border-color: var(--green-400);
      background: var(--green-50);
      transform: translateY(-4px);
      box-shadow: var(--shadow-hover);
    }

    .cat-card.active {
      border-color: var(--green-500);
      background: var(--green-50);
    }

    .cat-icon {
      width: 52px;
      height: 52px;
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.4rem;
    }

    .cat-card:nth-child(1) .cat-icon {
      background: #e8f5e9;
      color: #2e7d32;
    }

    .cat-card:nth-child(2) .cat-icon {
      background: #fff3e0;
      color: #ef6c00;
    }

    .cat-card:nth-child(3) .cat-icon {
      background: #e3f2fd;
      color: #1565c0;
    }

    .cat-card:nth-child(4) .cat-icon {
      background: #fce4ec;
      color: #c62828;
    }

    .cat-card:nth-child(5) .cat-icon {
      background: #f3e5f5;
      color: #6a1b9a;
    }

    .cat-card:nth-child(6) .cat-icon {
      background: #e0f7fa;
      color: #00695c;
    }

    .cat-card:nth-child(7) .cat-icon {
      background: #f9fbe7;
      color: #558b2f;
    }

    .cat-card:nth-child(8) .cat-icon {
      background: #efebe9;
      color: #4e342e;
    }

    .cat-label {
      font-size: .8rem;
      font-weight: 600;
      text-align: center;
      color: var(--text-mid);
    }

    .cat-count {
      font-size: .72rem;
      color: var(--green-500);
      background: var(--green-100);
      border-radius: 50px;
      padding: 2px 8px;
    }

    /* ========= MAIN LAYOUT ========= */
    .main-layout {
      display: flex;
      gap: 24px;
      padding: 36px 40px 60px;
      max-width: 1400px;
      margin: 0 auto;
    }

    /* ========= SIDEBAR ========= */
    .sidebar {
      flex-shrink: 0;
      width: 260px;
    }

    .filter-card {
      background: var(--bg-card);
      border-radius: var(--radius-card);
      padding: 24px;
      box-shadow: var(--shadow-card);
      margin-bottom: 20px;
    }

    .filter-title {
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: 1rem;
      color: var(--text-dark);
      margin-bottom: 18px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .filter-title i {
      color: var(--green-500);
    }

    .filter-group {
      margin-bottom: 22px;
    }

    .filter-group:last-child {
      margin-bottom: 0;
    }

    .filter-group-label {
      font-size: .78rem;
      font-weight: 600;
      color: var(--text-soft);
      text-transform: uppercase;
      letter-spacing: .06em;
      margin-bottom: 10px;
    }

    .filter-options {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .filter-opt {
      display: flex;
      align-items: center;
      gap: 10px;
      cursor: pointer;
      padding: 6px 8px;
      border-radius: 8px;
      transition: background .2s;
    }

    .filter-opt:hover {
      background: var(--green-50);
    }

    .filter-opt input[type="checkbox"] {
      accent-color: var(--green-500);
      width: 15px;
      height: 15px;
    }

    .filter-opt-label {
      font-size: .87rem;
      color: var(--text-mid);
    }

    .filter-opt-count {
      margin-left: auto;
      font-size: .75rem;
      color: var(--text-soft);
      background: #f0f0f0;
      padding: 1px 7px;
      border-radius: 20px;
    }

    .price-range {
      display: flex;
      gap: 10px;
      align-items: center;
    }

    .price-input {
      flex: 1;
      border: 1.5px solid #ddd;
      border-radius: 10px;
      padding: 8px 10px;
      font-family: 'DM Sans', sans-serif;
      font-size: .85rem;
      outline: none;
      color: var(--text-dark);
      transition: border-color .2s;
    }

    .price-input:focus {
      border-color: var(--green-500);
    }

    .price-sep {
      color: var(--text-soft);
      font-size: .8rem;
    }

    .range-slider {
      width: 100%;
      margin-top: 14px;
      accent-color: var(--green-500);
    }

    .btn-apply-filter {
      width: 100%;
      background: var(--green-500);
      border: none;
      color: #fff;
      padding: 11px;
      border-radius: 12px;
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: .9rem;
      cursor: pointer;
      margin-top: 18px;
      transition: all .2s;
    }

    .btn-apply-filter:hover {
      background: var(--green-600);
      transform: translateY(-1px);
    }

    .condition-tags {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .cond-tag {
      padding: 6px 14px;
      border-radius: 50px;
      border: 1.5px solid #ddd;
      font-size: .82rem;
      cursor: pointer;
      transition: all .2s;
      color: var(--text-mid);
    }

    .cond-tag:hover {
      border-color: var(--green-400);
      color: var(--green-600);
    }

    .cond-tag.active {
      background: var(--green-500);
      border-color: var(--green-500);
      color: #fff;
    }

    .sidebar-promo {
      background: linear-gradient(135deg, var(--green-800), var(--green-900));
      border-radius: var(--radius-card);
      padding: 24px;
      color: #fff;
      position: relative;
      overflow: hidden;
    }

    .sidebar-promo::before {
      content: '';
      position: absolute;
      right: -20px;
      top: -20px;
      width: 100px;
      height: 100px;
      background: rgba(248, 171, 30, .15);
      border-radius: 50%;
    }

    .sidebar-promo .promo-tag {
      font-size: .72rem;
      font-weight: 700;
      background: var(--yellow-400);
      color: var(--green-900);
      padding: 3px 10px;
      border-radius: 50px;
      display: inline-block;
      margin-bottom: 12px;
    }

    .sidebar-promo h4 {
      font-size: 1.05rem;
      font-weight: 800;
      margin-bottom: 8px;
      color: #fff;
    }

    .sidebar-promo p {
      font-size: .82rem;
      color: rgba(255, 255, 255, .65);
      margin-bottom: 16px;
      line-height: 1.5;
    }

    .sidebar-promo a {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: var(--yellow-400);
      color: var(--green-900);
      padding: 8px 18px;
      border-radius: 50px;
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: .82rem;
      transition: background .2s;
    }

    .sidebar-promo a:hover {
      background: var(--yellow-300);
    }

    /* ========= PRODUCT GRID ========= */
    .content-area {
      flex: 1;
      min-width: 0;
    }

    .content-section {
      margin-bottom: 50px;
    }

    .product-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
      gap: 18px;
    }

    .product-card {
      background: var(--bg-card);
      border-radius: var(--radius-card);
      overflow: hidden;
      box-shadow: var(--shadow-card);
      cursor: pointer;
      transition: all .3s cubic-bezier(.175, .885, .32, 1.275);
      border: 1.5px solid transparent;
      position: relative;
    }

    .product-card:hover {
      transform: translateY(-6px);
      box-shadow: var(--shadow-hover);
      border-color: var(--green-200, #a8e6bf);
    }

    .product-card .badge-new {
      position: absolute;
      top: 12px;
      left: 12px;
      z-index: 2;
      background: var(--green-500);
      color: #fff;
      font-size: .68rem;
      font-weight: 700;
      padding: 3px 9px;
      border-radius: 50px;
      text-transform: uppercase;
      letter-spacing: .05em;
    }

    .product-card .badge-used {
      position: absolute;
      top: 12px;
      left: 12px;
      z-index: 2;
      background: var(--yellow-400);
      color: var(--green-900);
      font-size: .68rem;
      font-weight: 700;
      padding: 3px 9px;
      border-radius: 50px;
      text-transform: uppercase;
      letter-spacing: .05em;
    }

    .product-card .btn-fav {
      position: absolute;
      top: 12px;
      right: 12px;
      z-index: 2;
      width: 32px;
      height: 32px;
      background: rgba(255, 255, 255, .88);
      border: none;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      font-size: .9rem;
      color: #bbb;
      transition: all .2s;
      backdrop-filter: blur(4px);
    }

    .product-card .btn-fav:hover {
      color: #e53935;
      transform: scale(1.1);
    }

    .product-card .btn-fav.active {
      color: #e53935;
    }

    .product-img {
      width: 100%;
      height: 170px;
      object-fit: cover;
      display: block;
      background: #e8ede9;
    }

    .product-img-placeholder {
      width: 100%;
      height: 170px;
      background: linear-gradient(135deg, #e8f5e9, #f1f8f2);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.8rem;
      color: #b8d4be;
    }

    .product-body {
      padding: 14px 16px 16px;
    }

    .product-price {
      font-family: 'Syne', sans-serif;
      font-weight: 800;
      font-size: 1.15rem;
      color: var(--green-600);
      margin-bottom: 4px;
    }

    .product-title {
      font-size: .88rem;
      font-weight: 500;
      color: var(--text-dark);
      margin-bottom: 10px;
      line-height: 1.35;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .product-meta {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .product-meta-row {
      display: flex;
      align-items: center;
      gap: 5px;
      font-size: .78rem;
      color: var(--text-soft);
    }

    .product-meta-row i {
      font-size: .8rem;
    }

    .product-seller {
      margin-top: 12px;
      padding-top: 12px;
      border-top: 1px solid #f0f4f1;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .seller-avatar {
      width: 26px;
      height: 26px;
      border-radius: 50%;
      background: var(--green-100);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: .7rem;
      font-weight: 700;
      color: var(--green-700);
      flex-shrink: 0;
      overflow: hidden;
    }

    .seller-info {
      flex: 1;
      min-width: 0;
    }

    .seller-name {
      font-size: .76rem;
      font-weight: 600;
      color: var(--text-dark);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .seller-rating {
      display: flex;
      align-items: center;
      gap: 3px;
      font-size: .7rem;
      color: var(--yellow-500);
    }

    .seller-rating span {
      color: var(--text-soft);
      margin-left: 2px;
    }

    /* ========= SECTION CERCA DE TI ========= */
    .nearby-section {
      background: linear-gradient(135deg, var(--green-50), var(--yellow-50));
      border-radius: 24px;
      padding: 32px;
      margin-bottom: 0;
      border: 1.5px solid var(--green-100);
    }

    .nearby-section .section-header {
      margin-bottom: 22px;
    }

    .nearby-badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      background: var(--green-500);
      color: #fff;
      font-size: .72rem;
      font-weight: 700;
      padding: 4px 10px;
      border-radius: 50px;
      margin-bottom: 8px;
    }

    /* ========= FOOTER ========= */
    .footer {
      background: var(--green-900);
      border-top: 4px solid var(--yellow-400);
      color: rgba(255, 255, 255, .7);
    }

    .footer-top {
      display: grid;
      grid-template-columns: 2fr 1fr 1fr 1fr;
      gap: 48px;
      padding: 60px 48px 48px;
    }

    .footer-brand .brand-name {
      font-family: 'Syne', sans-serif;
      font-weight: 800;
      font-size: 1.4rem;
      color: #fff;
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .footer-brand .brand-icon {
      width: 36px;
      height: 36px;
      background: var(--yellow-400);
      border-radius: 9px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--green-900);
      font-size: 1rem;
    }

    .footer-brand p {
      font-size: .87rem;
      line-height: 1.65;
      margin-bottom: 20px;
    }

    .social-links {
      display: flex;
      gap: 10px;
    }

    .social-btn {
      width: 38px;
      height: 38px;
      border-radius: 10px;
      background: rgba(255, 255, 255, .08);
      border: 1px solid rgba(255, 255, 255, .12);
      display: flex;
      align-items: center;
      justify-content: center;
      color: rgba(255, 255, 255, .65);
      font-size: 1rem;
      cursor: pointer;
      transition: all .2s;
    }

    .social-btn:hover {
      background: var(--yellow-400);
      color: var(--green-900);
      border-color: var(--yellow-400);
      transform: translateY(-2px);
    }

    .footer-col h5 {
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: .92rem;
      color: #fff;
      margin-bottom: 18px;
    }

    .footer-col ul {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .footer-col ul li a {
      font-size: .84rem;
      transition: color .2s;
    }

    .footer-col ul li a:hover {
      color: var(--yellow-300);
    }

    .footer-col .app-btns {
      display: flex;
      flex-direction: column;
      gap: 10px;
      margin-top: 4px;
    }

    .app-btn {
      display: flex;
      align-items: center;
      gap: 10px;
      background: rgba(255, 255, 255, .08);
      border: 1px solid rgba(255, 255, 255, .15);
      border-radius: 10px;
      padding: 9px 14px;
      cursor: pointer;
      transition: all .2s;
    }

    .app-btn:hover {
      background: rgba(255, 255, 255, .14);
    }

    .app-btn i {
      font-size: 1.3rem;
      color: rgba(255, 255, 255, .8);
    }

    .app-btn-text {
      font-size: .75rem;
      color: rgba(255, 255, 255, .55);
    }

    .app-btn-text strong {
      display: block;
      font-size: .85rem;
      color: #fff;
    }

    .footer-bottom {
      border-top: 1px solid rgba(255, 255, 255, .08);
      padding: 20px 48px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .footer-bottom p {
      font-size: .8rem;
    }

    .footer-bottom a {
      color: var(--yellow-300);
    }

    .footer-legal {
      display: flex;
      gap: 20px;
    }

    .footer-legal a {
      font-size: .8rem;
    }

    .footer-legal a:hover {
      color: var(--yellow-300);
    }

    /* ========= SCROLL ANIMATION ========= */
    @keyframes fadeUp {
      from {
        opacity: 0;
        transform: translateY(24px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .product-card {
      animation: fadeUp .45s both;
    }

    .product-card:nth-child(1) {
      animation-delay: .05s;
    }

    .product-card:nth-child(2) {
      animation-delay: .10s;
    }

    .product-card:nth-child(3) {
      animation-delay: .15s;
    }

    .product-card:nth-child(4) {
      animation-delay: .20s;
    }

    .product-card:nth-child(5) {
      animation-delay: .25s;
    }

    .product-card:nth-child(6) {
      animation-delay: .30s;
    }

    .product-card:nth-child(7) {
      animation-delay: .35s;
    }

    .product-card:nth-child(8) {
      animation-delay: .40s;
    }

    /* ========= MISC ========= */
    .divider-line {
      height: 3px;
      background: linear-gradient(to right, var(--green-400), var(--yellow-400), transparent);
      border-radius: 50px;
      margin-bottom: 24px;
    }

    .empty-spacer {
      height: 8px;
    }

    .tag-pill {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      background: var(--green-100);
      color: var(--green-700);
      font-size: .75rem;
      font-weight: 600;
      padding: 3px 10px;
      border-radius: 50px;
    }
  </style>
</head>

<body>

  <!-- ======= NAVBAR ======= -->
  <nav class="navbar">
    <div class="nav-logo">
      <div class="logo-icon"><i class="bi bi-shop"></i></div>
      <span>Comercio<em>Local</em></span>
    </div>

    <div class="nav-search">
      <input type="text" placeholder="Buscar productos, servicios...">
      <button><i class="bi bi-search"></i></button>
    </div>

    <div class="nav-city">
      <i class="bi bi-geo-alt-fill"></i>
      Bogotá, Colombia
      <i class="bi bi-chevron-down"></i>
    </div>

    <div class="nav-spacer"></div>

    <div class="nav-links">
      <button class="btn-ghost-nav"><i class="bi bi-person"></i> Iniciar sesión</button>
      <button class="btn-ghost-nav">Registrarse</button>
      <button class="btn-publish">
        <i class="bi bi-plus-circle-fill"></i>
        Publicar
      </button>
    </div>
  </nav>

  <!-- ======= HERO ======= -->
  <section class="hero">
    <div class="hero-dots"></div>

    <div class="hero-content">
      <div class="hero-badge">
        <i class="bi bi-lightning-charge-fill"></i>
        +48,000 anuncios activos hoy
      </div>
      <h1>ComercioLocal<br><em>Compra y vende</em><br>en tu ciudad</h1>
      <p>La plataforma que conecta vecinos, emprendedores y compradores en tu comunidad. Rápido, seguro y local.</p>

      <div class="hero-search">
        <input type="text" placeholder="¿Qué estás buscando hoy?">
        <div class="sep"></div>
        <select>
          <option>Toda Colombia</option>
          <option>Bogotá</option>
          <option>Medellín</option>
          <option>Cali</option>
          <option>Barranquilla</option>
        </select>
        <button class="btn-hero-search">
          <i class="bi bi-search"></i> Buscar
        </button>
      </div>

      <div class="hero-stats">
        <div class="hero-stat">
          <div class="num">48K+</div>
          <div class="lbl">Anuncios activos</div>
        </div>
        <div class="hero-stat">
          <div class="num">120K</div>
          <div class="lbl">Usuarios registrados</div>
        </div>
        <div class="hero-stat">
          <div class="num">32</div>
          <div class="lbl">Ciudades</div>
        </div>
      </div>
    </div>

    <div class="hero-illustration">
      <div class="hero-mini-card featured">
        <div style="width:100%;height:120px;background:linear-gradient(135deg,#1a6b33,#25a050);display:flex;align-items:center;justify-content:center;font-size:3rem;">🏍️</div>
        <div class="hmc-info">
          <div class="hmc-price">$4.800.000</div>
          <div class="hmc-title">Moto Honda 150cc – Seminueva</div>
        </div>
      </div>
      <div class="hero-mini-card">
        <div style="width:100%;height:110px;background:linear-gradient(135deg,#fff3e0,#ffe0b2);display:flex;align-items:center;justify-content:center;font-size:2.5rem;">💻</div>
        <div class="hmc-info">
          <div class="hmc-price" style="color:var(--yellow-400);">$1.200.000</div>
          <div class="hmc-title">MacBook Air M1</div>
        </div>
      </div>
      <div class="hero-mini-card">
        <div style="width:100%;height:110px;background:linear-gradient(135deg,#e3f2fd,#bbdefb);display:flex;align-items:center;justify-content:center;font-size:2.5rem;">👟</div>
        <div class="hmc-info">
          <div class="hmc-price" style="color:var(--yellow-400);">$180.000</div>
          <div class="hmc-title">Zapatillas Nike SB</div>
        </div>
      </div>
    </div>
  </section>

  <!-- ======= CATEGORIES ======= -->
  <section class="categories-section">
    <div class="section-header">
      <h2 class="section-title">Explorar <span>categorías</span></h2>
      <a class="section-link" href="#">Ver todas <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="categories-grid">
      <div class="cat-card active">
        <div class="cat-icon"><i class="bi bi-cpu-fill"></i></div>
        <div class="cat-label">Tecnología</div>
        <div class="cat-count">4.2K</div>
      </div>
      <div class="cat-card">
        <div class="cat-icon"><i class="bi bi-bag-heart-fill"></i></div>
        <div class="cat-label">Ropa y Moda</div>
        <div class="cat-count">8.1K</div>
      </div>
      <div class="cat-card">
        <div class="cat-icon"><i class="bi bi-car-front-fill"></i></div>
        <div class="cat-label">Vehículos</div>
        <div class="cat-count">2.9K</div>
      </div>
      <div class="cat-card">
        <div class="cat-icon"><i class="bi bi-house-door-fill"></i></div>
        <div class="cat-label">Hogar</div>
        <div class="cat-count">5.6K</div>
      </div>
      <div class="cat-card">
        <div class="cat-icon"><i class="bi bi-trophy-fill"></i></div>
        <div class="cat-label">Deportes</div>
        <div class="cat-count">3.3K</div>
      </div>
      <div class="cat-card">
        <div class="cat-icon"><i class="bi bi-tools"></i></div>
        <div class="cat-label">Herramientas</div>
        <div class="cat-count">1.8K</div>
      </div>
      <div class="cat-card">
        <div class="cat-icon"><i class="bi bi-leaf-fill"></i></div>
        <div class="cat-label">Jardín</div>
        <div class="cat-count">980</div>
      </div>
      <div class="cat-card">
        <div class="cat-icon"><i class="bi bi-book-fill"></i></div>
        <div class="cat-label">Libros</div>
        <div class="cat-count">2.4K</div>
      </div>
    </div>
  </section>

  <!-- ======= MAIN LAYOUT ======= -->
  <div class="main-layout">

    <!-- SIDEBAR FILTERS -->
    <aside class="sidebar">
      <div class="filter-card">
        <div class="filter-title"><i class="bi bi-sliders"></i> Filtros</div>

        <div class="filter-group">
          <div class="filter-group-label">Categoría</div>
          <div class="filter-options">
            <label class="filter-opt">
              <input type="checkbox" checked>
              <span class="filter-opt-label">Tecnología</span>
              <span class="filter-opt-count">4.2K</span>
            </label>
            <label class="filter-opt">
              <input type="checkbox">
              <span class="filter-opt-label">Ropa y Moda</span>
              <span class="filter-opt-count">8.1K</span>
            </label>
            <label class="filter-opt">
              <input type="checkbox">
              <span class="filter-opt-label">Vehículos</span>
              <span class="filter-opt-count">2.9K</span>
            </label>
            <label class="filter-opt">
              <input type="checkbox">
              <span class="filter-opt-label">Hogar</span>
              <span class="filter-opt-count">5.6K</span>
            </label>
            <label class="filter-opt">
              <input type="checkbox">
              <span class="filter-opt-label">Deportes</span>
              <span class="filter-opt-count">3.3K</span>
            </label>
          </div>
        </div>

        <div class="filter-group">
          <div class="filter-group-label">Rango de precio</div>
          <div class="price-range">
            <input type="text" class="price-input" placeholder="Mín" value="0">
            <span class="price-sep">–</span>
            <input type="text" class="price-input" placeholder="Máx" value="5.000.000">
          </div>
          <input type="range" class="range-slider" min="0" max="10000000" value="5000000">
        </div>

        <div class="filter-group">
          <div class="filter-group-label">Estado del producto</div>
          <div class="condition-tags">
            <div class="cond-tag active">Todos</div>
            <div class="cond-tag">Nuevo</div>
            <div class="cond-tag">Usado</div>
            <div class="cond-tag">Reacondicionado</div>
          </div>
        </div>

        <div class="filter-group">
          <div class="filter-group-label">Ciudad</div>
          <div class="filter-options">
            <label class="filter-opt">
              <input type="checkbox" checked>
              <span class="filter-opt-label">Bogotá</span>
              <span class="filter-opt-count">18K</span>
            </label>
            <label class="filter-opt">
              <input type="checkbox">
              <span class="filter-opt-label">Medellín</span>
              <span class="filter-opt-count">9K</span>
            </label>
            <label class="filter-opt">
              <input type="checkbox">
              <span class="filter-opt-label">Cali</span>
              <span class="filter-opt-count">7K</span>
            </label>
            <label class="filter-opt">
              <input type="checkbox">
              <span class="filter-opt-label">Barranquilla</span>
              <span class="filter-opt-count">5K</span>
            </label>
          </div>
        </div>

        <button class="btn-apply-filter">Aplicar filtros</button>
      </div>

      <!-- Sidebar Promo -->
      <div class="sidebar-promo">
        <div class="promo-tag">🚀 Destacar anuncio</div>
        <h4>¿Vendes algo?</h4>
        <p>Publica gratis y llega a miles de compradores en tu ciudad hoy mismo.</p>
        <a href="#">Publicar ahora <i class="bi bi-arrow-right"></i></a>
      </div>
    </aside>

    <!-- CONTENT -->
    <div class="content-area">

      <!-- Cerca de ti -->
      <div class="content-section">
        <div class="nearby-section">
          <div class="section-header">
            <div>
              <div class="nearby-badge"><i class="bi bi-geo-alt-fill"></i> Tu zona</div>
              <h2 class="section-title">Productos cerca <span>de ti</span></h2>
            </div>
            <a class="section-link" href="#">Ver más <i class="bi bi-arrow-right"></i></a>
          </div>
          <div class="product-grid">

            <!-- Card 1 -->
            <div class="product-card">
              <div class="badge-new">Nuevo</div>
              <button class="btn-fav"><i class="bi bi-heart"></i></button>
              <div class="product-img-placeholder">📱</div>
              <div class="product-body">
                <div class="product-price">$850.000</div>
                <div class="product-title">iPhone 13 128GB Negro – Perfecto estado</div>
                <div class="product-meta">
                  <div class="product-meta-row"><i class="bi bi-geo-alt"></i> Chapinero, Bogotá</div>
                  <div class="product-meta-row"><i class="bi bi-clock"></i> Hace 2 horas</div>
                </div>
                <div class="product-seller">
                  <div class="seller-avatar">CA</div>
                  <div class="seller-info">
                    <div class="seller-name">Carlos Ávila</div>
                    <div class="seller-rating">
                      <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-half"></i>
                      <span>4.8</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Card 2 -->
            <div class="product-card">
              <div class="badge-used">Usado</div>
              <button class="btn-fav active"><i class="bi bi-heart-fill"></i></button>
              <div class="product-img-placeholder">🛋️</div>
              <div class="product-body">
                <div class="product-price">$320.000</div>
                <div class="product-title">Sofá esquinero 3 puestos – Buen estado</div>
                <div class="product-meta">
                  <div class="product-meta-row"><i class="bi bi-geo-alt"></i> Usaquén, Bogotá</div>
                  <div class="product-meta-row"><i class="bi bi-clock"></i> Hace 5 horas</div>
                </div>
                <div class="product-seller">
                  <div class="seller-avatar">LM</div>
                  <div class="seller-info">
                    <div class="seller-name">Laura Morales</div>
                    <div class="seller-rating">
                      <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star"></i>
                      <span>4.2</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Card 3 -->
            <div class="product-card">
              <div class="badge-new">Nuevo</div>
              <button class="btn-fav"><i class="bi bi-heart"></i></button>
              <div class="product-img-placeholder">🚲</div>
              <div class="product-body">
                <div class="product-price">$1.100.000</div>
                <div class="product-title">Bicicleta MTB Trek 29" Aluminio 21v</div>
                <div class="product-meta">
                  <div class="product-meta-row"><i class="bi bi-geo-alt"></i> Suba, Bogotá</div>
                  <div class="product-meta-row"><i class="bi bi-clock"></i> Hace 1 día</div>
                </div>
                <div class="product-seller">
                  <div class="seller-avatar">JP</div>
                  <div class="seller-info">
                    <div class="seller-name">Julián Pérez</div>
                    <div class="seller-rating">
                      <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                      <span>5.0</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Card 4 -->
            <div class="product-card">
              <div class="badge-used">Usado</div>
              <button class="btn-fav"><i class="bi bi-heart"></i></button>
              <div class="product-img-placeholder">👗</div>
              <div class="product-body">
                <div class="product-price">$95.000</div>
                <div class="product-title">Vestido floral talla M – Zara original</div>
                <div class="product-meta">
                  <div class="product-meta-row"><i class="bi bi-geo-alt"></i> Kennedy, Bogotá</div>
                  <div class="product-meta-row"><i class="bi bi-clock"></i> Hace 3 horas</div>
                </div>
                <div class="product-seller">
                  <div class="seller-avatar">SR</div>
                  <div class="seller-info">
                    <div class="seller-name">Sara Rodríguez</div>
                    <div class="seller-rating">
                      <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-half"></i>
                      <span>4.6</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- Productos recientes -->
      <div class="content-section">
        <div class="section-header">
          <div>
            <h2 class="section-title">Productos <span>recientes</span></h2>
          </div>
          <div style="display:flex;gap:10px;align-items:center;">
            <span style="font-size:.82rem;color:var(--text-soft);">Ordenar por:</span>
            <select style="border:1.5px solid #ddd;border-radius:8px;padding:6px 12px;font-family:'DM Sans',sans-serif;font-size:.84rem;outline:none;color:var(--text-dark);cursor:pointer;">
              <option>Más recientes</option>
              <option>Menor precio</option>
              <option>Mayor precio</option>
              <option>Más cercanos</option>
            </select>
            <a class="section-link" href="#">Ver todos <i class="bi bi-arrow-right"></i></a>
          </div>
        </div>
        <div class="divider-line"></div>

        <div class="product-grid">
          <!-- Card 5 -->
          <div class="product-card">
            <div class="badge-new">Nuevo</div>
            <button class="btn-fav"><i class="bi bi-heart"></i></button>
            <div class="product-img-placeholder">🎧</div>
            <div class="product-body">
              <div class="product-price">$380.000</div>
              <div class="product-title">Sony WH-1000XM4 Noise Cancelling</div>
              <div class="product-meta">
                <div class="product-meta-row"><i class="bi bi-geo-alt"></i> Teusaquillo, Bogotá</div>
                <div class="product-meta-row"><i class="bi bi-clock"></i> Hace 20 min</div>
              </div>
              <div class="product-seller">
                <div class="seller-avatar">MG</div>
                <div class="seller-info">
                  <div class="seller-name">Miguel García</div>
                  <div class="seller-rating">
                    <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star"></i>
                    <span>4.3</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Card 6 -->
          <div class="product-card">
            <div class="badge-used">Usado</div>
            <button class="btn-fav"><i class="bi bi-heart"></i></button>
            <div class="product-img-placeholder">🏋️</div>
            <div class="product-body">
              <div class="product-price">$450.000</div>
              <div class="product-title">Set de mancuernas 5 a 30 kg ajustables</div>
              <div class="product-meta">
                <div class="product-meta-row"><i class="bi bi-geo-alt"></i> Fontibón, Bogotá</div>
                <div class="product-meta-row"><i class="bi bi-clock"></i> Hace 45 min</div>
              </div>
              <div class="product-seller">
                <div class="seller-avatar">AF</div>
                <div class="seller-info">
                  <div class="seller-name">Andrés Forero</div>
                  <div class="seller-rating">
                    <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                    <span>5.0</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Card 7 -->
          <div class="product-card">
            <div class="badge-new">Nuevo</div>
            <button class="btn-fav"><i class="bi bi-heart"></i></button>
            <div class="product-img-placeholder">🖨️</div>
            <div class="product-body">
              <div class="product-price">$290.000</div>
              <div class="product-title">Impresora HP LaserJet Pro M15w WiFi</div>
              <div class="product-meta">
                <div class="product-meta-row"><i class="bi bi-geo-alt"></i> Barrios Unidos, Bogotá</div>
                <div class="product-meta-row"><i class="bi bi-clock"></i> Hace 1 hora</div>
              </div>
              <div class="product-seller">
                <div class="seller-avatar">VC</div>
                <div class="seller-info">
                  <div class="seller-name">Valentina Cruz</div>
                  <div class="seller-rating">
                    <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-half"></i>
                    <span>4.7</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Card 8 -->
          <div class="product-card">
            <div class="badge-used">Usado</div>
            <button class="btn-fav active"><i class="bi bi-heart-fill"></i></button>
            <div class="product-img-placeholder">🪴</div>
            <div class="product-body">
              <div class="product-price">$45.000</div>
              <div class="product-title">Plantas suculentas variadas con maceta</div>
              <div class="product-meta">
                <div class="product-meta-row"><i class="bi bi-geo-alt"></i> La Candelaria, Bogotá</div>
                <div class="product-meta-row"><i class="bi bi-clock"></i> Hace 2 horas</div>
              </div>
              <div class="product-seller">
                <div class="seller-avatar">NB</div>
                <div class="seller-info">
                  <div class="seller-name">Natalia Bernal</div>
                  <div class="seller-rating">
                    <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star"></i>
                    <span>4.1</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>

    </div>
  </div>

  <!-- ======= FOOTER ======= -->
  <footer class="footer">
    <div class="footer-top">
      <div class="footer-brand">
        <div class="brand-name">
          <div class="brand-icon"><i class="bi bi-shop"></i></div>
          ComercioLocal
        </div>
        <p>Conectamos compradores y vendedores dentro de la misma ciudad. Más local, más rápido, más seguro.</p>
        <div class="social-links">
          <div class="social-btn"><i class="bi bi-facebook"></i></div>
          <div class="social-btn"><i class="bi bi-instagram"></i></div>
          <div class="social-btn"><i class="bi bi-twitter-x"></i></div>
          <div class="social-btn"><i class="bi bi-whatsapp"></i></div>
          <div class="social-btn"><i class="bi bi-youtube"></i></div>
          <div class="social-btn"><i class="bi bi-tiktok"></i></div>
        </div>
      </div>

      <div class="footer-col">
        <h5>Explorar</h5>
        <ul>
          <li><a href="#">Tecnología</a></li>
          <li><a href="#">Vehículos</a></li>
          <li><a href="#">Hogar</a></li>
          <li><a href="#">Ropa y Moda</a></li>
          <li><a href="#">Deportes</a></li>
          <li><a href="#">Servicios</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h5>Cuenta</h5>
        <ul>
          <li><a href="#">Iniciar sesión</a></li>
          <li><a href="#">Registrarse</a></li>
          <li><a href="#">Mis anuncios</a></li>
          <li><a href="#">Mensajes</a></li>
          <li><a href="#">Favoritos</a></li>
          <li><a href="#">Centro de ayuda</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h5>Descarga la app</h5>
        <div class="app-btns">
          <div class="app-btn">
            <i class="bi bi-apple"></i>
            <div class="app-btn-text">
              Disponible en<br>
              <strong>App Store</strong>
            </div>
          </div>
          <div class="app-btn">
            <i class="bi bi-google-play"></i>
            <div class="app-btn-text">
              Disponible en<br>
              <strong>Google Play</strong>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="footer-bottom">
      <p>© 2025 ComercioLocal. Hecho con <span style="color:var(--yellow-400);">♥</span> en Colombia. Todos los derechos reservados.</p>
      <div class="footer-legal">
        <a href="#">Términos de uso</a>
        <a href="#">Privacidad</a>
        <a href="#">Cookies</a>
        <a href="#">Contacto</a>
      </div>
    </div>
  </footer>

  <script>
    // Category card toggle
    document.querySelectorAll('.cat-card').forEach(card => {
      card.addEventListener('click', () => {
        document.querySelectorAll('.cat-card').forEach(c => c.classList.remove('active'));
        card.classList.add('active');
      });
    });

    // Condition tag toggle
    document.querySelectorAll('.cond-tag').forEach(tag => {
      tag.addEventListener('click', () => {
        document.querySelectorAll('.cond-tag').forEach(t => t.classList.remove('active'));
        tag.classList.add('active');
      });
    });

    // Fav button toggle
    document.querySelectorAll('.btn-fav').forEach(btn => {
      btn.addEventListener('click', e => {
        e.stopPropagation();
        btn.classList.toggle('active');
        const icon = btn.querySelector('i');
        if (btn.classList.contains('active')) {
          icon.className = 'bi bi-heart-fill';
        } else {
          icon.className = 'bi bi-heart';
        }
      });
    });
  </script>
</body>

</html>