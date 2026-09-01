<style>
    /* ============================
   TESTIMONIALS - PANOLOCAL STYLE SLIDER
   ============================ */
    .testimonials-section {
        position: relative;
        overflow: hidden;
        background: linear-gradient(135deg, #f8f9fa 0%, #fff 50%, #f0f4f8 100%);
        padding: 80px 0 100px;
    }

    .testimonials-slider {
        position: relative;
        max-width: 1100px;
        margin: 0 auto;
    }

    .testimonials-slider-container {
        position: relative;
        perspective: 1500px;
    }

    .testimonials-slider-track {
        position: relative;
        min-height: 500px;
    }

    /* CSS-Only Auto-Play Slider - Ultra Smooth Version */
    /* Animation: 12s total (5s per slide) for smoother experience */


    .testimonial-slide {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        transform-origin: center center;
        will-change: transform, opacity, filter;
        /* Default - card phía sau xa nhất */
        opacity: 0.15;
        transform: translateY(60px) scale(0.88);
        z-index: 0;
        filter: blur(3px);
    }

    /* Slide 1 */
    .testimonial-slide:nth-child(1) {
        animation: stackSlide1 12s ease-in-out infinite;
    }

    /* Slide 2 */
    .testimonial-slide:nth-child(2) {
        animation: stackSlide2 12s ease-in-out infinite;
    }

    /* Slide 3 */
    .testimonial-slide:nth-child(3) {
        animation: stackSlide3 12s ease-in-out infinite;
    }

    /* Slide 4 */
    .testimonial-slide:nth-child(4) {
        animation: stackSlide4 12s ease-in-out infinite;
    }

    /* 
   STACKED CARDS EFFECT:
   - Active (z-index: 4): opacity 1, scale 1, blur 0
   - Next (z-index: 3): opacity 0.5, scale 0.95, translateY 25px, blur 1px  
   - Third (z-index: 2): opacity 0.3, scale 0.90, translateY 45px, blur 2px
   - Back (z-index: 1): opacity 0.15, scale 0.85, translateY 60px, blur 3px
*/

    @keyframes stackSlide1 {

        /* Active: 0% - 20% */
        0%,
        20% {
            opacity: 1;
            transform: translateY(0) scale(1) rotate(0deg);
            z-index: 4;
            filter: blur(0);
        }

        /* Transition out: 21% - 25% */
        25% {
            opacity: 0;
            transform: translateY(-30px) scale(0.98) rotate(0deg);
            z-index: 5;
            filter: blur(0);
        }

        /* Back position: ẨN HOÀN TOÀN */
        26%,
        45% {
            opacity: 0;
            transform: translateY(60px) scale(0.85) rotate(-3deg);
            z-index: 1;
            filter: blur(3px);
            visibility: hidden;
        }

        /* Third position: MỜ 2 */
        46%,
        68% {
            opacity: 0.3;
            transform: translateY(45px) scale(0.90) rotate(-2deg);
            z-index: 2;
            filter: blur(2px);
            visibility: visible;
        }

        /* Next position: MỜ 1 */
        70%,
        93% {
            opacity: 0.5;
            transform: translateY(25px) scale(0.95) rotate(-1deg);
            z-index: 3;
            filter: blur(1px);
        }

        /* Transition to active: 95% - 100% */
        95%,
        100% {
            opacity: 1;
            transform: translateY(0) scale(1) rotate(0deg);
            z-index: 4;
            filter: blur(0);
        }
    }

    @keyframes stackSlide2 {

        /* Next position: MỜ 1 */
        0%,
        20% {
            opacity: 0.5;
            transform: translateY(25px) scale(0.95) rotate(-1deg);
            z-index: 3;
            filter: blur(1px);
        }

        /* Active: 25% - 45% */
        25%,
        45% {
            opacity: 1;
            transform: translateY(0) scale(1) rotate(0deg);
            z-index: 4;
            filter: blur(0);
        }

        /* Transition out */
        50% {
            opacity: 0;
            transform: translateY(-30px) scale(0.98) rotate(0deg);
            z-index: 5;
            filter: blur(0);
        }

        /* Back position: ẨN HOÀN TOÀN */
        51%,
        68% {
            opacity: 0;
            transform: translateY(60px) scale(0.85) rotate(-3deg);
            z-index: 1;
            filter: blur(3px);
            visibility: hidden;
        }

        /* Third position: MỜ 2 */
        70%,
        93% {
            opacity: 0.3;
            transform: translateY(45px) scale(0.90) rotate(-2deg);
            z-index: 2;
            filter: blur(2px);
            visibility: visible;
        }

        /* Next position: MỜ 1 */
        95%,
        100% {
            opacity: 0.5;
            transform: translateY(25px) scale(0.95) rotate(-1deg);
            z-index: 3;
            filter: blur(1px);
        }
    }

    @keyframes stackSlide3 {

        /* Third position: MỜ 2 */
        0%,
        20% {
            opacity: 0.3;
            transform: translateY(45px) scale(0.90) rotate(-2deg);
            z-index: 2;
            filter: blur(2px);
        }

        /* Next position: MỜ 1 */
        25%,
        45% {
            opacity: 0.5;
            transform: translateY(25px) scale(0.95) rotate(-1deg);
            z-index: 3;
            filter: blur(1px);
        }

        /* Active: 50% - 70% */
        50%,
        70% {
            opacity: 1;
            transform: translateY(0) scale(1) rotate(0deg);
            z-index: 4;
            filter: blur(0);
        }

        /* Transition out */
        75% {
            opacity: 0;
            transform: translateY(-30px) scale(0.98) rotate(0deg);
            z-index: 5;
            filter: blur(0);
        }

        /* Back position: ẨN HOÀN TOÀN */
        76%,
        93% {
            opacity: 0;
            transform: translateY(60px) scale(0.85) rotate(-3deg);
            z-index: 1;
            filter: blur(3px);
            visibility: hidden;
        }

        /* Third position: MỜ 2 */
        95%,
        100% {
            opacity: 0.3;
            transform: translateY(45px) scale(0.90) rotate(-2deg);
            z-index: 2;
            filter: blur(2px);
            visibility: visible;
        }
    }

    @keyframes stackSlide4 {

        /* Back position: ẨN HOÀN TOÀN */
        0%,
        20% {
            opacity: 0;
            transform: translateY(60px) scale(0.85) rotate(-3deg);
            z-index: 1;
            filter: blur(3px);
            visibility: hidden;
        }

        /* Third position: MỜ 2 */
        25%,
        45% {
            opacity: 0.3;
            transform: translateY(45px) scale(0.90) rotate(-2deg);
            z-index: 2;
            filter: blur(2px);
            visibility: visible;
        }

        /* Next position: MỜ 1 */
        50%,
        70% {
            opacity: 0.5;
            transform: translateY(25px) scale(0.95) rotate(-1deg);
            z-index: 3;
            filter: blur(1px);
        }

        /* Active: 75% - 93% */
        75%,
        93% {
            opacity: 1;
            transform: translateY(0) scale(1) rotate(0deg);
            z-index: 4;
            filter: blur(0);
        }

        /* Transition out */
        95%,
        100% {
            opacity: 0;
            transform: translateY(-30px) scale(0.98) rotate(0deg);
            z-index: 5;
            filter: blur(0);
        }
    }

    /* Hover để pause animation */
    .testimonials-slider:hover .testimonial-slide {
        animation-play-state: paused;
    }

    .testimonials-slide-inner {
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 30px;
        align-items: stretch;
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
        min-height: 400px;
    }

    .testimonials-slide-image {
        position: relative;
        overflow: hidden;
        min-height: 100%;
    }

    .testimonials-slide-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center top;
        transition: transform 0.6s ease;
        position: absolute;
        top: 0;
        left: 0;
    }

    .testimonials-image-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 50%;
        background: linear-gradient(to top, rgba(139, 0, 0, 0.3) 0%, transparent 100%);
    }

    .testimonials-slide-content {
        padding: 30px 40px 30px 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: flex-start;
    }

    .testimonials-section .testimonials-quote-icon {
        width: 50px !important;
        height: 50px !important;
        background: linear-gradient(135deg, #8B0000 0%, #5a0000 100%) !important;
        border-radius: 14px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        margin-bottom: 18px !important;
        transform: rotate(-5deg) !important;
        box-shadow: 0 8px 25px rgba(139, 0, 0, 0.3) !important;
    }

    .testimonials-section .testimonials-quote-icon i {
        font-size: 22px !important;
        color: white !important;
    }

    .testimonials-section .testimonials-slide-content .testimonials-rating {
        display: flex !important;
        gap: 3px !important;
        margin-bottom: 15px !important;
        visibility: visible !important;
        opacity: 1 !important;
    }

    .testimonials-section .testimonials-slide-content .testimonials-rating i,
    .testimonials-section .testimonials-slide-content .testimonials-rating i.fa-star,
    .testimonials-section .testimonials-slide-content .testimonials-rating i.fa-solid,
    .testimonials-section .testimonials-slide-content .testimonials-rating i.fa-regular {
        font-size: 16px !important;
        color: #FFD700 !important;
        visibility: visible !important;
        opacity: 1 !important;
        display: inline-block !important;
        -webkit-text-stroke: 0 !important;
        text-shadow: none !important;
    }

    .testimonials-section .testimonials-slide-content .testimonials-rating i.fa-regular {
        color: #e0e0e0 !important;
    }

    .testimonials-section .testimonials-slide-content blockquote {
        font-size: 17px !important;
        line-height: 1.7 !important;
        color: #333333 !important;
        font-style: italic !important;
        margin-bottom: 20px !important;
        position: relative !important;
        /* Reset styles từ theme.css */
        background: transparent !important;
        padding: 0 !important;
        border: none !important;
        border-left: none !important;
        quotes: none !important;
        z-index: auto !important;
    }

    .testimonials-section .testimonials-slide-content blockquote::before,
    .testimonials-section .testimonials-slide-content blockquote::after {
        content: '' !important;
        display: none !important;
    }

    .testimonials-section .testimonials-slide-content blockquote p {
        color: #333333 !important;
        font-size: 17px !important;
        font-weight: 400 !important;
        line-height: 1.7 !important;
        margin: 0 !important;
    }

    .testimonials-section .testimonials-author-details {
        margin-bottom: 15px !important;
    }

    .testimonials-section .testimonials-author-details h4 {
        font-size: 18px !important;
        font-weight: 700 !important;
        color: #1a1a1a !important;
        margin-bottom: 4px !important;
        font-family: var(--font-heading) !important;
    }

    .testimonials-section .testimonials-author-details span {
        font-size: 14px !important;
        color: #666666 !important;
        text-transform: uppercase !important;
        letter-spacing: 1px !important;
    }

    .testimonials-company-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        padding: 10px 20px;
        border-radius: 30px;
        font-size: 13px;
        color: var(--text-medium);
        font-weight: 500;
    }

    .testimonials-company-badge i {
        color: var(--primary);
    }

    /* Progress Bar */
    .testimonials-slider-progress {
        max-width: 300px;
        height: 4px;
        background: #e0e0e0;
        border-radius: 2px;
        margin: 30px auto 0;
        overflow: hidden;
    }

    .testimonials-progress-bar {
        height: 100%;
        background: linear-gradient(90deg, var(--primary) 0%, var(--gold) 100%);
        border-radius: 2px;
        width: 0%;
        animation: progressSlide 12s linear infinite;
    }

    @keyframes progressSlide {
        0% {
            width: 0%;
        }

        25% {
            width: 25%;
        }

        50% {
            width: 50%;
        }

        75% {
            width: 75%;
        }

        100% {
            width: 100%;
        }
    }

    /* Pause cả slider và progress bar khi hover vào slider */
    .testimonials-slider:hover .testimonial-slide {
        animation-play-state: paused;
    }

    .testimonials-slider:hover~.testimonials-slider-progress .testimonials-progress-bar {
        animation-play-state: paused;
    }

    /* Decorative Elements */
    .testimonial-decorations {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        pointer-events: none;
        overflow: hidden;
    }

    .deco-circle {
        position: absolute;
        border-radius: 50%;
        opacity: 0.05;
    }

    .deco-1 {
        width: 400px;
        height: 400px;
        background: var(--primary);
        top: -100px;
        left: -100px;
    }

    .deco-2 {
        width: 300px;
        height: 300px;
        background: var(--gold);
        bottom: -50px;
        right: -50px;
    }

    .deco-dots {
        position: absolute;
        top: 20%;
        right: 5%;
        width: 100px;
        height: 100px;
        background-image: radial-gradient(var(--primary) 2px, transparent 2px);
        background-size: 15px 15px;
        opacity: 0.3;
    }

    /* Responsive */
    @media (max-width: 992px) {
        .testimonials-slide-inner {
            grid-template-columns: 1fr;
            gap: 0;
        }

        .testimonials-slide-image {
            height: 350px;
            position: relative;
        }

        .testimonials-slide-image img {
            position: relative;
        }

        .testimonials-slide-content {
            padding: 20px;
        }

        .testimonials-slide-content blockquote {
            font-size: 14px;
        }

        .testimonials-slide-content blockquote p {
            font-size: 14px;
        }

        .testimonials-slider-track {
            min-height: 735px;
        }

        .testimonial-slide {
            position: absolute;
        }
    }

    @media (max-width: 576px) {
        .testimonials-section {
            padding: 60px 0 80px;
        }

        .testimonials-quote-icon {
            width: 35px;
            height: 32px;
        }

        .testimonials-quote-icon i {
            font-size: 17px;
        }

        .testimonials-author-details h4 {
            font-size: 18px;
        }
    }
</style>

<!-- Testimonials - Panolocal Style Slider -->
<section class="section testimonials-section">
    <div class="container">
        @php
        $title = $shortcode->title;
        $subtitle = $shortcode->subtitle;
        @endphp

        @if($title || $subtitle)
        <div class="section-header text-center mb-60">
            @if($subtitle)
            <h2 class="section-title" style="color:#A10908">{!! BaseHelper::clean($subtitle) !!}</h3>
            @endif
            @if($title)
            <h3 class="section-title">{!! BaseHelper::clean($title) !!}?</h3>
            @endif
        </div>
        @endif

        <div class="testimonials-slider">
            <div class="testimonials-slider-container">
                <div class="testimonials-slider-track">
                    @foreach($testimonials as $testimonial)
                    <div class="testimonial-slide">
                        <div class="testimonials-slide-inner">
                            <div class="testimonials-slide-image">
                                {{ RvMedia::image($testimonial->image, $testimonial->name, attributes: ['loading' =>
                                'lazy']) }}
                                <div class="testimonials-image-overlay"></div>
                            </div>
                            <div class="testimonials-slide-content">
                                {{-- <div class="testimonials-quote-icon">
                                    <i class="fa-solid fa-quote-left"></i>
                                </div> --}}
                                <div class="testimonials-rating">
                                    @php
                                    $stars = $testimonial->shortcode_stars ?? 5;
                                    @endphp
                                    @for ($i = 1; $i <= 5; $i++) @if ($i <=$stars) <i class="fa-solid fa-star"></i>
                                        @elseif ($i - 0.5 <= $stars) <i class="fa-solid fa-star-half-stroke"></i>
                                            @else
                                            <i class="fa-regular fa-star"></i>
                                            @endif
                                            @endfor
                                </div>
                                <blockquote>
                                    {!! BaseHelper::clean($testimonial->content) !!}
                                </blockquote>
                                <div class="testimonials-author-details">
                                    <h4>{{ $testimonial->name }}</h4>
                                    <span>{{ $testimonial->company }}</span>
                                </div>
                                @if(!empty($testimonial->subtitle))
                                <div class="testimonials-company-badge">
                                    <i class="fa-solid fa-building"></i>
                                    <span>{{ $testimonial->subtitle }}</span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Progress Bar -->
        {{-- <div class="testimonials-slider-progress">
            <div class="testimonials-progress-bar"></div>
        </div> --}}
    </div>

    <!-- Decorative Elements -->
    <div class="testimonial-decorations">
        <div class="deco-circle deco-1"></div>
        <div class="deco-circle deco-2"></div>
        <div class="deco-dots"></div>
    </div>
</section>