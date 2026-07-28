# Pull full WordPress page JSON into deploy/marketing/wp-cache/
# Run in PowerShell from repo root (needs network access to yourmechaniconline.com):
#
#   powershell -File deploy/marketing/scripts/fetch_wp_cache.ps1
#
# Then apply content:
#   python deploy/marketing/scripts/migrate_wp_from_cache.py

$base = "https://yourmechaniconline.com/wp-json/wp/v2/pages"
$fields = "id,slug,link,parent,title,content,excerpt,aioseo_meta_data,aioseo_head_json"
$outDir = Join-Path $PSScriptRoot "..\wp-cache"
New-Item -ItemType Directory -Force -Path $outDir | Out-Null

$page = 1
while ($true) {
    $url = "$base`?per_page=20&page=$page&_fields=$fields"
    $outFile = Join-Path $outDir ("pages-{0:D2}.json" -f $page)
    try {
        Write-Host "Fetching page $page ..."
        Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 120 -OutFile $outFile
        $json = Get-Content $outFile -Raw | ConvertFrom-Json
        if (-not $json -or $json.Count -eq 0) {
            Remove-Item $outFile -ErrorAction SilentlyContinue
            break
        }
        Write-Host "  saved $($json.Count) pages -> $outFile"
        $page++
    } catch {
        Write-Host "Done or error at page ${page}: $_"
        break
    }
}

Write-Host "Run: python deploy/marketing/scripts/migrate_wp_from_cache.py"
