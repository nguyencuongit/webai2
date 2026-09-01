@if($config['zalo_link'])
    <a class="webai-zalo-float" href="{{ $config['zalo_link'] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $config['zalo_label'] ?: 'Zalo' }}">
        <span>{{ $config['zalo_label'] ?: 'Zalo' }}</span>
    </a>

    <style>
        .webai-zalo-float { position: fixed; right: 24px; bottom: 24px; z-index: 1050; display: inline-flex; align-items: center; justify-content: center; width: 68px; aspect-ratio: 1; border-radius: 50%; background: #0068ff; color: #fff; font-size: 16px; font-weight: 700; line-height: 1; text-decoration: none; box-shadow: 0 12px 28px rgba(0, 104, 255, .34); transition: transform .2s ease, box-shadow .2s ease; }
        .webai-zalo-float:hover { color: #fff; transform: translateY(-2px); box-shadow: 0 16px 32px rgba(0, 104, 255, .42); }
        @media (max-width: 575px) { .webai-mobile-account { display: none; } .webai-zalo-float { top: 10px; right: 16px; bottom: auto; z-index: 1001; width: 38px; border-radius: 10px; font-size: 10px; box-shadow: 0 4px 12px rgba(0, 104, 255, .28); } }
    </style>
@endif
