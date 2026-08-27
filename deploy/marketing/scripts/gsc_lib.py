"""Shared helpers for Google Search Console fetch + report scripts."""

from __future__ import annotations

import csv
import os
import sys
from datetime import date, timedelta
from pathlib import Path
from typing import Any

ROOT = Path(__file__).resolve().parents[3]
GSC_DIR = ROOT / "deploy" / "marketing" / "gsc"
DEFAULT_PROPERTY = "https://www.yourmechaniconline.com/"
DEFAULT_DAYS = 487  # ~16 months, matches GSC UI export default
ROW_LIMIT = 25000
SCOPES = ("https://www.googleapis.com/auth/webmasters.readonly",)


def configure_stdout() -> None:
    if sys.platform == "win32" and hasattr(sys.stdout, "reconfigure"):
        sys.stdout.reconfigure(encoding="utf-8", errors="replace")
    if sys.platform == "win32" and hasattr(sys.stderr, "reconfigure"):
        sys.stderr.reconfigure(encoding="utf-8", errors="replace")


def load_dotenv() -> None:
    """Load YMO_* vars from repo .env without overriding existing env."""
    env_path = ROOT / ".env"
    if not env_path.is_file():
        return
    for line in env_path.read_text(encoding="utf-8", errors="replace").splitlines():
        line = line.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        key, _, value = line.partition("=")
        key = key.strip()
        value = value.strip().strip('"').strip("'")
        if key.startswith("YMO_GSC_") and key not in os.environ:
            os.environ[key] = value
        if key == "GOOGLE_APPLICATION_CREDENTIALS" and key not in os.environ:
            os.environ[key] = value


def credentials_path() -> Path | None:
    load_dotenv()
    for key in ("YMO_GSC_CREDENTIALS", "GOOGLE_APPLICATION_CREDENTIALS"):
        raw = os.environ.get(key, "").strip()
        if not raw:
            continue
        path = Path(raw)
        if not path.is_absolute():
            path = ROOT / path
        if path.is_file():
            return path
    return None


def site_url() -> str:
    load_dotenv()
    prop = os.environ.get("YMO_GSC_PROPERTY", DEFAULT_PROPERTY).strip()
    if not prop.endswith("/") and prop.startswith("http"):
        prop += "/"
    return prop or DEFAULT_PROPERTY


def date_range(days: int | None = None) -> tuple[str, str]:
    load_dotenv()
    if days is None:
        try:
            days = int(os.environ.get("YMO_GSC_DAYS", DEFAULT_DAYS))
        except ValueError:
            days = DEFAULT_DAYS
    end = date.today() - timedelta(days=3)  # GSC data lag ~2–3 days
    start = end - timedelta(days=max(days - 1, 1))
    return start.isoformat(), end.isoformat()


def pct_str(ctr: float) -> str:
    return f"{ctr * 100:.2f}%"


def build_service():
    cred_path = credentials_path()
    if cred_path is None:
        raise FileNotFoundError(
            "GSC credentials not found. Set YMO_GSC_CREDENTIALS in .env "
            "(see deploy/marketing/GSC_SETUP.md)."
        )
    try:
        from google.oauth2 import service_account
        from googleapiclient.discovery import build
    except ImportError as exc:
        raise ImportError(
            "Install GSC dependencies: pip install -r deploy/marketing/requirements-gsc.txt"
        ) from exc

    creds = service_account.Credentials.from_service_account_file(
        str(cred_path),
        scopes=SCOPES,
    )
    return build("searchconsole", "v1", credentials=creds, cache_discovery=False)


def fetch_rows(
    service: Any,
    property_url: str,
    start_date: str,
    end_date: str,
    dimensions: list[str],
) -> list[dict[str, Any]]:
    rows: list[dict[str, Any]] = []
    start_row = 0
    while True:
        body = {
            "startDate": start_date,
            "endDate": end_date,
            "dimensions": dimensions,
            "rowLimit": ROW_LIMIT,
            "startRow": start_row,
        }
        resp = (
            service.searchanalytics()
            .query(siteUrl=property_url, body=body)
            .execute()
        )
        batch = resp.get("rows") or []
        rows.extend(batch)
        if len(batch) < ROW_LIMIT:
            break
        start_row += ROW_LIMIT
    return rows


def write_csv(path: Path, header: list[str], data_rows: list[list[Any]]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    with path.open("w", encoding="utf-8", newline="") as fh:
        writer = csv.writer(fh)
        writer.writerow(header)
        writer.writerows(data_rows)


def row_to_metrics(row: dict[str, Any]) -> tuple[int, int, str, float]:
    clicks = int(row.get("clicks") or 0)
    impressions = int(row.get("impressions") or 0)
    ctr = float(row.get("ctr") or 0.0)
    position = float(row.get("position") or 0.0)
    return clicks, impressions, pct_str(ctr), round(position, 2)


def list_accessible_sites(service: Any) -> list[str]:
    resp = service.sites().list().execute()
    entries = resp.get("siteEntry") or []
    return sorted(e.get("siteUrl", "") for e in entries if e.get("siteUrl"))
