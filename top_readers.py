"""Summer reading program: find the top five readers by points.

Scoring: 50 points per book read + 1 point per page.
Data file: https://public.karat.io/content/test/test_file.txt
Each line: <reader id>,<reader name>,<book name>,<number of pages>
"""

import csv
import sys
from collections import defaultdict
from urllib.request import urlopen

DATA_URL = "https://public.karat.io/content/test/test_file.txt"
POINTS_PER_BOOK = 50


def read_lines(source):
    """Yield lines from a local path or, by default, the remote data file."""
    if source:
        with open(source, newline="", encoding="utf-8") as fh:
            yield from fh
    else:
        with urlopen(DATA_URL) as resp:
            for raw in resp:
                yield raw.decode("utf-8")


def score_readers(lines):
    """Return {reader_id: total_points} from CSV lines.

    Book names may contain commas, so we treat the last field as pages and the
    first as the id, joining anything in between as the book name.
    """
    points = defaultdict(int)
    for row in csv.reader(lines):
        if len(row) < 4:
            continue
        reader_id, pages = row[0].strip(), row[-1].strip()
        if not pages.isdigit():
            continue  # skip header or malformed rows
        points[reader_id] += POINTS_PER_BOOK + int(pages)
    return points


def top_readers(points, n=5):
    # Sort by points descending; ties broken by id for stable output.
    return sorted(points.items(), key=lambda kv: (-kv[1], kv[0]))[:n]


def main():
    source = sys.argv[1] if len(sys.argv) > 1 else None
    points = score_readers(read_lines(source))
    for reader_id, total in top_readers(points):
        print(reader_id, total)


if __name__ == "__main__":
    main()
