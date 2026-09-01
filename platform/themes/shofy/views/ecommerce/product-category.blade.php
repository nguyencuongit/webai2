@php
    Theme::set('pageTitle', $category->name);
@endphp

{{-- Custom CSS cho trang danh mục sản phẩm - Bo tròn và hài hòa --}}
<style>
/* === Product Card Styling === */
.tp-product-item {
    border-radius: 16px;
    overflow: hidden;
    background: #fff;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
    transition: all 0.3s ease;
    border: 1px solid #f0f0f0;
}
.tp-product-item:hover {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    transform: translateY(-4px);
}

/* Product Thumbnail */
.tp-product-thumb {
    border-radius: 16px 16px 0 0;
    overflow: hidden;
}
.tp-product-thumb img {
    border-radius: 16px 16px 0 0;
    transition: transform 0.4s ease;
}
.tp-product-item:hover .tp-product-thumb img {
    transform: scale(1.05);
}

/* Product Content */
.tp-product-content {
    padding: 16px;
    border-radius: 0 0 16px 16px;
}
.tp-product-title {
    font-size: 15px;
    font-weight: 600;
    margin-bottom: 8px;
}
.tp-product-title a {
    color: #333;
    transition: color 0.3s ease;
}
.tp-product-title a:hover {
    color: var(--tp-theme-primary, #821E23);
}

/* Product Badges */
.tp-product-badge {
    border-radius: 8px !important;
    padding: 4px 10px;
    font-size: 11px;
    font-weight: 600;
}

/* Product Actions */
.tp-product-action-btn {
    border-radius: 50% !important;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}
.tp-product-action-btn:hover {
    background: var(--tp-theme-primary, #821E23);
    color: #fff;
    transform: scale(1.1);
}

/* === Filter Sidebar === */
.bb-filter-offcanvas-wrapper {
    border-radius: 0 20px 20px 0;
}
.tp-shop-sidebar .accordion-item {
    border-radius: 12px !important;
    margin-bottom: 16px;
    border: 1px solid #eee;
    overflow: hidden;
}
.tp-shop-sidebar .accordion-button {
    border-radius: 12px !important;
    font-weight: 600;
    padding: 16px 20px;
}
.tp-shop-sidebar .accordion-button:not(.collapsed) {
    border-radius: 12px 12px 0 0 !important;
}
.tp-shop-sidebar .accordion-body {
    padding: 16px 20px;
}
.tp-shop-sidebar .form-check-input {
    border-radius: 4px;
}

/* Price Range Slider */
.tp-shop-sidebar .noUi-connect {
    background: var(--tp-theme-primary, #821E23);
    border-radius: 4px;
}
.tp-shop-sidebar .noUi-horizontal {
    height: 6px;
    border-radius: 4px;
}
.tp-shop-sidebar .noUi-handle {
    border-radius: 50%;
    width: 18px !important;
    height: 18px !important;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

/* === Top Filter Bar === */
.tp-shop-top {
    background: #fff;
    border-radius: 16px;
    padding: 16px 24px;
    margin-bottom: 30px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
    border: 1px solid #f0f0f0;
}
.tp-shop-top-left {
    gap: 16px;
}
.tp-shop-top-result {
    background: #f8f9fa;
    padding: 10px 16px;
    border-radius: 10px;
    border: 1px solid #eee;
}
.tp-shop-top-result p {
    margin: 0;
    font-size: 13px;
    color: #666;
    font-weight: 500;
}
.tp-shop-top-tab .nav-link {
    border-radius: 8px !important;
    padding: 10px 14px;
    margin-right: 8px;
    transition: all 0.3s ease;
    background: #f5f5f5;
    border: 1px solid #eee;
}
.tp-shop-top-tab .nav-link:hover {
    background: #eee;
}
.tp-shop-top-tab .nav-link.active {
    background: var(--tp-theme-primary, #821E23);
    color: #fff;
    border-color: var(--tp-theme-primary, #821E23);
}
.tp-shop-top-select select,
.tp-shop-top-select .nice-select {
    border-radius: 10px !important;
    border: 1px solid #e0e0e0;
    padding: 10px 16px;
    min-height: 44px;
}
.nice-select .list {
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}
.nice-select .option {
    padding: 10px 16px;
    transition: background 0.2s ease;
}
.nice-select .option:hover,
.nice-select .option.selected {
    background: #f5f5f5;
}

/* Filter Button */
.tp-filter-btn {
    border-radius: 10px !important;
    padding: 10px 18px;
    background: #fff;
    border: 1px solid #e0e0e0;
    transition: all 0.3s ease;
}
.tp-filter-btn:hover {
    background: var(--tp-theme-primary, #821E23);
    color: #fff;
    border-color: var(--tp-theme-primary, #821E23);
}

/* === Pagination === */
.tp-pagination ul {
    gap: 8px;
}
.tp-pagination .page-link,
.tp-pagination li a,
.tp-pagination li span {
    border-radius: 10px !important;
    min-width: 42px;
    height: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #e0e0e0;
    transition: all 0.3s ease;
}
.tp-pagination .page-item.active .page-link,
.tp-pagination li.active a {
    background: var(--tp-theme-primary, #821E23);
    border-color: var(--tp-theme-primary, #821E23);
    color: #fff;
}

/* === Alert Box === */
.alert {
    border-radius: 12px !important;
}

/* === Filter Results Tags === */
.bb-filter-results {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 20px;
}
.bb-filter-results .badge,
.bb-filter-results .btn {
    border-radius: 20px !important;
    padding: 8px 16px;
}

/* === Category Description Box === */
.tp-product-listing-description {
    background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 30px;
    border: 1px solid #eee;
}

/* === Responsive === */
@media (max-width: 768px) {
    .tp-product-item {
        border-radius: 12px;
    }
    .tp-product-thumb,
    .tp-product-thumb img {
        border-radius: 12px 12px 0 0;
    }
    .tp-product-content {
        padding: 12px;
        border-radius: 0 0 12px 12px;
    }
    .tp-shop-top {
        border-radius: 10px;
        padding: 12px 16px;
    }
}
</style>

<section class="tp-shop-area @if (! theme_option('theme_breadcrumb_enabled', true)) pt-50 @endif">
    <div class="container position-relative">
        {!! dynamic_sidebar('products_by_category_top_sidebar') !!}

        @include(Theme::getThemeNamespace('views.ecommerce.includes.products-listing'), ['pageName' => $category->name, 'pageDescription' => $category->description])

        {!! dynamic_sidebar('products_by_category_bottom_sidebar') !!}
    </div>
</section>
