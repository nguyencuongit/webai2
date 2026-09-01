@php
  $sectionBackgroundColor = $shortcode->background_color ?: ($shortcode->icon_color ?: '#ffffff');
@endphp

<style>
  .pc-section {
    background: #fff;
  }

  .pc-kicker {
    font-size: 14px;
    color: #d08a6a;
  }

  .pc-title {
    font-size: clamp(34px, 3.6vw, 56px);
    font-weight: 800;
    letter-spacing: -0.02em;
    color: #0b1a27;
  }

  .pc-btn-all {
    border: 1px solid rgba(11, 26, 39, .22);
    border-radius: 0;
    padding: 12px 22px;
    background: #fff;
    color: #0b1a27;
    font-weight: 500;
  }

  .pc-btn-all:hover {
    background: #0b1a27;
    color: #fff;
  }

  .pc-card {
    position: relative;
    display: block;
    overflow: hidden;
    border-radius: 0;
    aspect-ratio: 3 / 4;
    background: #eee;
  }

  .pc-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transform: scale(1);
    transition: transform .7s cubic-bezier(.2, .9, .2, 1);
  }

  .pc-card::after {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(to top,
        rgba(0, 0, 0, .62) 0%,
        rgba(0, 0, 0, .25) 28%,
        rgba(0, 0, 0, 0) 55%);
    pointer-events: none;
  }

  .pc-card__content {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    padding: 18px 18px 16px;
    z-index: 1;
    text-align: center;
  }

  .pc-card__name {
    color: #fff;
    font-size: 26px;
    font-weight: 800;
    text-shadow: 0 2px 14px rgba(0, 0, 0, .35);
  }

  .pc-card__count {
    color: rgba(255, 255, 255, .92);
    font-size: 14px;
    font-weight: 600;
    margin-top: 4px;
    text-shadow: 0 2px 14px rgba(0, 0, 0, .35);
  }

  .pc-card:hover img {
    transform: scale(1.06);
  }

  .pc-kicker {
    font-size: 14px;
    color: #d08a6a;
  }

  .pc-title {
    font-size: clamp(34px, 3.6vw, 56px);
    font-weight: 800;
    letter-spacing: -0.02em;
    color: #0b1a27;
  }

  .pc-btn-all {
    border: 1px solid rgba(11, 26, 39, .22);
    border-radius: 6px !important;
    padding: 12px 22px;
    background: #fff;
    color: #0b1a27;
  }

  .pc-btn-all:hover {
    background: #0b1a27;
    color: #fff;
  }

  .pc-belt {
    overflow: hidden;
    cursor: grab;
    user-select: none;
    touch-action: pan-y;
  }

  .pc-belt.is-dragging {
    cursor: grabbing;
  }

  .pc-belt__track {
    display: flex;
    gap: 24px;
    width: max-content;
    padding: 6px 2px;
    will-change: transform;
    transform: translate3d(0, 0, 0);
  }

  .pc-card {
    position: relative;
    display: block;
    overflow: hidden;
    border-radius: 0;
    background: #eee;
    width: 320px;
    aspect-ratio: 3 / 4;
    flex: 0 0 auto;
  }

  @media (max-width: 576px) {
    .pc-card {
      width: 260px;
    }
  }

  .pc-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transform: scale(1);
    transition: transform .7s cubic-bezier(.2, .9, .2, 1);
  }

  .pc-card::after {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(to top,
        rgba(0, 0, 0, .62) 0%,
        rgba(0, 0, 0, .25) 28%,
        rgba(0, 0, 0, 0) 55%);
    pointer-events: none;
  }

  .pc-card__content {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    padding: 18px 18px 16px;
    z-index: 1;
    text-align: center;
  }

  .pc-card__name {
    color: #fff;
    font-size: 26px;
    font-weight: 800;
    text-shadow: 0 2px 14px rgba(0, 0, 0, .35);
  }

  .pc-card__count {
    color: rgba(255, 255, 255, .92);
    font-size: 14px;
    font-weight: 600;
    margin-top: 4px;
    text-shadow: 0 2px 14px rgba(0, 0, 0, .35);
  }

  .pc-card:hover img {
    transform: scale(1.06);
  }

  /* Overlay hover */
  .pc-card__overlay {
    position: absolute;
    inset: 0;
    display: block;
    background: rgba(0, 0, 0, .42);
    opacity: 0;
    visibility: hidden;
    transition: opacity .25s ease, visibility .25s ease;
    z-index: 2;
    pointer-events: none;
  }

  .pc-card__btn {
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    z-index: 3;

    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 12px 18px;
    border: 1px solid rgba(255, 255, 255, .9);
    color: #fff;
    background: rgba(255, 255, 255, .10);
    backdrop-filter: blur(8px);
    text-decoration: none;
    font-weight: 700;
    letter-spacing: .02em;

    pointer-events: auto;
  }

  .pc-card__btn:hover {
    background: #fff;
    color: #0b1a27;
  }

  .pc-card:hover .pc-card__overlay,
  .pc-card:hover .pc-card__btn {
    opacity: 1;
    visibility: visible;
    /* border-radius: 30px; */
  }

  .pc-card__btn {
    opacity: 0;
    visibility: hidden;
    transition: opacity .25s ease, visibility .25s ease;
    border-radius: 30px;

  }

  .pc-card::after {
    z-index: 1;
  }

  .pc-card__content {
    z-index: 1;
  }

  .pc-card__overlay,
  .pc-card__content,
  .pc-card img {
    pointer-events: none;
  }

  .pc-card__btn {
    pointer-events: auto;
    touch-action: manipulation;
  }

  .pc-card img {
    -webkit-user-drag: none;
    user-drag: none;
  }

  /* Hiệu ứng ảnh nghiêng - Tilted Image Effect */
  .pc-card {
    -webkit-clip-path: polygon(94.239% 100%, 5.761% 100%, 5.761% 100%,
        4.826% 99.95%, 3.94% 99.803%, 3.113% 99.569%,
        2.358% 99.256%, 1.687% 98.87%, 1.111% 98.421%,
        0.643% 97.915%, 0.294% 97.362%, 0.075% 96.768%,
        0 96.142%, 0 3.858%, 0 3.858%,
        0.087% 3.185%, 0.338% 2.552%, 0.737% 1.968%,
        1.269% 1.442%, 1.92% 0.984%, 2.672% 0.602%,
        3.512% 0.306%, 4.423% 0.105%, 5.391% 0.008%,
        6.4% 0.024%, 94.879% 6.625%, 94.879% 6.625%,
        95.731% 6.732%, 96.532% 6.919%, 97.272% 7.178%,
        97.942% 7.503%, 98.533% 7.887%, 99.038% 8.323%,
        99.445% 8.805%, 99.747% 9.326%, 99.935% 9.88%,
        100% 10.459%, 100% 96.142%, 100% 96.142%,
        99.925% 96.768%, 99.706% 97.362%, 99.357% 97.915%,
        98.889% 98.421%, 98.313% 98.87%, 97.642% 99.256%,
        96.887% 99.569%, 96.06% 99.803%, 95.174% 99.95%,
        94.239% 100%);
    clip-path: polygon(94.239% 100%, 5.761% 100%, 5.761% 100%,
        4.826% 99.95%, 3.94% 99.803%, 3.113% 99.569%,
        2.358% 99.256%, 1.687% 98.87%, 1.111% 98.421%,
        0.643% 97.915%, 0.294% 97.362%, 0.075% 96.768%,
        0 96.142%, 0 3.858%, 0 3.858%,
        0.087% 3.185%, 0.338% 2.552%, 0.737% 1.968%,
        1.269% 1.442%, 1.92% 0.984%, 2.672% 0.602%,
        3.512% 0.306%, 4.423% 0.105%, 5.391% 0.008%,
        6.4% 0.024%, 94.879% 6.625%, 94.879% 6.625%,
        95.731% 6.732%, 96.532% 6.919%, 97.272% 7.178%,
        97.942% 7.503%, 98.533% 7.887%, 99.038% 8.323%,
        99.445% 8.805%, 99.747% 9.326%, 99.935% 9.88%,
        100% 10.459%, 100% 96.142%, 100% 96.142%,
        99.925% 96.768%, 99.706% 97.362%, 99.357% 97.915%,
        98.889% 98.421%, 98.313% 98.87%, 97.642% 99.256%,
        96.887% 99.569%, 96.06% 99.803%, 95.174% 99.95%,
        94.239% 100%);
  }

  /* Card chẵn - góc xéo ngược lại */
  .pc-card:nth-child(2n) {
    -webkit-clip-path: polygon(5.761% 100%, 94.239% 100%, 94.239% 100%,
        95.174% 99.95%, 96.06% 99.803%, 96.887% 99.569%,
        97.642% 99.256%, 98.313% 98.87%, 98.889% 98.421%,
        99.357% 97.915%, 99.706% 97.362%, 99.925% 96.768%,
        100% 96.142%, 100% 3.858%, 100% 3.858%,
        99.913% 3.185%, 99.662% 2.552%, 99.263% 1.968%,
        98.731% 1.442%, 98.08% 0.984%, 97.328% 0.602%,
        96.488% 0.306%, 95.577% 0.105%, 94.609% 0.008%,
        93.6% 0.024%, 5.121% 6.625%, 5.121% 6.625%,
        4.269% 6.732%, 3.468% 6.919%, 2.728% 7.178%,
        2.058% 7.503%, 1.467% 7.887%, 0.962% 8.323%,
        0.555% 8.805%, 0.253% 9.326%, 0.065% 9.88%,
        0 10.459%, 0 96.142%, 0 96.142%,
        0.075% 96.768%, 0.294% 97.362%, 0.643% 97.915%,
        1.111% 98.421%, 1.687% 98.87%, 2.358% 99.256%,
        3.113% 99.569%, 3.94% 99.803%, 4.826% 99.95%,
        5.761% 100%);
    clip-path: polygon(5.761% 100%, 94.239% 100%, 94.239% 100%,
        95.174% 99.95%, 96.06% 99.803%, 96.887% 99.569%,
        97.642% 99.256%, 98.313% 98.87%, 98.889% 98.421%,
        99.357% 97.915%, 99.706% 97.362%, 99.925% 96.768%,
        100% 96.142%, 100% 3.858%, 100% 3.858%,
        99.913% 3.185%, 99.662% 2.552%, 99.263% 1.968%,
        98.731% 1.442%, 98.08% 0.984%, 97.328% 0.602%,
        96.488% 0.306%, 95.577% 0.105%, 94.609% 0.008%,
        93.6% 0.024%, 5.121% 6.625%, 5.121% 6.625%,
        4.269% 6.732%, 3.468% 6.919%, 2.728% 7.178%,
        2.058% 7.503%, 1.467% 7.887%, 0.962% 8.323%,
        0.555% 8.805%, 0.253% 9.326%, 0.065% 9.88%,
        0 10.459%, 0 96.142%, 0 96.142%,
        0.075% 96.768%, 0.294% 97.362%, 0.643% 97.915%,
        1.111% 98.421%, 1.687% 98.87%, 2.358% 99.256%,
        3.113% 99.569%, 3.94% 99.803%, 4.826% 99.95%,
        5.761% 100%);
  }
</style>

<section class="pc-section py-4 py-lg-5" style="background-color: {{ $sectionBackgroundColor }};">
  <div class="container">
    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
      <div>
        <div class="tp-section-title-pre-3">{{$shortcode->kicker}}</div>
        <h2 class="section-title tp-section-title-3">{{$shortcode->heading}}</h2>
      </div>

      <a href="/products" class="btn pc-btn-all">Mua tất cả sản phẩm →</a>
    </div>

    <!-- BELT -->
    <div class="pc-belt mt-3 mt-lg-4" id="pcBelt">
      <div class="pc-belt__track" id="pcBeltTrack">
        @foreach($categories as $category)
        <div class="pc-card">
          {{ RvMedia::image($category->image, $category->name) }}

          <div class="pc-card__overlay"></div>

          <a class="pc-card__btn" href="{{ $category->url }}">Chi tiết</a>

          <div class="pc-card__content">
            <div class="pc-card__name">{{ $category->name }}</div>
          </div>
        </div>
        @endforeach

      </div>
    </div>
  </div>
</section>


<script>
  (() => {
  const belt  = document.getElementById('pcBelt');
  const track = document.getElementById('pcBeltTrack');
  if (!belt || !track) return;

  const originals = Array.from(track.children);
  const frag = document.createDocumentFragment();
  originals.forEach(node => frag.appendChild(node.cloneNode(true)));
  track.appendChild(frag);

  let loopWidth = 0;
  function calcLoopWidth(){
    loopWidth = originals.reduce((sum, el) => sum + el.getBoundingClientRect().width, 0);
    const gap = parseFloat(getComputedStyle(track).gap || 0);
    loopWidth += gap * (originals.length);
  }

  const imgs = track.querySelectorAll('img');
  Promise.all(Array.from(imgs).map(img => img.decode?.().catch(()=>null))).finally(() => {
    calcLoopWidth();
  });

  window.addEventListener('resize', () => {
    calcLoopWidth();
  });

  // Motion
  const SPEED = 80; 
  let x = 0;
  let lastTs = 0;
  let isDown = false;
  let startX = 0;
  let startTranslate = 0;
  let hover = false;
  let pauseUntil = 0;

  function normalize(){
    if (!loopWidth) return;
    if (x <= -loopWidth) x += loopWidth;
    if (x > 0) x -= loopWidth;
  }

  function render(){
    track.style.transform = `translate3d(${x}px,0,0)`;
  }

  function tick(ts){
    if (!lastTs) lastTs = ts;
    const dt = (ts - lastTs) / 1000;
    lastTs = ts;

    const paused = isDown || hover || Date.now() < pauseUntil;
    if (!paused) {
      x -= SPEED * dt;      
      normalize();
      render();
    }
    requestAnimationFrame(tick);
  }

  belt.addEventListener('mouseenter', () => hover = true);
  belt.addEventListener('mouseleave', () => hover = false);

 belt.addEventListener('pointerdown', (e) => {
  if (e.target.closest('.pc-card__btn')) return;

  isDown = true;
  belt.classList.add('is-dragging');
  belt.setPointerCapture(e.pointerId);
  startX = e.clientX;
  startTranslate = x;
});
document.addEventListener('pointerdown', (e) => {
  if (e.target.closest('.pc-card__btn')) e.stopPropagation();
}, true);

  belt.addEventListener('pointermove', (e) => {
    if (!isDown) return;
    const dx = e.clientX - startX;
    x = startTranslate + dx;   
    normalize();
    render();
  });

  function endDrag(){
    if (!isDown) return;
    isDown = false;
    belt.classList.remove('is-dragging');
    pauseUntil = Date.now() + 700; 
  }
  belt.addEventListener('pointerup', endDrag);
  belt.addEventListener('pointercancel', endDrag);

  requestAnimationFrame(tick);
})();
</script>
