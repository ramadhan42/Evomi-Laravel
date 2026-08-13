{{-- Shared square help modals (FAQ + Kontak) --}}
<style>
.evomi-help-modal{position:fixed;inset:0;z-index:230}
.evomi-help-modal__backdrop{position:absolute;inset:0;background:rgba(15,23,42,.48);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px)}
.evomi-help-modal__frame{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:1rem}
.evomi-help-modal__panel{
  position:relative;
  display:flex;
  flex-direction:column;
  width:min(92vw,92vh,560px);
  height:min(92vw,92vh,560px);
  max-width:560px;
  max-height:560px;
  aspect-ratio:1/1;
  overflow:hidden;
  background:#fff;
  border-radius:24px;
  box-shadow:0 24px 80px rgba(15,23,42,.28);
}
.evomi-help-modal__header{
  flex-shrink:0;
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:.75rem;
  padding:1.15rem 1.2rem 1rem;
  background:linear-gradient(145deg,#0d5f9e 0%,#1172ba 48%,#1a8ad4 100%);
  color:#fff;
}
.evomi-help-modal__kicker{font-size:11px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.72)}
.evomi-help-modal__title{margin-top:.15rem;font-size:1.15rem;font-weight:700;letter-spacing:-.02em;line-height:1.25}
.evomi-help-modal__subtitle{margin-top:.45rem;font-size:12px;line-height:1.45;color:rgba(255,255,255,.88)}
.evomi-help-modal__close{
  display:inline-flex;align-items:center;justify-content:center;
  width:34px;height:34px;border-radius:999px;border:0;color:#fff;
  background:rgba(255,255,255,.14);flex-shrink:0;cursor:pointer;
}
.evomi-help-modal__body{flex:1;min-height:0;overflow-y:auto;overscroll-behavior:contain;padding:1rem 1.15rem 1.25rem;background:linear-gradient(180deg,#f8fafc 0%,#fff 24%)}
.evomi-help-modal__search{
  width:100%;height:44px;border:1px solid #e5e7eb;border-radius:999px;
  padding:0 1rem 0 2.6rem;font-size:13px;outline:none;background:#fff;color:#0f172a;
}
.evomi-help-modal__search:focus{border-color:#1172ba;box-shadow:0 0 0 3px rgba(17,114,186,.12)}
.evomi-help-modal__input,.evomi-help-modal__textarea{
  width:100%;border:1px solid #e5e7eb;background:#fff;color:#0f172a;outline:none;font-size:13px;
}
.evomi-help-modal__input{height:44px;border-radius:999px;padding:0 1rem}
.evomi-help-modal__textarea{min-height:110px;border-radius:1.1rem;padding:.85rem 1rem;resize:none}
.evomi-help-modal__input:focus,.evomi-help-modal__textarea:focus{border-color:#1172ba;box-shadow:0 0 0 3px rgba(17,114,186,.12)}
.evomi-help-modal__submit{
  width:100%;height:48px;border:0;border-radius:999px;background:#1172ba;color:#fff;
  font-weight:700;font-size:14px;cursor:pointer;
}
.evomi-help-modal__submit:disabled{opacity:.7;cursor:not-allowed}
.evomi-help-modal__info{display:flex;align-items:flex-start;gap:.75rem;padding:.85rem;border:1px solid #f1f5f9;border-radius:1rem;background:#fff}
.evomi-help-modal__info-icon{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:12px;background:#e8f4fc;color:#1172ba;flex-shrink:0}
</style>
