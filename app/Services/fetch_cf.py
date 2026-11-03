#!/usr/bin/env python3.10
import sys
from curl_cffi import requests

if len(sys.argv) < 2:
    sys.exit(1)

url = sys.argv[1]
headers = {
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36",
    "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,application/json,*/*;q=0.8",
    "Accept-Language": "id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7",
}

try:
    r = requests.get(url, impersonate="chrome124", headers=headers, timeout=12)
    sys.stdout.write(r.text)
except Exception as e:
    sys.stderr.write(str(e))
    sys.exit(1)
