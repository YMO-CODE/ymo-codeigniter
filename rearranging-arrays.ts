import * as readline from "readline";

function solve(N: number, A: number[]): string {
  const freq: Record<number, number> = {};

  for (let i = 0; i < N; i++) {
    freq[A[i]] = (freq[A[i]] || 0) + 1;
  }

  for (const key in freq) {
    if (freq[key] % 2 !== 0) {
      return "NO";
    }
  }

  return "YES";
}

const rl = readline.createInterface({
  input: process.stdin,
  output: process.stdout,
  terminal: false,
});

const lines: string[] = [];
rl.on("line", (line) => lines.push(line.trim()));

rl.on("close", () => {
  let ptr = 0;
  const T = parseInt(lines[ptr++], 10);
  const out: string[] = [];

  for (let t = 0; t < T; t++) {
    const N = parseInt(lines[ptr++], 10);
    const A = lines[ptr++].split(" ").map((x) => parseInt(x, 10));
    out.push(solve(N, A));
  }

  console.log(out.join("\n"));
});
