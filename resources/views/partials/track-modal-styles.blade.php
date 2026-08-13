{{-- Centered Lacak Pesanan modal --}}
<style>
.evomi-track-modal{position:fixed;inset:0;z-index:230}
.evomi-track-modal__backdrop{position:absolute;inset:0;background:rgba(15,23,42,.48);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px)}
.evomi-track-modal__frame{position:absolute;inset:0;display:flex;align-items:flex-end;justify-content:center;padding:0}
@media (min-width:640px){.evomi-track-modal__frame{align-items:center;padding:1.5rem}}
.evomi-track-modal__panel{position:relative;display:flex;flex-direction:column;width:100%;max-width:560px;max-height:min(92vh,760px);overflow:hidden;background:#fff;border-radius:28px 28px 0 0;box-shadow:0 24px 80px rgba(15,23,42,.28)}
@media (min-width:640px){.evomi-track-modal__panel{border-radius:28px;max-height:min(88vh,720px)}}
.evomi-track-modal__hero{position:relative;overflow:hidden;flex-shrink:0;padding:1.35rem 1.35rem 1.2rem;background:linear-gradient(145deg,#0d5f9e 0%,#1172ba 48%,#1a8ad4 100%);color:#fff}
.evomi-track-modal__hero-glow{position:absolute;right:-18%;top:-40%;width:220px;height:220px;border-radius:999px;background:rgba(255,255,255,.12);pointer-events:none}
.evomi-track-modal__icon{display:inline-flex;align-items:center;justify-content:center;width:42px;height:42px;border-radius:14px;background:rgba(255,255,255,.16);flex-shrink:0}
.evomi-track-modal__kicker{font-size:11px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.72)}
.evomi-track-modal__title{margin-top:.15rem;font-size:1.25rem;font-weight:700;letter-spacing:-.02em}
.evomi-track-modal__close{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:999px;color:#fff;background:rgba(255,255,255,.14);flex-shrink:0;border:0;cursor:pointer}
.evomi-track-modal__subtitle{margin-top:.85rem;font-size:13px;line-height:1.5;color:rgba(255,255,255,.88)}
.evomi-track-modal__search{display:flex;gap:.5rem;margin-top:1rem}
.evomi-track-modal__input{flex:1;min-width:0;height:46px;border:0;border-radius:14px;padding:0 .95rem;font-size:13px;font-weight:500;color:#0f172a;background:#fff;outline:none}
.evomi-track-modal__submit{flex-shrink:0;height:46px;min-width:96px;padding:0 1.1rem;border-radius:14px;border:0;font-size:13px;font-weight:700;color:#1172ba;background:#fff;cursor:pointer}
.evomi-track-modal__submit:disabled{opacity:.7;cursor:not-allowed}
.evomi-track-modal__body{flex:1;min-height:0;overflow-y:auto;padding:1.15rem 1.25rem 1.5rem;background:linear-gradient(180deg,#f8fafc 0%,#fff 28%)}
.evomi-track-modal__empty{padding:2.75rem 1rem;text-align:center}
.evomi-track-modal__empty-icon{display:inline-flex;align-items:center;justify-content:center;width:64px;height:64px;margin:0 auto 1rem;border-radius:22px;color:#1172ba;background:rgba(17,114,186,.08)}
</style>
