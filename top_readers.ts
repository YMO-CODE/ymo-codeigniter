/**
 * Summer reading program: find the top five readers by points.
 *
 * Scoring: 50 points per book read + 1 point per page.
 * Data file: https://public.karat.io/content/test/test_file.txt
 * Each line: <reader id>,<reader name>,<book name>,<number of pages>
 *
 * Run: npx tsx top_readers.ts            (fetches from the URL)
 *      npx tsx top_readers.ts file.txt   (uses a local file)
 */

import { readFile } from "node:fs/promises";

const DATA_URL = "https://public.karat.io/content/test/test_file.txt";
const POINTS_PER_BOOK = 50;

async function readContent(source?: string): Promise<string> {
  const target = source ?? DATA_URL;
  // Fetch over the network for URLs; otherwise read a local file.
  if (/^https?:\/\//i.test(target)) {
    const resp = await fetch(target);
    if (!resp.ok) throw new Error(`Failed to fetch data: ${resp.status}`);
    return resp.text();
  }
  return readFile(target, "utf-8");
}

function scoreReaders(content: string): Map<string, number> {
  const points = new Map<string, number>();
  for (const line of content.split(/\r?\n/)) {
    if (!line.trim()) continue;
    // Book names may contain commas: id is the first field, pages the last.
    const fields = line.split(",");
    if (fields.length < 4) continue;
    const readerId = fields[0].trim();
    const pages = Number(fields[fields.length - 1].trim());
    if (!Number.isInteger(pages)) continue; // skip header / malformed rows
    points.set(readerId, (points.get(readerId) ?? 0) + POINTS_PER_BOOK + pages);
  }
  return points;
}

function topReaders(
  points: Map<string, number>,
  n = 5,
): Array<[string, number]> {
  // Sort by points descending; ties broken by id for stable output.
  return [...points.entries()]
    .sort((a, b) => b[1] - a[1] || a[0].localeCompare(b[0]))
    .slice(0, n);
}

async function main(): Promise<void> {
  const source = process.argv[2];
  const points = scoreReaders(await readContent(source));
  for (const [readerId, total] of topReaders(points)) {
    console.log(readerId, total);
  }
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
