param([int]$Port = 8088)
$ErrorActionPreference = 'Stop'
$projectRoot = $PSScriptRoot
$phpCommand = Get-Command php -ErrorAction SilentlyContinue
if (-not $phpCommand) { throw 'PHP 8.2 veya ustu kurulmali ve PATH degiskenine eklenmelidir.' }
$phpExecutable = $phpCommand.Source
$moduleList = & $phpExecutable -m
$phpFlags = @()
if ($moduleList -notcontains 'pdo_sqlite') { $phpFlags += @('-d', 'extension=php_pdo_sqlite.dll') }
if ($moduleList -notcontains 'gd') { $phpFlags += @('-d', 'extension=php_gd.dll') }
Push-Location -LiteralPath $projectRoot
try {
    & $phpExecutable @phpFlags tools/install.php
    if ($LASTEXITCODE -ne 0) { throw 'Kurulum tamamlanamadi. PHP eklentilerini kontrol edin.' }
    Write-Host "Site: http://127.0.0.1:$Port"
    Write-Host "Panel: http://127.0.0.1:$Port/admin/"
    Write-Host 'Durdurmak icin Ctrl+C. Giris bilgileri: storage/admin-access.txt'
    & $phpExecutable @phpFlags -d upload_max_filesize=6M -d post_max_size=12M -S "127.0.0.1:$Port" router.php
} finally { Pop-Location }
