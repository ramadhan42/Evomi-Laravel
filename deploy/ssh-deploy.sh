#!/usr/bin/env bash
#
# Menerapkan satu rilis GitHub ke satu domain di server, lewat SSH.
#
#   bash ssh-deploy.sh <sha> <domain>
#
# Skrip ini menggantikan evomi-deploy.php yang dipanggil lewat HTTP: tidak ada
# berkas yang perlu diunggah ke docroot, tidak ada endpoint deploy yang sempat
# menganggur, dan tidak ada ketergantungan pada FTP yang beberapa kali timeout.
#
# Yang disalin hanya subpath di bawah ini; folder tujuan tidak pernah dihapus,
# jadi berkas lama tetap ada sebagai jaring pengaman.
set -euo pipefail

SHA="${1:?sha commit wajib}"
DOMAIN="${2:?nama domain wajib}"
REPO="${DEPLOY_REPO:-ramadhan42/Evomi-Laravel}"

# CLI bawaan server masih PHP 8.3, sedangkan vendor menuntut >= 8.4.1.
PHP="${PHP_BIN:-/opt/alt/php84/usr/bin/php}"
[ -x "$PHP" ] || PHP=php

BASE="$HOME/domains/$DOMAIN"
LARAVEL="$BASE/laravel"
DOC="$BASE/public_html"

[ -d "$LARAVEL" ] || { echo "GAGAL: $LARAVEL tidak ada"; exit 1; }
[ -d "$DOC" ] || { echo "GAGAL: $DOC tidak ada"; exit 1; }

WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT

echo "== rilis $SHA -> $DOMAIN"
curl -sSLf "https://codeload.github.com/$REPO/tar.gz/$SHA" -o "$WORK/src.tar.gz"
tar -xzf "$WORK/src.tar.gz" -C "$WORK"
SRC="$(find "$WORK" -maxdepth 1 -type d -name '*-*' | head -1)"
[ -n "$SRC" ] || { echo "GAGAL: arsip tidak berisi folder sumber"; exit 1; }

copy() {
    if [ ! -e "$1" ]; then
        echo "   lewati (tidak ada di repo): ${1#"$SRC"/}"
        return 0
    fi

    mkdir -p "$2"
    cp -r "$1/." "$2/"
    echo "   $(printf '%-24s' "${1#"$SRC"/}") -> ${2#"$HOME"/}"
}

echo "== salin berkas"
copy "$SRC/app"                "$LARAVEL/app"
cp   "$SRC/bootstrap/app.php"  "$LARAVEL/bootstrap/app.php"
copy "$SRC/config"             "$LARAVEL/config"
copy "$SRC/database"           "$LARAVEL/database"
copy "$SRC/routes"             "$LARAVEL/routes"
copy "$SRC/resources"          "$LARAVEL/resources"
# Aset build dibutuhkan Laravel (manifest) dan docroot (URL publik).
copy "$SRC/public/build"       "$LARAVEL/public/build"
copy "$SRC/public/build"       "$DOC/build"
# robots.txt dilayani langsung dari docroot dan bukan folder, jadi copy() di
# atas melewatinya - tanpa dua baris ini perubahannya tidak pernah sampai.
cp   "$SRC/public/robots.txt"  "$DOC/robots.txt"
cp   "$SRC/public/robots.txt"  "$LARAVEL/public/robots.txt"
copy "$SRC/public/src"         "$LARAVEL/public/src"
copy "$SRC/public/src"         "$DOC/src"
copy "$SRC/public/videos"      "$LARAVEL/public/videos"
copy "$SRC/public/videos"      "$DOC/videos"
# Media unggahan yang ikut repo.
copy "$SRC/storage/app/public" "$LARAVEL/storage/app/public"

cd "$LARAVEL"

echo "== bersihkan cache"
for cmd in view:clear route:clear config:clear cache:clear; do
    "$PHP" artisan "$cmd" >/dev/null && echo "   $cmd"
done
rm -f storage/framework/views/*.php 2>/dev/null || true

# Unggahan tidak ikut repo, jadi tiap server mengonversi berkasnya sendiri.
# Wajib sebelum migrate: migrasi hanya mengubah path bila .webp sudah ada.
echo "== konversi gambar unggahan"
"$PHP" artisan evomi:images-to-webp 2>&1 | tail -2 || echo "   (dilewati)"

echo "== migrate"
"$PHP" artisan migrate --force 2>&1 | tail -8

echo "== bangun cache"
cache_ok=1
for cmd in config:cache route:cache view:cache; do
    if "$PHP" artisan "$cmd" >/dev/null; then
        echo "   $cmd"
    else
        cache_ok=0
        break
    fi
done

# Cache setengah jadi bisa mengunci situs pada konfigurasi lama; buang saja
# supaya Laravel kembali membaca .env apa adanya.
if [ "$cache_ok" -eq 0 ]; then
    echo "   cache gagal - dibuang lagi"
    for cmd in config:clear route:clear view:clear; do "$PHP" artisan "$cmd" >/dev/null || true; done
    exit 1
fi

echo "SELESAI $DOMAIN"
