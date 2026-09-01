@php
    Theme::asset()->container('footer')->usePath()->add('nice-select', 'js/nice-select.js');
@endphp

{{-- Custom CSS cho thanh filter phía trên --}}
<style>
/* Top Filter Bar Container */
.tp-shop-top {
    background: #fff;
    border-radius: 16px;
    padding: 16px 24px;
    margin-bottom: 30px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
    border: 1px solid #f0f0f0;
}

/* Left Side - Tabs and Result */
.tp-shop-top-left {
    gap: 16px;
    flex-wrap: nowrap !important;
}

/* Result Text */
.tp-shop-top-result {
    background: linear-gradient(135deg, #f8f9fa, #fff);
    padding: 10px 18px;
    border-radius: 10px;
    border: 1px solid #eee;
    white-space: nowrap;
}
.tp-shop-top-result p {
    margin: 0;
    font-size: 13px;
    color: #555;
    font-weight: 500;
    white-space: nowrap;
}

/* Grid/List Tabs */
.tp-shop-top-tab {
    flex-shrink: 0;
}
.tp-shop-top-tab .nav-tabs {
    border: none;
    gap: 6px;
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: nowrap !important;
}
.tp-shop-top-tab .nav-tabs .nav-item {
    margin-bottom: 0;
}
.tp-shop-top-tab .nav-link {
    border-radius: 10px !important;
    padding: 10px 14px;
    transition: all 0.3s ease;
    background: #f5f5f5;
    border: 1px solid #eee !important;
    color: #666;
}
.tp-shop-top-tab .nav-link:hover {
    background: #eee;
    color: #333;
}
.tp-shop-top-tab .nav-link.active {
    background: var(--tp-theme-primary, #821E23) !important;
    color: #fff !important;
    border-color: var(--tp-theme-primary, #821E23) !important;
}

/* Dropdowns */
.tp-shop-top-select {
    margin-left: 12px;
}
.tp-shop-top-select .nice-select {
    border-radius: 10px !important;
    border: 1px solid #e0e0e0;
    padding: 0 40px 0 16px;
    height: 44px;
    line-height: 44px;
    background: #fff;
    font-size: 13px;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
}
.tp-shop-top-select .nice-select .current {
    line-height: 1;
}
.tp-shop-top-select .nice-select:hover {
    border-color: #ccc;
}
.tp-shop-top-select .nice-select .list {
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
    border: 1px solid #eee;
    overflow: hidden;
    margin-top: 8px;
}
.tp-shop-top-select .nice-select .option {
    padding: 10px 16px;
    font-size: 13px;
    transition: background 0.2s ease;
}
.tp-shop-top-select .nice-select .option:hover,
.tp-shop-top-select .nice-select .option.selected {
    background: #f5f5f5;
}

/* Filter Button */
.tp-filter-btn {
    border-radius: 10px !important;
    padding: 10px 18px;
    background: #fff;
    border: 1px solid #e0e0e0;
    font-weight: 500;
    transition: all 0.3s ease;
}
.tp-filter-btn:hover {
    background: var(--tp-theme-primary, #821E23);
    color: #fff;
    border-color: var(--tp-theme-primary, #821E23);
}

/* Responsive */
@media (max-width: 768px) {
    .tp-shop-top {
        border-radius: 12px;
        padding: 12px 16px;
    }
    .tp-shop-top-left {
        flex-wrap: wrap;
        gap: 12px;
    }
    .tp-shop-top-result {
        width: 100%;
        text-align: center;
    }
}
</style>

<div class="tp-shop-top mb-45">
    @if(products_listing_layout() === 'no-sidebar')
        <form action="{{ URL::current() }}" method="GET" class="bb-product-form-filter">
            @include(EcommerceHelper::viewPath('includes.filters.filter-hidden-fields'))
        </form>
    @endif

    <div class="row">
        <div class="col-xl-6">
            <div class="tp-shop-top-left d-flex align-items-center">
                <div class="tp-shop-top-tab tp-tab">
                    <ul class="nav nav-tabs" id="productTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button @class(['nav-link', 'active' => request()->query('layout', get_product_layout()) === 'grid']) data-value="grid" id="grid-tab" data-bb-toggle="change-product-filter-layout" type="button" role="tab" aria-controls="grid-tab-pane" aria-selected="true">
                                <x-core::icon name="ti ti-layout-grid" />
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button @class(['nav-link', 'active' => request()->query('layout', get_product_layout()) === 'list']) data-value="list" id="list-tab" data-bb-toggle="change-product-filter-layout" type="button" role="tab" aria-controls="list-tab-pane" aria-selected="false">
                                <x-core::icon name="ti ti-layout-list" />
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="tp-shop-top-result">
                    <p>{{ __('Showing :from - :to of :total products', ['from' => $products->firstItem() ?: 0, 'to' => $products->lastItem() ?: 0, 'total' => $products->total()]) }}</p>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="tp-shop-top-right d-sm-flex align-items-center justify-content-xl-end">
                <div class="tp-shop-top-select">
                    <select name="sort-by" data-nice-select>
                        @foreach (EcommerceHelper::getSortParams() as $key => $value)
                            <option value="{{ $key }}" @selected(BaseHelper::stringify(request()->input('sort-by')) === $key)>{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tp-shop-top-select sort-by">
                    <select name="per-page" data-nice-select>
                        @foreach (EcommerceHelper::getShowParams() as $key => $value)
                            <option value="{{ $key }}" @selected($key === request()->integer('per-page', theme_option('number_of_products_per_page', 12)))>{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
                @if (EcommerceHelper::hasAnyProductFilters())
                    <div @class(['tp-shop-top-filter', 'd-lg-none' => products_listing_layout() !== 'no-sidebar'])>
                        <button type="button" class="tp-filter-btn" data-bb-toggle="toggle-filter-sidebar">
                            <span>
                                <svg width="16" height="15" viewBox="0 0 16 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M14.9998 3.45001H10.7998" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M3.8 3.45001H1" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
                                    <path
                                        d="M6.5999 5.9C7.953 5.9 9.0499 4.8031 9.0499 3.45C9.0499 2.0969 7.953 1 6.5999 1C5.2468 1 4.1499 2.0969 4.1499 3.45C4.1499 4.8031 5.2468 5.9 6.5999 5.9Z"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                        stroke-miterlimit="10"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                    <path d="M15.0002 11.15H12.2002" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M5.2 11.15H1" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
                                    <path
                                        d="M9.4002 13.6C10.7533 13.6 11.8502 12.5031 11.8502 11.15C11.8502 9.79691 10.7533 8.70001 9.4002 8.70001C8.0471 8.70001 6.9502 9.79691 6.9502 11.15C6.9502 12.5031 8.0471 13.6 9.4002 13.6Z"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                        stroke-miterlimit="10"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </span>
                            {{ __('Filter') }}
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
