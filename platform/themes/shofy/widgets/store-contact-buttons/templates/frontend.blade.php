<div class="tp-product-contact-buttons d-flex flex-wrap gap-2">
    @if($config['hotline'])
        <a href="tel:{{ $config['hotline'] }}" class="tp-contact-btn tp-contact-btn-hotline">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
            </svg>
            <span>{{ $config['hotline_label'] ?: __('Đặt nhanh') }}</span>
        </a>
    @endif
    @if($config['zalo_link'])
        <a href="{{ $config['zalo_link'] }}" target="_blank" class="tp-contact-btn tp-contact-btn-zalo">
            <img src="https://page.widget.zalo.me/static/images/2.0/Logo.svg" alt="Zalo" width="24" height="24">
            <span>{{ $config['zalo_label'] ?: 'Zalo OA' }}</span>
        </a>
    @endif
    @if($config['facebook_link'])
        <a href="{{ $config['facebook_link'] }}" target="_blank" class="tp-contact-btn tp-contact-btn-facebook">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
            </svg>
            <span>{{ $config['facebook_label'] ?: 'Facebook' }}</span>
        </a>
    @endif
</div>

<style>
.tp-product-contact-buttons {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}
.tp-contact-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
    transition: all 0.3s ease;
    flex: 1;
    justify-content: center;
}
.tp-contact-btn-hotline {
    background: linear-gradient(135deg, #ff9800, #f57c00);
    border-color: #ff9800;
    color: #fff;
}
.tp-contact-btn-hotline:hover {
    background: linear-gradient(135deg, #f57c00, #e65100);
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 152, 0, 0.4);
}
.tp-contact-btn-zalo {
    background: #fff;
    border-color: #0068ff;
    color: #0068ff;
}
.tp-contact-btn-zalo:hover {
    background: #0068ff;
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 104, 255, 0.3);
}
.tp-contact-btn-facebook {
    background: #fff;
    border-color: #1877f2;
    color: #1877f2;
}
.tp-contact-btn-facebook:hover {
    background: #1877f2;
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(24, 119, 242, 0.3);
}
.tp-contact-btn-facebook:hover svg {
    fill: #fff;
}

@media (max-width: 576px) {
    .tp-product-contact-buttons {
        flex-direction: column;
    }
    .tp-contact-btn {
        flex: unset;
        width: 100%;
    }
}
</style>
