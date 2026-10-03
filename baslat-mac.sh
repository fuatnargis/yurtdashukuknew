#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")"

if ! command -v php >/dev/null 2>&1; then
  echo "PHP bulunamadı. Mac'te PHP 8.2+ ve pdo_sqlite, mbstring, gd eklentilerini kurun." >&2
  exit 1
fi
if ! php -r 'exit(PHP_VERSION_ID >= 80200 ? 0 : 1);'; then
  echo "PHP 8.2 veya daha yeni bir sürüm gerekiyor." >&2
  exit 1
fi
for extension in pdo_sqlite mbstring gd; do
  if ! php -r 'exit(extension_loaded($argv[1]) ? 0 : 1);' "$extension"; then
    echo "Eksik PHP eklentisi: $extension" >&2
    exit 1
  fi
done

if [[ ! -s storage/site.sqlite ]]; then
  echo "storage/site.sqlite bulunamadı. Var olan verileri korumak için boş veritabanı oluşturulmadı." >&2
  exit 1
fi
if [[ -n "${HUKUK_DATABASE_URL:-}" || -n "${HUKUK_DATA_DIR:-}" ]] ||
   { [[ -f .env.local ]] && grep -Eq '^[[:space:]]*HUKUK_(DATABASE_URL|DATA_DIR)=.+' .env.local; }; then
  echo "Harici veritabanı/veri yolu ayarı bulundu. Yerel kopyayı açmadan önce bu ayarı kontrol edin." >&2
  exit 1
fi

port="${1:-8088}"
if [[ ! "$port" =~ ^[0-9]+$ ]] || (( port < 1 || port > 65535 )); then
  echo "Geçerli bir port numarası girin (1-65535)." >&2
  exit 1
fi

echo "Site: http://127.0.0.1:$port/"
echo "Yönetim paneli: http://127.0.0.1:$port/admin/"
echo "Durdurmak için bu Terminal penceresinde Ctrl+C tuşlarına basın."
exec php -d upload_max_filesize=6M -d post_max_size=12M -S "127.0.0.1:$port" router.php
