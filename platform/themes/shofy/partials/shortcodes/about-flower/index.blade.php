 <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Allura&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

  <style>
    :root{
      --border:#d6c7c3;
      --ink:#fffefe;
      --muted:#6e6e6e;
      --blue:#6d86ad;
      --soft:#faf7f4;
    }
    body{ 
    background:#f2f2f2; font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; }
    
    .banner{
      background:radial-gradient(
        ellipse at center,
        #d00303ff 0%,
        #b11111ff 40%,
        #950101ff 100%
      );
      border: 8px solid #fff;
      outline: 3px solid var(--border);
      padding: 26px;
    }

    .left-col{ position:relative; min-height:520px; }
    .logo-about img{
      width: 40%;
     }

    .pill{
      display:inline-block;
      font-size:12px;
      letter-spacing:.12em;
      padding:10px 14px;
      background: var(--blue);
      color:#fff;
      text-transform:uppercase;
    }

    .script{ font-family: Allura, cursive; font-size:30px; color:#7a96c0; }
    .headline{
      font-family: "Playfair Display", serif;
      font-weight:600;
      font-size:56px;
      line-height:1.02;
      letter-spacing:-.01em;
      color:var(--ink);
      width: 50%;
      margin: 20px 0;
    }

    .meta{ font-size:14px; color:#2b2b2b; }
    .meta small{ color:var(--muted); font-weight:500; }

    .btn-join{
      background: var(--blue);
      color:#fff;
      border:0;
      padding: 12px 18px;
      font-weight:600;
      letter-spacing:.12em;
      text-transform:uppercase;
      border-radius:0;
    }
    .btn-join:hover {
      background: white ;
    }
    .confetti{
      
      position:absolute; inset:0; pointer-events:none; opacity:.7;
      background:
        radial-gradient(circle at 20% 30%, #f2c94c 0 2px, transparent 3px),
        radial-gradient(circle at 35% 50%, #f2c94c 0 2px, transparent 3px),
        radial-gradient(circle at 55% 25%, #f2c94c 0 2px, transparent 3px),
        radial-gradient(circle at 72% 45%, #f2c94c 0 2px, transparent 3px),
        radial-gradient(circle at 80% 18%, #f2c94c 0 2px, transparent 3px);
      background-repeat:no-repeat;
    }
    .banner-main{
      position: absolute;
      top: 0;
      left: 0;
      z-index: 1;
    }

    .deco{
    position:absolute;
    pointer-events:none;
    opacity:.95;
    display:block;
    }
    .deco.top{ top:6px; right:28px; width:120px; transform: rotate(8deg); }
    .deco.bottom{ left:16px; bottom:10px; width:190px; transform: rotate(-8deg); }

    .tile{
      position:relative;
      background: var(--soft);
      aspect-ratio: 4 / 3;
      overflow:hidden;
      box-shadow: 0 10px 24px rgba(0,0,0,.08);
    }
    .tile img{
      width:100%; height:100%;
      object-fit:cover; display:block;
      transform: scale(1);
      transition: transform .55s cubic-bezier(.2,.9,.2,1);
    }
    .tile:hover img{ transform: scale(1.05); }

    .play-overlay{
      position:absolute; inset:0;
      display:grid; place-items:center;
    }
    .play-btn{
      width:86px; height:62px;
      background: rgba(255,255,255,.92);
      border:1px solid rgba(0,0,0,.08);
      box-shadow: 0 14px 30px rgba(0,0,0,.14);
      border-radius:12px;
      display:grid; place-items:center;
    }
    .play-btn:before{
      content:"";
      width:0;height:0;
      border-left: 16px solid rgba(0,0,0,.75);
      border-top: 10px solid transparent;
      border-bottom: 10px solid transparent;
      margin-left: 4px;
    }

    .icon-flower{
      position: absolute;
      z-index: 0;
      width: clamp(90px, 14vw, 170px);
    }
    .icon-flower img{
      width: 100%;
      height: auto;
      display: block;
    }
    .icon2{
      top:20%;
      left:-3%;
    }
    .icon1{
      bottom: 10%;
      left: 15%;
    }
    .icon3{
      width: 35%;
      bottom: 0%;
      right: -4%;
      /* transform: rotate(25deg); */
      transform-origin: center;
    }
    .icon4{
      width: 35%;
      top: -5%;
      right: 3%;
      transform: rotate(0deg);
      transform-origin: center;
    }

    @media (max-width: 992px){
      .left-col{ min-height: auto; }
      .headline{ font-size:46px; }
      .banner-main{
        position: relative;
        top: auto;
        left: auto;
        z-index: 2;
        }
      .icon2{
        top:30%;
      }
    }
    @media (max-width: 576px){
      .banner{ padding:16px; }
      .headline{ font-size:40px; }
    }
  </style>

  <div class="container py-5">
    <section class="banner">
      <div class="row g-4 align-items-stretch">

        <!-- LEFT -->
        <div class="col-12 col-lg-6">
          <div class="left-col p-2">
            <div class="confetti">
               <!-- Decorative SVGs -->
              @if($shortcode->icon1)
              <div class="icon-flower icon1">
                {{ RvMedia::image($shortcode->icon1, 'icon1') }}
              </div>
              @endif
              @if($shortcode->icon2)
              <div class="icon-flower icon2">
                {{ RvMedia::image($shortcode->icon2, 'icon2') }}
              </div>
              @endif
              @if($shortcode->icon3)
                <div class="icon-flower icon3">
                  {{ RvMedia::image($shortcode->icon3, 'icon3') }}
                </div>
              @endif
              @if($shortcode->icon4)
              <div class="icon-flower icon4">
                {{ RvMedia::image($shortcode->icon4, 'icon4') }}
              </div>
               @endif
              
            </div>
            <div class="banner-main">
              <div class="logo-about mb-3">
                {{ RvMedia::image($logo, 'logo') }}
              </div>

              <div class="script mt-2">{{$shortcode->title1}}</div>
              <h1 class="headline mb-3">
                {{$shortcode->title2}}
              </h1>
              <button class="btn btn-join">Mua ngay</button>
            </div>
           
          </div>
        </div>

        <!-- RIGHT (2x2 images) -->
        <div class="d-none d-lg-block col-lg-6">
          <div class="row g-3">

            <div class="col-6">
              <div class="tile">
                {{ RvMedia::image($shortcode->image1, 'image1') }}
              </div>
            </div>

            <div class="col-6">
              <div class="tile">
                {{ RvMedia::image($shortcode->image2, 'image2') }}
              </div>
            </div>

            <div class="col-6">
              <div class="tile">
                {{ RvMedia::image($shortcode->image3, 'image3') }}
              </div>
            </div>

            <div class="col-6">
              <div class="tile">
                {{ RvMedia::image($shortcode->image4, 'image4') }}
              </div>
            </div>

          </div>
        </div>

      </div>
    </section>
  </div>