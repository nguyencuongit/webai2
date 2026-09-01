@php
    Theme::layout('full-width');
    Theme::set('pageTitle', __('Products'));
@endphp

<section class="tp-shop-area">
    <div class="container position-relative pt-10 pb-25">
        {!! dynamic_sidebar('products_listing_top_sidebar') !!}

        @include(Theme::getThemeNamespace('views.ecommerce.includes.products-listing'))

        {!! dynamic_sidebar('products_listing_bottom_sidebar') !!}
    </div>
</section>
