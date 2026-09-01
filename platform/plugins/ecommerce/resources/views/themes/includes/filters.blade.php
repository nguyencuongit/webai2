@if (EcommerceHelper::hasAnyProductFilters())
    @php
        $dataForFilter = EcommerceHelper::dataForFilter($category ?? null);
        [$categories, $brands, $tags, $rand, $categoriesRequest, $urlCurrent, $categoryId, $maxFilterPrice] = $dataForFilter;
    @endphp

    {{-- Custom CSS cho sidebar lọc - Bo tròn và hài hòa --}}
    <style>
    /* === Sidebar Column Width === */
    .bb-filter-offcanvas-area:not(.bb-filter-offcanvas-area-on-desktop) {
        min-width: 280px;
    }
    
    /* === Main Sidebar Container === */
    .bb-shop-sidebar {
        background: #fff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
        border: 1px solid #f0f0f0;
        min-width: 260px;
    }

    /* === Each Filter Block === */
    .bb-product-filter {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 16px 18px;
        margin-bottom: 16px;
        border: 1px solid #eee;
        transition: all 0.3s ease;
    }
    .bb-product-filter:last-child {
        margin-bottom: 0;
    }
    .bb-product-filter:hover {
        border-color: #ddd;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    }

    /* === Filter Title === */
    .bb-product-filter-title {
        font-size: 14px;
        font-weight: 700;
        color: #333;
        margin-bottom: 14px;
        padding-bottom: 10px;
        border-bottom: 1px dashed #e0e0e0 !important;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* === Filter Content === */
    .bb-product-filter-content {
        padding: 0;
    }

    /* === Category List === */
    .bb-product-filter-items {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .bb-product-filter-item {
        margin-bottom: 8px;
        padding: 0;
        background: transparent;
        border-radius: 8px;
        transition: all 0.2s ease;
        border: none;
        position: relative;
    }
    .bb-product-filter-item:last-child {
        margin-bottom: 0;
    }
    
    /* Category Link */
    .bb-product-filter-item > a,
    .bb-product-filter-link {
        color: #555;
        text-decoration: none;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        background: #fff;
        border-radius: 8px;
        border: 1px solid #eee;
        transition: all 0.2s ease;
    }
    .bb-product-filter-item > a:hover,
    .bb-product-filter-link:hover {
        background: #f5f5f5;
        border-color: #ddd;
        color: var(--tp-theme-primary, #821E23);
    }
    .bb-product-filter-item > a.active,
    .bb-product-filter-link.active {
        background: var(--tp-theme-primary, #821E23);
        color: #fff;
        border-color: var(--tp-theme-primary, #821E23);
        font-weight: 600;
    }
    .bb-product-filter-link.active svg {
        color: #fff;
    }

    /* Hide default folder icon, show custom styling */
    .bb-product-filter-link svg,
    .bb-product-filter-link .ti {
        width: 18px;
        height: 18px;
        flex-shrink: 0;
        color: #888;
    }
    
    /* Expand/Collapse Button */
    .bb-product-filter-item > button[data-bb-toggle="toggle-product-categories-tree"] {
        position: absolute;
        right: 10px;
        top: 10px;
        width: 28px;
        height: 28px;
        border-radius: 6px;
        background: #f0f0f0;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        z-index: 2;
    }
    .bb-product-filter-item > button[data-bb-toggle="toggle-product-categories-tree"]:hover {
        background: #e0e0e0;
    }
    .bb-product-filter-item > button[data-bb-toggle="toggle-product-categories-tree"] svg {
        width: 14px;
        height: 14px;
        color: #666;
    }
    
    /* Nested Categories */
    .bb-product-filter-item .bb-product-filter-items {
        margin-top: 8px;
        margin-left: 16px;
        padding-left: 12px;
        border-left: 2px solid #eee;
    }
    .bb-product-filter-item .bb-product-filter-items .bb-product-filter-item > a {
        padding: 8px 12px;
        font-size: 12px;
    }

    /* === Checkbox Styling === */
    .bb-product-filter-item input[type="checkbox"] {
        width: 18px;
        height: 18px;
        border-radius: 4px;
        border: 2px solid #ccc;
        margin-right: 10px;
        cursor: pointer;
        accent-color: var(--tp-theme-primary, #821E23);
    }
    .bb-product-filter-item label {
        cursor: pointer;
        font-size: 13px;
        color: #555;
        display: flex;
        align-items: center;
    }

    /* === Price Slider === */
    .bb-product-price-filter {
        padding: 10px 0;
    }
    .price-slider {
        margin-bottom: 15px;
    }
    .noUi-target {
        background: #e9ecef;
        border-radius: 6px;
        border: none;
        box-shadow: none;
        height: 8px;
    }
    .noUi-connect {
        background: var(--tp-theme-primary, #821E23);
        border-radius: 6px;
    }
    .noUi-horizontal .noUi-handle {
        width: 20px !important;
        height: 20px !important;
        border-radius: 50%;
        background: #fff;
        border: 3px solid var(--tp-theme-primary, #821E23);
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        cursor: pointer;
        top: -6px;
    }
    .noUi-horizontal .noUi-handle::before,
    .noUi-horizontal .noUi-handle::after {
        display: none;
    }
    .input-range-label {
        background: #fff;
        padding: 8px 14px;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
        font-weight: 600;
        font-size: 13px;
        color: #333;
    }

    /* === Nested Categories === */
    .bb-product-filter-item .bb-product-filter-items {
        margin-top: 8px;
        margin-left: 12px;
        padding-left: 12px;
        border-left: 2px solid #eee;
    }
    .bb-product-filter-item .bb-product-filter-items .bb-product-filter-item {
        background: transparent;
        padding: 6px 10px;
        margin-bottom: 6px;
    }

    /* === Product Count Badge === */
    .bb-product-filter-item .count,
    .bb-product-filter-item .badge {
        background: #e9ecef;
        color: #666;
        font-size: 11px;
        padding: 3px 8px;
        border-radius: 12px;
        font-weight: 600;
    }

    /* === Attribute Colors/Swatches === */
    .bb-product-filter-attribute-item .bb-product-filter-items {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    .bb-attribute-swatch {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        border: 2px solid #ddd;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .bb-attribute-swatch:hover,
    .bb-attribute-swatch.active {
        border-color: var(--tp-theme-primary, #821E23);
        transform: scale(1.1);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    /* === Filter Offcanvas (Mobile) === */
    .bb-filter-offcanvas-wrapper {
        background: #fff;
        border-radius: 0 20px 20px 0;
        padding: 20px;
    }
    .bb-filter-offcanvas-close-btn {
        background: #f5f5f5;
        border: none;
        border-radius: 10px;
        padding: 10px 16px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
    }
    .bb-filter-offcanvas-close-btn:hover {
        background: #eee;
    }

    /* === Responsive === */
    @media (max-width: 768px) {
        .bb-shop-sidebar {
            border-radius: 12px;
            padding: 16px;
        }
        .bb-product-filter {
            border-radius: 10px;
            padding: 14px;
        }
    }
    </style>

    <div class="bb-shop-sidebar">
        <form action="{{ URL::current() }}" data-action="{{ route('public.products') }}" method="GET" class="bb-product-form-filter">
            @include(EcommerceHelper::viewPath('includes.filters.filter-hidden-fields'))

            {!! apply_filters('theme_ecommerce_products_filter_before', null, $dataForFilter) !!}

            @if (EcommerceHelper::isEnabledFilterProductsByCategories())
                @include(EcommerceHelper::viewPath('includes.filters.categories'))
            @endif

            @if (EcommerceHelper::isEnabledFilterProductsByBrands())
                @include(EcommerceHelper::viewPath('includes.filters.brands'))
            @endif

            @if (EcommerceHelper::isEnabledFilterProductsByTags())
                @include(EcommerceHelper::viewPath('includes.filters.tags'))
            @endif

            @if (EcommerceHelper::isEnabledFilterProductsByPrice() && (! EcommerceHelper::hideProductPrice() || EcommerceHelper::isCartEnabled()))
                @include(EcommerceHelper::viewPath('includes.filters.price'))
            @endif

            @include(EcommerceHelper::viewPath('includes.filters.discounted-only'))

            @if (EcommerceHelper::isEnabledFilterProductsByAttributes())
                @include(EcommerceHelper::viewPath('includes.filters.attributes', ['view' => $view ?? null]))
            @endif

            {!! apply_filters('theme_ecommerce_products_filter_after', null, $dataForFilter) !!}
        </form>
    </div>
@endif
