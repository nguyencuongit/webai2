<style>
.customer-reviews {
    padding: 80px 0;
    background: rgb(255 255 255);
    position: relative;
    overflow: hidden;
}

.customer-reviews .section-title {
    text-align: center;
    font-size: 34px;
    margin-bottom: 50px;
    font-weight: 600;
}

.reviews-wrapper {
    padding: 10px 0 40px;
}

.review-item {
    background: #fff;
    border-radius: 18px;
    padding: 30px;
    text-align: center;
    box-shadow: 0 20px 40px rgba(0,0,0,.06);
    opacity: 0;
    transform: translateY(40px);
    transition: opacity .6s ease, transform .6s ease;
}

.review-item.animated {
    opacity: 1;
    transform: translateY(0);
}

.review-item .avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    margin-bottom: 12px;
    object-fit: cover;
}

.review-item h4 {
    margin: 8px 0 6px;
    font-size: 18px;
}

.review-item p {
    font-size: 15px;
    line-height: 1.6;
    color: #555;
}

.rating {
    margin-bottom: 12px;
}
.rating span {
    color: #ddd;
    font-size: 16px;
}
.rating .active {
    color: #d4a017;
}

/* Không khí Tết nhẹ */
.tet-theme::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image:
        radial-gradient(circle at 10% 20%, rgba(218,41,28,.08), transparent 40%),
        radial-gradient(circle at 90% 10%, rgba(255,193,7,.08), transparent 40%);
    pointer-events: none;
}

/* Canvas pháo hoa */
.fireworks-canvas {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 1;
}

/* Fix Swiper */
.swiper-slide {
    height: auto;
}
</style>

<section class="customer-reviews slider tet-theme" data-animate>
    <h2 class="section-title">
        {{ __('Khách hàng nói gì về chúng tôi') }}
    </h2>

    <div class="reviews-wrapper swiper">
        <div class="swiper-wrapper">
            @foreach($reviews as $review)
                <div class="swiper-slide review-item">
                    <img class="avatar"
                         src="{{ $review->user->avatar_url ?? theme_asset('images/avatar.png') }}"
                         alt="{{ $review->user->name ?? __('Khách hàng') }}">

                    <h4>{{ $review->user->name ?? __('Khách hàng') }}</h4>

                    <div class="rating">
                        @for($i = 1; $i <= 5; $i++)
                            <span class="{{ $i <= $review->star ? 'active' : '' }}">★</span>
                        @endfor
                    </div>

                    <p>{{ $review->comment }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <canvas class="fireworks-canvas"></canvas>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {

    /* =======================
       INIT SWIPER
    ======================= */
    if (typeof Swiper !== 'undefined') {
        new Swiper('.customer-reviews .swiper', {
            slidesPerView: 3,
            spaceBetween: 30,
            loop: true,
            autoplay: {
                delay: 4000,
disableOnInteraction: false
            },
            breakpoints: {
                0: { slidesPerView: 1 },
                768: { slidesPerView: 2 },
                1024: { slidesPerView: 3 }
            }
        });
    }

    /* =======================
       EASING
    ======================= */
    function easeOutCubic(t) {
        return 1 - Math.pow(1 - t, 3);
    }

    /* =======================
       FIREWORK CLASSES
    ======================= */
    class Firework {
        constructor(x, y, targetY, color) {
            this.x = x;
            this.startY = y;
            this.targetY = targetY;
            this.color = color;
            this.progress = 0;

            this.cp1 = { x: x + Math.random() * 100 - 50, y: y - 150 };
            this.cp2 = { x: x + Math.random() * 100 - 50, y: targetY + 80 };
        }

        update() {
            this.progress += 0.025;
            return this.progress < 1;
        }

        draw(ctx) {
            const t = easeOutCubic(this.progress);

            const x =
                Math.pow(1 - t, 3) * this.x +
                3 * Math.pow(1 - t, 2) * t * this.cp1.x +
                3 * (1 - t) * Math.pow(t, 2) * this.cp2.x +
                Math.pow(t, 3) * this.x;

            const y =
                Math.pow(1 - t, 3) * this.startY +
                3 * Math.pow(1 - t, 2) * t * this.cp1.y +
                3 * (1 - t) * Math.pow(t, 2) * this.cp2.y +
                Math.pow(t, 3) * this.targetY;

            ctx.beginPath();
            ctx.arc(x, y, 2, 0, Math.PI * 2);
            ctx.fillStyle = this.color;
            ctx.fill();
        }
    }

    class Particle {
        constructor(x, y, color) {
            const angle = Math.random() * Math.PI * 2;
            const speed = Math.random() * 4 + 1;

            this.x = x;
            this.y = y;
            this.vx = Math.cos(angle) * speed;
            this.vy = Math.sin(angle) * speed;
            this.alpha = 1;
            this.color = color;
        }

        update() {
            this.x += this.vx;
            this.y += this.vy;
            this.vy += 0.05;
            this.alpha -= 0.02;
            return this.alpha > 0;
        }

        draw(ctx) {
            ctx.globalAlpha = this.alpha;
            ctx.beginPath();
            ctx.arc(this.x, this.y, 2, 0, Math.PI * 2);
            ctx.fillStyle = this.color;
            ctx.fill();
            ctx.globalAlpha = 1;
        }
    }

    function launchFireworks(section) {
        const canvas = section.querySelector('.fireworks-canvas');
        const ctx = canvas.getContext('2d');

        canvas.width = section.offsetWidth;
        canvas.height = section.offsetHeight;

        let fireworks = [];
        let particles = [];

        const colors = [
            'rgba(218,41,28,0.9)',
            'rgba(255,193,7,0.9)',
            'rgba(255,152,0,0.9)'
        ];

        for (let i = 0; i < 3; i++) {
            fireworks.push(
new Firework(
                    canvas.width * (0.3 + Math.random() * 0.4),
                    canvas.height,
                    canvas.height * (0.25 + Math.random() * 0.2),
                    colors[i % colors.length]
                )
            );
        }

        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            fireworks = fireworks.filter(fw => {
                fw.draw(ctx);
                if (!fw.update()) {
                    for (let i = 0; i < 30; i++) {
                        particles.push(new Particle(fw.x, fw.targetY, fw.color));
                    }
                    return false;
                }
                return true;
            });

            particles = particles.filter(p => {
                p.draw(ctx);
                return p.update();
            });

            if (fireworks.length || particles.length) {
                requestAnimationFrame(animate);
            }
        }

        animate();
    }

    /* =======================
       SCROLL TRIGGER
    ======================= */
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;

            entry.target.querySelectorAll('.review-item')
                .forEach((el, i) => {
                    setTimeout(() => el.classList.add('animated'), i * 120);
                });

            launchFireworks(entry.target);
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.35 });

    document.querySelectorAll('.customer-reviews[data-animate]')
        .forEach(el => observer.observe(el));
});
</script>