<style>
/* MINI CART REDESIGN - Modern & Premium Style */
.cartmini__area {
    border-radius: 16px 0 0 16px;
    box-shadow: -10px 0 40px rgba(0, 0, 0, 0.15);
    overflow: hidden;
}
.cartmini__area .cartmini__wrapper {
    padding: 0;
    background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
    height: 100%;
}
.cartmini__area .cartmini__top-wrapper {
    background: #fff;
}
.cartmini__area .cartmini__top {
    background: linear-gradient(135deg, var(--tp-theme-primary) 0%, #1a5a2a 100%);
    padding: 20px 25px;
    margin: 0;
    border-bottom: none;
}
.cartmini__area .cartmini__top .cartmini__top-title {
    border-bottom: none;
    padding: 0;
}
.cartmini__area .cartmini__top .cartmini__top-title h4 {
    color: #fff;
    font-size: 18px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
}
.cartmini__area .cartmini__top .cartmini__top-title h4::before {
    content: '';
    display: inline-block;
    width: 24px;
    height: 24px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='8' cy='21' r='1'/%3E%3Ccircle cx='19' cy='21' r='1'/%3E%3Cpath d='M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12'/%3E%3C/svg%3E");
    background-size: contain;
    background-repeat: no-repeat;
}
.cartmini__area .cartmini__close {
    top: 18px;
    right: 20px;
}
.cartmini__area .cartmini__close .cartmini__close-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    backdrop-filter: blur(4px);
}
.cartmini__area .cartmini__close .cartmini__close-btn:hover {
    background: rgba(255, 255, 255, 0.35);
    transform: rotate(90deg);
}
.cartmini__area .cartmini__close .cartmini__close-btn svg {
    width: 18px;
    height: 18px;
}
.cartmini__area .cartmini__widget {
    padding: 15px 20px;
    flex: 1;
    overflow-y: auto;
    background: #fff;
    scrollbar-width: thin;
}
.cartmini__area .cartmini__widget::-webkit-scrollbar {
    width: 4px;
}
.cartmini__area .cartmini__widget::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}
.cartmini__area .cartmini__widget::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 4px;
}
.cartmini__area .cartmini__widget-item {
    background: #f8fafc;
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 12px;
    border: 1px solid #eef2f6;
    transition: all 0.25s ease;
    position: relative;
}
.cartmini__area .cartmini__widget-item:hover {
    background: #f1f5f9;
    border-color: #e2e8f0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
}
.cartmini__area .cartmini__widget-item:last-child {
    margin-bottom: 0;
    border-bottom: 0;
}
.cartmini__area .cartmini__thumb {
    border: none;
    margin-right: 12px;
    flex-shrink: 0;
}
.cartmini__area .cartmini__thumb a {
    display: block;
    border-radius: 10px;
    overflow: hidden;
    background: #fff;
    border: 1px solid #eef2f6;
}
.cartmini__area .cartmini__thumb img {
    width: 72px;
    height: 72px;
    object-fit: cover;
    border-radius: 10px;
    transition: transform 0.3s ease;
}
.cartmini__area .cartmini__thumb:hover img {
    transform: scale(1.05);
}
.cartmini__area .cartmini__content {
    padding-right: 30px;
    flex: 1;
    min-width: 0;
}
.cartmini__area .cartmini__title {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 6px;
    line-height: 1.4;
}
.cartmini__area .cartmini__title a {
    color: var(--tp-common-black);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    transition: color 0.2s ease;
}
.cartmini__area .cartmini__title a:hover {
    color: var(--tp-theme-primary);
}
.cartmini__area .cartmini__price-wrapper {
    margin-top: 6px;
}
.cartmini__area .cartmini__price {
    font-size: 15px;
    font-weight: 700;
    color: var(--tp-theme-primary);
    display: flex;
    align-items: center;
    gap: 8px;
}
.cartmini__area .tp-product-quantity {
    margin-top: 8px;
}
.cartmini__area .tp-product-quantity .tp-cart-input {
    border-radius: 6px !important;
    background: #fff !important;
}
.cartmini__area .tp-product-quantity .tp-cart-minus,
.cartmini__area .tp-product-quantity .tp-cart-plus {
    background: #fff;
    border-radius: 4px;
}
.cartmini__area .tp-product-quantity .tp-cart-minus:hover,
.cartmini__area .tp-product-quantity .tp-cart-plus:hover {
    background: var(--tp-theme-primary);
    color: #fff;
}
.cartmini__area .cartmini__del {
    position: absolute !important;
    top: 12px;
    right: 12px;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #fee2e2;
    color: #ef4444;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.25s ease;
    line-height: 1;
}
.cartmini__area .cartmini__del svg {
    width: 14px;
    height: 14px;
}
.cartmini__area .cartmini__del:hover {
    background: #ef4444;
    color: #fff;
    transform: scale(1.1);
}
.cartmini__area .cartmini__checkout {
    background: #fff;
    padding: 20px;
    border-top: 2px solid #f1f5f9;
    padding-bottom: 30px;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.05);
    flex-shrink: 0;
}
.cartmini__area .cartmini__checkout .cartmini__checkout-title {
    background: #f8fafc;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 20px;
}
.cartmini__area .cartmini__checkout .cartmini__checkout-title > div {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
}
.cartmini__area .cartmini__checkout .cartmini__checkout-title > div:not(:last-child) {
    border-bottom: 1px dashed #e2e8f0;
    padding-bottom: 10px;
    margin-bottom: 6px;
}
.cartmini__area .cartmini__checkout .cartmini__checkout-title > div:last-child h4 {
    font-size: 16px;
    color: var(--tp-common-black);
}
.cartmini__area .cartmini__checkout .cartmini__checkout-title > div:last-child span {
    font-size: 18px;
    color: var(--tp-theme-primary);
    font-weight: 700;
}
.cartmini__area .cartmini__checkout .cartmini__checkout-title h4 {
    font-size: 14px;
    font-weight: 500;
    color: #64748b;
    margin: 0;
}
.cartmini__area .cartmini__checkout .cartmini__checkout-title span {
    font-size: 15px;
    font-weight: 600;
    color: var(--tp-common-black);
}
.cartmini__area .cartmini__checkout .cartmini__checkout-btn {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.cartmini__area .cartmini__checkout .cartmini__checkout-btn .tp-btn {
    border-radius: 10px;
    font-size: 15px;
    font-weight: 600;
    padding: 5px 24px;
    text-align: center;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin: 0;
}
.cartmini__area .cartmini__checkout .cartmini__checkout-btn .tp-btn:not(.tp-btn-border) {
    background: linear-gradient(135deg, var(--tp-theme-primary) 0%, #1a5a2a 100%);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    color: #fff;
    border: none;
}
.cartmini__area .cartmini__checkout .cartmini__checkout-btn .tp-btn:not(.tp-btn-border):hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
}
.cartmini__area .cartmini__checkout .cartmini__checkout-btn .tp-btn.tp-btn-border {
    background: transparent;
    border: 2px solid #e2e8f0;
    color: var(--tp-common-black);
}
.cartmini__area .cartmini__checkout .cartmini__checkout-btn .tp-btn.tp-btn-border:hover {
    background: #f8fafc;
    border-color: var(--tp-theme-primary);
    color: var(--tp-theme-primary);
}
.cartmini__area .cartmini__empty {
    padding: 60px 30px;
    margin-top: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: calc(100vh - 200px);
}
.cartmini__area .cartmini__empty img {
    max-width: 180px;
    margin-bottom: 25px;
    opacity: 0.8;
    animation: cartFloatAnim 3s ease-in-out infinite;
}
.cartmini__area .cartmini__empty p {
    font-size: 18px;
    color: #64748b;
    margin-bottom: 20px;
    font-weight: 500;
}
.cartmini__area .cartmini__empty .tp-btn {
    background: linear-gradient(135deg, var(--tp-theme-primary) 0%, #1a5a2a 100%);
    color: #fff;
    border-radius: 10px;
    padding: 14px 32px;
    font-weight: 600;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
    transition: all 0.3s ease;
}
.cartmini__area .cartmini__empty .tp-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
}
@keyframes cartFloatAnim {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}
@media (max-width: 576px) {
    .cartmini__area {
        width: 100% !important;
        border-radius: 0;
    }
    .cartmini__area .cartmini__widget {
        height: calc(100vh - 340px);
    }
    .cartmini__area .cartmini__widget-item {
        padding: 12px;
    }
    .cartmini__area .cartmini__thumb img {
        width: 60px;
        height: 60px;
    }
    .cartmini__area .cartmini__checkout {
        padding: 15px;
        padding-bottom: 25px;
    }
}
</style>

<div class="cartmini__wrapper d-flex justify-content-between flex-column">
    <div class="cartmini__top-wrapper">
        <div class="cartmini__top p-relative">
            <div class="cartmini__top-title">
                <h4>{{ __('Shopping cart') }}</h4>
            </div>
            <div class="cartmini__close">
                <button type="button" class="cartmini__close-btn cartmini-close-btn" aria-label="{{ __('Close cart') }}">
                    <x-core::icon name="ti ti-x" />
                </button>
            </div>
        </div>

        @if ($ajax ?? false)
            {!! Theme::partial('mini-cart.content') !!}
        @else
            <div data-bb-toggle="mini-cart-content-slot"></div>
        @endif
    </div>

    @if ($ajax ?? false)
        {!! Theme::partial('mini-cart.footer') !!}
    @else
        <div data-bb-toggle="mini-cart-footer-slot"></div>
    @endif
</div>
