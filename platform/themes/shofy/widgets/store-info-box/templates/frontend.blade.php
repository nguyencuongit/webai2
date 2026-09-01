<div class="tp-store-info-box">
    @if($config['title'])
        <h4 class="tp-store-info-title">{{ $config['title'] }}</h4>
    @endif
    <div class="tp-store-info-content">
        @if($config['address'])
            <p class="tp-store-address">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                </svg>
                {{ $config['address'] }}
            </p>
        @endif
        @if($config['phone'])
            <a href="tel:{{ preg_replace('/[^0-9]/', '', $config['phone']) }}" class="tp-store-phone">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                </svg>
                {{ $config['phone'] }}
            </a>
        @endif
        @if($config['google_map_link'])
            <a href="{{ $config['google_map_link'] }}" target="_blank" class="tp-store-map-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                </svg>
                {{ __('Bản đồ') }}
            </a>
        @endif
    </div>
</div>

<style>
.tp-store-info-box {
    background: #fef6f6;
    border: 1px solid #fce4e4;
    border-radius: 12px;
    padding: 20px;
}
.tp-store-info-title {
    font-size: 16px;
    font-weight: 600;
    color: #e74c3c;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px dashed #fce4e4;
}
.tp-store-info-content {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.tp-store-address {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    color: #333;
    font-size: 14px;
    margin: 0;
}
.tp-store-address svg {
    flex-shrink: 0;
    margin-top: 2px;
    color: #e74c3c;
}
.tp-store-phone {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #fff;
    border: 2px solid #e74c3c;
    border-radius: 25px;
    color: #e74c3c;
    font-weight: 700;
    font-size: 16px;
    text-decoration: none;
    transition: all 0.3s ease;
    width: fit-content;
}
.tp-store-phone:hover {
    background: #e74c3c;
    color: #fff;
}
.tp-store-phone:hover svg {
    stroke: #fff;
}
.tp-store-phone svg {
    color: #e74c3c;
}
.tp-store-map-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 20px;
    color: #666;
    font-size: 13px;
    text-decoration: none;
    transition: all 0.3s ease;
    width: fit-content;
}
.tp-store-map-btn:hover {
    background: #f5f5f5;
    color: #333;
    border-color: #bbb;
}
</style>
