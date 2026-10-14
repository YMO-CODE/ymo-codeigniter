# Quick HTML sanity check for v2 service pages (run against local :8080).
$ErrorActionPreference = 'Stop'
$registry = Join-Path $PSScriptRoot '..\..\..\application\cache\marketing_pages_registry.php'
$text = Get-Content -Raw -Path $registry
$keys = @()
$current = $null
foreach ($line in ($text -split "`n")) {
    if ($line -match "^  '([^']+)' =>") {
        $current = $matches[1]
    } elseif ($current -and $line -match "^    'page_type' => 'service'") {
        $keys += $current
        $current = $null
    } elseif ($line -match "^  '[^']+' =>") {
        $current = $null
    }
}
$keys = $keys | Sort-Object -Unique
$base = 'http://www.yourmechaniconline.com:8080/'
$issues = @()
foreach ($key in $keys) {
    $url = $base + $key.TrimStart('/')
    try {
        $html = (Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 30).Content
    } catch {
        $issues += [pscustomobject]@{ slug = $key; issue = "fetch_failed: $_" }
        continue
    }
    if ($html -match 'ymo-v2-overview-layout__includes' -and $html -match '</aside>\s*</div>\s*</div>\s*</div>\s*<div class="ymo-content-section ymo-v2-why-faq') {
        $issues += [pscustomobject]@{ slug = $key; issue = 'extra_close_after_includes' }
    }
    if ($html -match 'ymo-v2-overview-layout__includes' -and $html -match 'ymo-v2-service-overview[\s\S]*ymo-v2-why-faq' -and $html -notmatch '</aside>\s*(?:</div>\s*){2,}\s*<div class="ymo-content-section ymo-v2-why-faq') {
        $issues += [pscustomobject]@{ slug = $key; issue = 'overview_boundary' }
    }
    if ($html -match 'ymo-v2-overview-layout--split' -and $html -match 'ymo-v2-checklist__item') {
        $count = ([regex]::Matches($html, 'ymo-v2-checklist__item')).Count
        if ($count -gt 7) {
            $issues += [pscustomobject]@{ slug = $key; issue = "split_with_${count}_includes" }
        }
    }
    if ($html -match 'ymo-v2-service-overview[\s\S]{0,8000}ymo-v2-why-faq' -and $html -notmatch 'ymo-v2-why-faq[\s\S]*ymo-v2-service-overview') {
        # nested why inside overview (rough)
        $start = $html.IndexOf('ymo-v2-service-overview')
        $why = $html.IndexOf('ymo-v2-why-faq', $start)
        $closeOverview = $html.IndexOf('</div></div>', $start)
        if ($why -gt 0 -and $closeOverview -gt $why) {
            $issues += [pscustomobject]@{ slug = $key; issue = 'why_faq_nested_in_overview' }
        }
    }
}
Write-Host "Checked $($keys.Count) service pages."
if ($issues.Count -eq 0) {
    Write-Host 'No issues flagged.'
    exit 0
}
$issues | Format-Table -AutoSize
exit 1
