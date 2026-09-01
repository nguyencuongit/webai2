<!-- Keen Slider (CDN) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/keen-slider@latest/keen-slider.min.css">
<script src="https://cdn.jsdelivr.net/npm/keen-slider@latest/keen-slider.js"></script>

<style>
  .tn-wrap{ padding: 10px 0; background:#fff; }

  .tn-viewport{
    position: relative;
    /* overflow: hidden; */
    width: 100%;
  }

  /* keen container */
  .tn-keen.keen-slider{
    padding: 18px 0;
  }

.tn-slide{
  opacity: .7;
  transition: opacity .35s ease;
}
.tn-slide.is-active{
  opacity: 1;
}

.tn-item{
  position: relative;
  overflow: visible; 
  padding: 30px;
  width: 100%;
  padding-bottom: 40px;
}

/* scale chuyển xuống card */
.tn-card{
  border-radius: 9px;
  /* overflow: hidden; */
  box-shadow: none;
  background: #f3f3f3;
  aspect-ratio: 16 / 6.5;
  transform: scale(.98) translateZ(0);
  transition: transform .35s ease;
  backface-visibility: hidden;
  will-change: transform;
  contain: paint;
}

.tn-slide.is-active .tn-card{
  transform: scale(1) translateZ(0);
  box-shadow: 0px 10px 30px rgba(0, 0, 0, 1);
}
  .tn-card img{
  border-radius: 9px;

    width: 100%;
    height: 100%;
   /*  object-fit: cover; */
    display: block;
    transform: translateZ(0);
    backface-visibility: hidden;
    will-change: transform;
  }

  .tn-nav{
    position:absolute;
    top: 50%;
    transform: translateY(-50%);
    z-index: 5;
    border: 0;
    width: 50px;
    height: 50px;
    border-radius: 999px;
    background: rgba(255,255,255,.9);
    box-shadow: 0 10px 25px rgba(0,0,0,.18);
    cursor: pointer;
    display: grid;
    place-items: center;
    font-size: 24px;
    line-height: 1;
    user-select: none;
  }
  .tn-nav.prev{ left: 10px; }
  .tn-nav.next{ right: 10px; }
.keen-slider:not([data-keen-slider-disabled]) {
  display: flex;
  overflow: visible !important;
  touch-action: pan-y;
}
.keen-slider:not([data-keen-slider-disabled]) .keen-slider__slide {
  position: relative;
  width: 100%;
  min-height: 100%;
}
 

  .bowknot{
    position: absolute;
    top:0;
    left: 50%;
    right: 0;
    display: grid;
    place-items: start center;
    z-index:10;
    pointer-events: none;
  }
  .bowknot img{
    width:20%;
  }
  .silk_ribbon{
    position: absolute;
    top:10px;
    left: 0;
    z-index:9;
    pointer-events: none;
  }
 
  .silk_ribbon_left img{
     width:40%;
    margin-left: auto;
  }
  .silk_ribbon_right img{
    width:40%;
  }

.tn-card{ contain: unset; }

.tn-slide .bowknot{
  opacity: 0;
  transform: translateX(-50%) translateY(-10px) scale(.85);
  transition: opacity .35s ease, transform .55s cubic-bezier(.2,.9,.2,1);
}
.tn-slide.is-active .bowknot{
  opacity: 1;
  transform: translateX(-50%) translateY(0) scale(1);
  transition-delay: .25s;   /* <-- trễ 0.25s */
}

.tn-slide .silk_ribbon{
  opacity: 0;
  transition: opacity .2s ease;
  pointer-events: none;
}

.silk_ribbon{
  width: 100%;
  display: flex;
  justify-content: center;
  gap: 14px;
}

.tn-slide .silk_ribbon_left,
.tn-slide .silk_ribbon_right{
  opacity: 0;
  transform: translateY(-6px) scaleX(0);
  transition: transform .70s cubic-bezier(.2,.9,.2,1), opacity .30s ease;
}

.tn-slide .silk_ribbon_left{ transform-origin: right center; }
.tn-slide .silk_ribbon_right{ transform-origin: left center; }

.tn-slide.is-active .silk_ribbon{
  opacity: 1;
}

.tn-slide.is-active .silk_ribbon_left,
.tn-slide.is-active .silk_ribbon_right{
  opacity: 1;
  transform: translateY(0) scaleX(1);
  transition-delay: .40s;  
}

 @media (max-width: 992px){
    .tn-nav.prev{ left: 8px; }
    .tn-nav.next{ right: 8px; }
    .tn-item{padding: 10px;}
    .silk_ribbon{top:5px;}
  }
  @media (max-width: 576px){
    .tn-nav{ width: 38px; height: 38px; font-size: 22px; }
    .tn-nav.prev{ left: 6px; }
    .tn-nav.next{ right: 6px; }
  }
</style>

<section class="tn-wrap" style="background:{{$shortcode->icon_color}}">
  <div class="tn-viewport" id="tnViewport">
    <button class="tn-nav prev" id="btnPrev" type="button" aria-label="Prev">‹</button>
    <button class="tn-nav next" id="btnNext" type="button" aria-label="Next">›</button>

    <div class="keen-slider tn-keen" id="tnKeen">

      @foreach($sliders as $slider)
        <div class="keen-slider__slide tn-slide">
           <div class="tn-item">
              <div class="bowknot">
                {{ RvMedia::image('banner-hoa/anh.png', 'bowknot') }}
              </div>
              <div class="silk_ribbon d-flex">
                <div class="silk_ribbon_left d-flex">
                  {{ RvMedia::image('banner-hoa/silk_ribbon_left.png', 'bowknot1') }}
                </div>
                <div class="silk_ribbon_right d-flex">
                  {{ RvMedia::image('banner-hoa/b.png', 'bowknot2') }}
                </div>
              </div>

              <div class="tn-card">
                {{ RvMedia::image($slider['image'], $slider['title']) }}
              </div>
            </div>
        </div>
      @endforeach
    </div>
  </div>
</section>

<script>
  const viewport = document.getElementById('tnViewport');
  const container = document.getElementById('tnKeen');
  const btnPrev = document.getElementById('btnPrev');
  const btnNext = document.getElementById('btnNext');

  const AUTOPLAY_MS = 6000;

  function setActive(slider) {
    const rel = slider.track.details.rel; // slide đang ở giữa (relative index)
    slider.slides.forEach((el, i) => el.classList.toggle('is-active', i === rel));
  }

  function autoplayPlugin(ms) {
    return (slider) => {
      let timeout;
      let mouseOver = false;

      function clear() {
        clearTimeout(timeout);
      }
      function next() {
        clear();
        if (mouseOver) return;
        timeout = setTimeout(() => slider.next(), ms);
      }

      slider.on("created", () => {
        viewport.addEventListener("mouseenter", () => { mouseOver = true; clear(); });
        viewport.addEventListener("mouseleave", () => { mouseOver = false; next(); });
        next();
      });
      slider.on("dragStarted", clear);
      slider.on("animationEnded", next);
      slider.on("updated", next);
    };
  }

  const slider = new KeenSlider(container, {
    loop: true,
    rubberband: false,
    renderMode: "performance",
    slides: {
      origin: "center",
      perView: 1.4,      // lộ 2 bên
      spacing: 22
    },
    breakpoints: {
      "(max-width: 992px)": {
        slides: { origin: "center", perView: 1.22, spacing: 18 }
      },
      "(max-width: 576px)": {
        slides: { origin: "center", perView: 1.08, spacing: 14 }
      }
    },
    created(s) { setActive(s); },
    slideChanged(s) { setActive(s); },
    updated(s) { setActive(s); },
  }, [autoplayPlugin(AUTOPLAY_MS)]);

  btnPrev.addEventListener('click', () => slider.prev());
  btnNext.addEventListener('click', () => slider.next());
</script>
