import * as readline from "readline";

const MAX = 1000000;
const INF = 1000000000;

// Step 1: generate all special numbers (digits 4 and 5 only)
const specials: number[] = [];
const queue = [4, 5];

for (let i = 0; i < queue.length; i++) {
  const n = queue[i];
  if (n > MAX) continue;

  specials.push(n);
  if (n * 10 + 4 <= MAX) queue.push(n * 10 + 4);
  if (n * 10 + 5 <= MAX) queue.push(n * 10 + 5);
}

specials.sort((a, b) => a - b);

// Step 2: dp[i] = minimum special numbers that sum to i
const dp: number[] = new Array(MAX + 1).fill(INF);
dp[0] = 0;

for (let i = 1; i <= MAX; i++) {
  for (const s of specials) {
    if (s > i) break;
    if (dp[i - s] + 1 < dp[i]) {
      dp[i] = dp[i - s] + 1;
    }
  }
}

// Step 3: read input and print answers
const rl = readline.createInterface({
  input: process.stdin,
  output: process.stdout,
  terminal: false,
});

const lines: string[] = [];
rl.on("line", (line) => lines.push(line.trim()));

rl.on("close", () => {
  const T = parseInt(lines[0], 10);
  const out: string[] = [];

  for (let t = 1; t <= T; t++) {
    const N = parseInt(lines[t], 10);
    out.push(dp[N] === INF ? "-1" : String(dp[N]));
  }

  console.log(out.join("\n"));
});
