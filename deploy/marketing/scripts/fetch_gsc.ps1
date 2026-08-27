# Refresh GSC data + print summary (Windows)
# Prerequisite: deploy/marketing/GSC_SETUP.md

$ErrorActionPreference = "Stop"
Set-Location (Split-Path (Split-Path $PSScriptRoot))

python -m pip install -r deploy/marketing/requirements-gsc.txt -q
python deploy/marketing/scripts/fetch_gsc.py
if ($LASTEXITCODE -eq 2) { exit 2 }
python deploy/marketing/scripts/gsc_report.py
exit $LASTEXITCODE
