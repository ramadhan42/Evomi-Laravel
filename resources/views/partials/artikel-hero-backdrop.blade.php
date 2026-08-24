{{-- Hero uses navbar/footer blue, with the same soft atmosphere as localhost --}}
@php($artikelAccent = $themeAccent ?? '#1172BA')
<style>
.artikel-hero-backdrop{position:absolute;inset:0;overflow:hidden;pointer-events:none}
.artikel-hero-backdrop__fill{
  position:absolute;inset:0;
  background: {{ $artikelAccent }};
}
.artikel-hero-backdrop__glow{
  position:absolute;inset:0;
  background:
    radial-gradient(ellipse 80% 70% at 8% 0%, rgba(255,255,255,.16), transparent 58%),
    radial-gradient(ellipse 60% 50% at 96% 8%, rgba(255,255,255,.12), transparent 52%),
    radial-gradient(ellipse 70% 45% at 50% 100%, rgba(0,0,0,.08), transparent 62%);
}
.artikel-hero-backdrop__grain{
  position:absolute;inset:0;opacity:.045;
  background-image:radial-gradient(circle at 1px 1px, rgba(255,255,255,.9) 1px, transparent 0);
  background-size:18px 18px;
}
.artikel-hero-backdrop__orb{
  position:absolute;border-radius:999px;pointer-events:none;
  border:1px solid rgba(255,255,255,.12);
  background:rgba(255,255,255,.08);
  filter:blur(18px);
}
.artikel-hero-backdrop__orb--a{top:-4.5rem;right:-2.5rem;width:14rem;height:14rem}
.artikel-hero-backdrop__orb--b{top:38%;left:-3.5rem;width:10.5rem;height:10.5rem;filter:blur(22px)}
.artikel-hero-backdrop__orb--c{
  bottom:1.5rem;right:18%;width:6.5rem;height:6.5rem;border-radius:1.75rem;
  transform:rotate(12deg);filter:blur(14px);
}
.artikel-hero-backdrop__dot{
  position:absolute;border-radius:999px;background:rgba(255,255,255,.55);
  box-shadow:0 0 12px rgba(255,255,255,.35);
}
.artikel-hero-backdrop__dot--d{bottom:4rem;left:12%;width:.55rem;height:.55rem}
.artikel-hero-backdrop__dot--e{top:6rem;left:42%;width:.4rem;height:.4rem;opacity:.7}
</style>
<div class="artikel-hero-backdrop" aria-hidden="true">
    <div class="artikel-hero-backdrop__fill"></div>
    <div class="artikel-hero-backdrop__glow"></div>
    <div class="artikel-hero-backdrop__grain"></div>
    <div class="artikel-hero-backdrop__orb artikel-hero-backdrop__orb--a artikel-orb artikel-orb-a"></div>
    <div class="artikel-hero-backdrop__orb artikel-hero-backdrop__orb--b artikel-orb artikel-orb-b"></div>
    <div class="artikel-hero-backdrop__orb artikel-hero-backdrop__orb--c artikel-orb artikel-orb-c"></div>
    <div class="artikel-hero-backdrop__dot artikel-hero-backdrop__dot--d artikel-orb artikel-orb-d"></div>
    <div class="artikel-hero-backdrop__dot artikel-hero-backdrop__dot--e artikel-orb artikel-orb-e"></div>
</div>
