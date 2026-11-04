#!/usr/bin/env python3
"""Confere links internos exportados como: post_id<TAB>post_url<TAB>target_url."""

from concurrent.futures import ThreadPoolExecutor, as_completed
import subprocess
import sys
from urllib.parse import urlsplit


def check(url):
    parsed = urlsplit(url)
    host = parsed.hostname or "www.farmacia.ufmg.br"
    command = [
        "curl", "--noproxy", "*", "-ksS", "-L", "--max-time", "20",
        "--resolve", f"{host}:443:127.0.0.1", "-o", "/dev/null",
        "-w", "%{http_code}\t%{url_effective}", url,
    ]
    result = subprocess.run(command, capture_output=True, text=True)
    if result.returncode:
        return url, 0, result.stderr.strip()
    status, _, final_url = result.stdout.partition("\t")
    return url, int(status or 0), final_url


def main():
    source = sys.argv[1]
    rows = []
    with open(source, encoding="utf-8") as handle:
        for line in handle:
            fields = line.rstrip("\n").split("\t", 2)
            if len(fields) == 3:
                rows.append(tuple(fields))
    targets = sorted({row[2] for row in rows})
    results = {}
    with ThreadPoolExecutor(max_workers=20) as pool:
        futures = {pool.submit(check, url): url for url in targets}
        for future in as_completed(futures):
            url, status, final_url = future.result()
            results[url] = (status, final_url)
    broken = []
    for post_id, post_url, target in rows:
        status, final_url = results[target]
        if status == 0 or status in (404, 410) or status >= 500:
            broken.append((post_id, post_url, target, status, final_url))
    for row in broken:
        print("\t".join(map(str, row)))
    print(f"checked_targets={len(targets)} broken_references={len(broken)}", file=sys.stderr)


if __name__ == "__main__":
    main()
